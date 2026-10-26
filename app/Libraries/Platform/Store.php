<?php

namespace App\Libraries\Platform;

use PDO;
use RuntimeException;
use Throwable;

/** Local transactional store. All money is integer satang, never floating point. */
class Store
{
    public PDO $db;
    private bool $transactionActive = false;
    private int $savepointNumber = 0;

    public function __construct(?string $path = null)
    {
        $path ??= (string)env('PLATFORM_DB_PATH', WRITEPATH . 'platform.sqlite');
        $this->db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $this->db->exec('PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000;');
        if (env('DEEPSEEK_API_KEY') && $this->installed() && $this->setting('deepseek_init') !== '1') {
            $this->set('ai_enabled', '1');
            $this->set('deepseek_init', '1');
        }
    }

    public function installed(): bool { return (bool) $this->one("SELECT name FROM sqlite_master WHERE type='table' AND name='users'"); }
    public function all(string $sql, array $params = []): array { $q = $this->db->prepare($sql); $q->execute($params); return $q->fetchAll(); }
    public function one(string $sql, array $params = []): ?array { return $this->all($sql, $params)[0] ?? null; }
    public function exec(string $sql, array $params = []): int { $q = $this->db->prepare($sql); $q->execute($params); return $q->rowCount(); }
    public function insert(string $table, array $data): int
    {
        // Invariant: every account receives the next immutable Affiliate ID.
        if ($table === 'users' && !$this->transactionActive) {
            return $this->transaction(fn() => $this->insert($table, $data));
        }
        if ($table === 'users') { $data['referral_code'] = Affiliate::nextCode($this); }
        if (!preg_match('/^[a-z_]+$/', $table) || array_filter(array_keys($data), fn($k) => !preg_match('/^[a-z_]+$/', $k))) { throw new RuntimeException('Invalid column'); }
        $this->exec('INSERT INTO ' . $table . ' (' . implode(',', array_keys($data)) . ') VALUES (' . implode(',', array_fill(0, count($data), '?')) . ')', array_values($data));
        $id = (int) $this->db->lastInsertId();
        if ($table === 'users') { $this->set('affiliate_sequence', $data['referral_code']); }
        return $id;
    }
    public function transaction(callable $callback): mixed
    {
        if ($this->transactionActive) {
            $savepoint='sn_nested_'.(++$this->savepointNumber);
            $this->db->exec('SAVEPOINT '.$savepoint);
            try { $result=$callback();$this->db->exec('RELEASE SAVEPOINT '.$savepoint);return $result; }
            catch(Throwable $e){$this->db->exec('ROLLBACK TO SAVEPOINT '.$savepoint);$this->db->exec('RELEASE SAVEPOINT '.$savepoint);throw $e;}
        }
        $this->db->exec('BEGIN IMMEDIATE');
        $this->transactionActive = true;
        try { $result = $callback(); $this->db->exec('COMMIT'); return $result; }
        catch (Throwable $e) { $this->db->exec('ROLLBACK'); throw $e; }
        finally { $this->transactionActive = false; }
    }
    public function audit(?int $actor, string $action, string $entity, int $id = 0, string $detail = ''): void
    { $this->insert('audit', ['actor_id' => $actor, 'action' => $action, 'entity' => $entity, 'entity_id' => $id, 'detail' => $detail, 'created_at' => self::now()]); }
    public static function now(): string { return gmdate('Y-m-d H:i:s'); }
    public function setting(string $key, string $default = ''): string { return $this->one('SELECT value FROM settings WHERE key=?', [$key])['value'] ?? $default; }
    public function set(string $key, string $value): void { $this->exec('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value', [$key, $value]); }
    public static function amount(string $value): int
    {
        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $value)) { throw new RuntimeException('จำนวนเงินไม่ถูกต้อง'); }
        [$whole, $decimal] = array_pad(explode('.', $value), 2, '');
        return (int) $whole * 100 + (int) str_pad($decimal, 2, '0');
    }
    public function rate(string $key, int $limit, int $seconds): void
    {
        $key = hash('sha256', $key . ':' . (int) floor(time() / $seconds));
        $this->exec('INSERT INTO rate_limits(key,hits,expires) VALUES(?,1,?) ON CONFLICT(key) DO UPDATE SET hits=hits+1', [$key, time() + $seconds]);
        if (($this->one('SELECT hits FROM rate_limits WHERE key=?', [$key])['hits'] ?? 0) > $limit) { throw new RuntimeException('ทำรายการถี่เกินไป กรุณารอสักครู่'); }
    }
    public function install(): void
    {
        $this->db->exec(file_get_contents(APPPATH . 'Database/platform.sql'));
        $this->db->exec(file_get_contents(APPPATH . 'Database/platform_billing.sql'));
        $this->db->exec(file_get_contents(APPPATH . 'Database/affiliate_sequence.sql'));
        $this->db->exec(file_get_contents(APPPATH . 'Database/workspace.sql'));
        $this->db->exec(file_get_contents(APPPATH . 'Database/registration_plans.sql'));
        $this->db->exec(file_get_contents(APPPATH . 'Database/member_photos.sql'));
        $this->db->exec(file_get_contents(APPPATH . 'Database/member_promotion.sql'));
        $this->db->exec(file_get_contents(APPPATH . 'Database/member_name_publications.sql'));
        $this->db->exec(file_get_contents(APPPATH . 'Database/pending_referrals.sql'));
        $this->migrateAnnualOfferAmounts();
        $this->migrateTeamRoles();
        $this->migrateProductPV();
        $productColumns = array_column($this->all('PRAGMA table_info(products)'), 'name');
        foreach (['member_price_satang' => 'INTEGER CHECK(member_price_satang IS NULL OR member_price_satang>=0)', 'inquiry_only' => 'INTEGER NOT NULL DEFAULT 0', 'short_description' => "TEXT NOT NULL DEFAULT ''", 'inquiry_label' => "TEXT NOT NULL DEFAULT ''", 'discount_kind' => "TEXT NOT NULL DEFAULT '' CHECK(discount_kind IN ('','fixed','percent'))", 'discount_value' => 'INTEGER NOT NULL DEFAULT 0 CHECK(discount_value>=0)'] as $column => $definition) {
            if (!in_array($column, $productColumns, true)) { $this->db->exec('ALTER TABLE products ADD COLUMN ' . $column . ' ' . $definition); }
        }
        if (!in_array('auth_version', array_column($this->all('PRAGMA table_info(users)'), 'name'))) {
            $this->db->exec('ALTER TABLE users ADD COLUMN auth_version INTEGER NOT NULL DEFAULT 0');
        }
        $columns=array_column($this->all('PRAGMA table_info(users)'),'name');
        foreach(['public_province'=>"TEXT NOT NULL DEFAULT ''",'facebook_url'=>"TEXT NOT NULL DEFAULT ''",'line_url'=>"TEXT NOT NULL DEFAULT ''",'public_name'=>"TEXT NOT NULL DEFAULT ''",'affiliate_headline'=>"TEXT NOT NULL DEFAULT ''",'affiliate_bio'=>"TEXT NOT NULL DEFAULT ''",'affiliate_public'=>'INTEGER NOT NULL DEFAULT 0','bank_name'=>"TEXT NOT NULL DEFAULT ''",'bank_account'=>"TEXT NOT NULL DEFAULT ''",'bank_account_name'=>"TEXT NOT NULL DEFAULT ''"] as $column=>$definition){
            if(!in_array($column,$columns,true)){$this->db->exec('ALTER TABLE users ADD COLUMN '.$column.' '.$definition);}
        }
        $defaultRoleMenus = json_encode(Access::DEFAULT_ROLE_MENUS, JSON_UNESCAPED_UNICODE);
        foreach (['store_name' => 'Project Field Shop', 'shipping_satang' => '0', 'bank_details' => '', 'bot_enabled' => '1', 'ai_enabled' => '0', 'analytics_enabled' => '1', 'bot_knowledge' => 'ยินดีต้อนรับ สามารถดูสินค้าและสั่งซื้อได้ที่หน้าร้าน หากต้องการคำแนะนำให้ฝากข้อมูลติดต่อและทีมงานจะติดต่อกลับ', 'line_enabled' => '0', 'facebook_enabled' => '0', 'role_menus' => $defaultRoleMenus] as $k => $v) {
            $this->exec('INSERT OR IGNORE INTO settings(key,value) VALUES(?,?)', [$k, $v]);
        }
        if ($this->setting('workspace_permissions_v1') !== '1') {
            $menus=json_decode($this->setting('role_menus','{}'),true)?:[];
            foreach(Access::DEFAULT_ROLE_MENUS as $role=>$defaults){$menus[$role]=array_values(array_unique([...($menus[$role]??$defaults),'salespages']));}
            $this->set('role_menus',json_encode($menus,JSON_UNESCAPED_UNICODE));$this->set('workspace_permissions_v1','1');
        }
    }
    /** Preserve columns, indexes, triggers and references while expanding SQLite's role CHECK. */
    public function migrateTeamRoles(): void
    {
        $sql=$this->one("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'")['sql']??'';
        if (str_contains($sql,"'downline_team'")) { return; }
        $updated=preg_replace("/CHECK\\s*\\(role IN \\(([^)]*)\\)\\)/i", "CHECK(role IN ($1,'downline_team','external_team'))",$sql,1,$count);
        if ($count!==1) { throw new RuntimeException('Cannot safely expand users role constraint'); }
        $objects=$this->all("SELECT sql FROM sqlite_master WHERE tbl_name='users' AND type IN ('index','trigger') AND sql IS NOT NULL");
        $before=$this->all('PRAGMA foreign_key_check');
        $sequence=(int)($this->one("SELECT seq FROM sqlite_sequence WHERE name='users'")['seq']??0);
        $this->db->exec('PRAGMA foreign_keys=OFF');
        try {
            $this->transaction(function() use($updated,$objects,$before,$sequence) {
                $this->db->exec(preg_replace('/^CREATE TABLE (?:"users"|users)/i','CREATE TABLE users_role_migration',$updated));
                $columns=implode(',',array_map(fn($r)=>'"'.$r['name'].'"',$this->all('PRAGMA table_info(users)')));
                $this->db->exec('INSERT INTO users_role_migration('.$columns.') SELECT '.$columns.' FROM users');
                $this->db->exec('DROP TABLE users');
                $this->db->exec('ALTER TABLE users_role_migration RENAME TO users');
                $this->exec("UPDATE sqlite_sequence SET seq=MAX(seq,?) WHERE name='users'",[$sequence]);
                foreach($objects as $object){$this->db->exec($object['sql']);}
                if ($this->all('PRAGMA foreign_key_check')!==$before) { throw new RuntimeException('Role migration changed foreign key integrity'); }
            });
        } finally { $this->db->exec('PRAGMA foreign_keys=ON'); }
    }
    public function migrateAnnualOfferAmounts(): void
    {
        foreach(['platform_invoices','platform_renewal_orders'] as $table){
            $sql=$this->one("SELECT sql FROM sqlite_master WHERE type='table' AND name=?",[$table])['sql']??'';
            if(!str_contains($sql,'amount_satang=99000') && !str_contains($sql,'amount_satang IN (39900,99000)')){continue;}
            $objects=$this->all("SELECT sql FROM sqlite_master WHERE tbl_name=? AND type IN ('index','trigger') AND sql IS NOT NULL",[$table]);
            $before=$this->all('PRAGMA foreign_key_check');
            $sequence=(int)($this->one('SELECT seq FROM sqlite_sequence WHERE name=?',[$table])['seq']??0);
            $replacement=str_replace(['amount_satang=99000','amount_satang IN (39900,99000)'],'amount_satang>0',$sql);
            $replacement=preg_replace('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?["`]?'.preg_quote($table,'/').'["`]?/i','CREATE TABLE '.$table.'_offer_migration',$replacement,1);
            $this->db->exec('PRAGMA foreign_keys=OFF');
            try{$this->transaction(function()use($table,$replacement,$objects,$sequence,$before){
                $this->db->exec($replacement);
                $this->db->exec('INSERT INTO '.$table.'_offer_migration SELECT * FROM '.$table);
                $this->db->exec('DROP TABLE '.$table);
                $this->db->exec('ALTER TABLE '.$table.'_offer_migration RENAME TO '.$table);
                $this->exec('UPDATE sqlite_sequence SET seq=MAX(seq,?) WHERE name=?',[$sequence,$table]);
                foreach($objects as $object){$this->db->exec($object['sql']);}
                if($this->all('PRAGMA foreign_key_check')!==$before){throw new RuntimeException('Annual offer migration changed foreign key integrity');}
            });}finally{$this->db->exec('PRAGMA foreign_keys=ON');}
        }
    }
    public function migrateProductPV(): void
    {
        foreach(['products','order_items'] as $table){
            if(!in_array('pv',array_column($this->all('PRAGMA table_info('.$table.')'),'name'),true)){
                $this->db->exec('ALTER TABLE '.$table.' ADD COLUMN pv INTEGER NOT NULL DEFAULT 0 CHECK(pv>=0)');
            }
        }
    }
}
