<?php
namespace App\Libraries\Platform;

use RuntimeException;

class Affiliate
{
    public function __construct(private Store $s) {}

    public static function facebookUrl(string $value): string
    {
        $value=trim($value);if($value===''){return '';}$parts=parse_url($value);
        if(strlen($value)>250 || !$parts || ($parts['scheme']??'')!=='https' || !in_array(strtolower($parts['host']??''),['facebook.com','www.facebook.com','m.facebook.com'],true) || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])){throw new RuntimeException('ใช้ลิงก์โปรไฟล์หรือเพจ https://www.facebook.com/... เท่านั้น');}
        $path=trim($parts['path']??'','/');$query=[];parse_str($parts['query']??'',$query);
        if($path==='profile.php' && isset($query['id']) && is_string($query['id']) && preg_match('/^[0-9]{1,30}$/D',$query['id'])){return 'https://www.facebook.com/profile.php?id='.$query['id'];}
        if(str_ends_with(strtolower($path),'.php') || !preg_match('~^(?:[A-Za-z0-9._-]{1,100}|(?:people|pages)/[A-Za-z0-9._%\~-]+/[0-9]{1,30}|share/[A-Za-z0-9]+)$~D',$path) || in_array(strtolower($path),['login','logout','sharer','dialog','share','l','help'],true)){throw new RuntimeException('กรุณาคัดลอกลิงก์โปรไฟล์หรือเพจ Facebook ให้ถูกต้อง');}
        return 'https://www.facebook.com/'.$path;
    }

    /** Only real LINE profile/add-friend URLs; never an arbitrary redirect destination. */
    public static function lineUrl(string $value): string
    {
        $value=trim($value);
        if($value===''){return '';}
        if(strlen($value)>250){throw new RuntimeException('ลิงก์ LINE ยาวเกินไป');}
        if(preg_match('/^@[A-Za-z0-9._-]{2,100}$/D',$value)){
            return 'https://line.me/R/ti/p/'.rawurlencode($value);
        }
        if(!preg_match('#^https://(?:line\.me/(?:R/)?ti/p/(?:%40|@|~)?[A-Za-z0-9._~-]+|lin\.ee/[A-Za-z0-9_-]+)(?:\?[A-Za-z0-9_%=&.-]+)?$#D',$value)){
            throw new RuntimeException('ใช้ลิงก์เพิ่มเพื่อน https://line.me/ti/p/... หรือ https://lin.ee/... หรือ LINE OA ID ที่ขึ้นต้นด้วย @');
        }
        return $value;
    }

    public function owner(string $code): ?array
    {
        $code = trim($code);
        if(!preg_match('/^[A-Za-z0-9_-]{3,32}$/D',$code)){return null;}
        $owner = $this->s->one('SELECT id,referral_code,public_name,line_url,facebook_url,affiliate_headline,affiliate_bio,affiliate_public FROM users WHERE LOWER(referral_code)=LOWER(?) AND active=1 AND role!=?',[$code,'member']);
        if ($owner) { return (new ProfilePhoto($this->s))->decorate($owner); }
        $aliases = json_decode($this->s->setting('affiliate_legacy_codes', '{}'), true);
        $legacyUser = is_array($aliases) ? ($aliases[strtolower($code)] ?? null) : null;
        $legacy=$legacyUser ? $this->s->one('SELECT id,referral_code,public_name,line_url,facebook_url,affiliate_headline,affiliate_bio,affiliate_public FROM users WHERE id=? AND active=1 AND role!=?', [(int)$legacyUser,'member']) : null;
        return $legacy?(new ProfilePhoto($this->s))->decorate($legacy):null;
    }

    /**
     * Generate next sequential Affiliate ID (referral_code).
     * FIXED RULE: starts at 0001, then 0002, 0003. Minimum four digits; never recycle issued IDs. Do not change this rule.
     */
    public static function nextCode(Store $store): string
    {
        $rows = $store->all("SELECT referral_code FROM users WHERE referral_code GLOB '[0-9]*'");
        $max = (int)$store->setting('affiliate_sequence', '0');
        foreach ($rows as $r) {
            $code = trim((string)$r['referral_code']);
            if (preg_match('/^(?:[0-9]{4}|[1-9][0-9]{4,17})$/D', $code) && (int)$code > 0) {
                $num = (int)$code;
                if ($num > $max) {
                    $max = $num;
                }
            }
        }
        do {
            $max++;
            $candidate = str_pad((string)$max, 4, '0', STR_PAD_LEFT);
            $exists = $store->one("SELECT id FROM users WHERE LOWER(referral_code) = LOWER(?)", [$candidate]);
        } while ($exists);

        return $candidate;
    }

    /**
     * Validate custom Affiliate ID.
     */
    public static function validateCustomCode(string $code, ?int $excludeUserId, Store $store): string
    {
        $current = $excludeUserId ? $store->one('SELECT referral_code FROM users WHERE id=?', [$excludeUserId]) : null;
        if ($current && hash_equals($current['referral_code'], trim($code))) { return $current['referral_code']; }
        throw new RuntimeException('Affiliate ID ออกโดยระบบเท่านั้น เริ่มจาก 0001 และเรียงต่อเนื่อง ไม่สามารถกำหนดหรือเปลี่ยนเอง');
    }

    /** Explicit/session attribution wins over the current signed-in account. */
    public function resolve(?array $context,string $cookie,?int $self=null): ?array
    {
        if($context && (int)($context['expires']??0)>=time()){
            return $this->owner((string)($context['code']??''));
        }
        if($cookie!==''){
            if(!preg_match('/^[a-f0-9]{48}$/D',$cookie)){return null;}
            $row=$this->s->one('SELECT u.referral_code FROM referral_tokens t JOIN users u ON u.id=t.sponsor_id WHERE t.token_hash=? AND t.expires>=? AND u.active=1',[hash('sha256',$cookie),time()]);
            return $row?$this->owner($row['referral_code']):null;
        }
        if($self){
            $row=$this->s->one('SELECT referral_code FROM users WHERE id=? AND active=1',[$self]);
            return $row?$this->owner($row['referral_code']):null;
        }
        return null;
    }

    public function saveProfile(int $id,array $input,int $actor): void
    {
        $name=trim((string)($input['public_name']??''));$headline=trim((string)($input['affiliate_headline']??''));$bio=trim((string)($input['affiliate_bio']??''));
        $public=(string)($input['affiliate_public']??'0');$line=self::lineUrl((string)($input['line_url']??''));
        $facebook=self::facebookUrl((string)($input['facebook_url']??($this->s->one('SELECT facebook_url FROM users WHERE id=?',[$id])['facebook_url']??'')));
        $province=trim((string)($input['public_province']??($this->s->one('SELECT public_province FROM users WHERE id=?',[$id])['public_province']??'')));
        if(mb_strlen($province)>60){throw new RuntimeException('จังหวัดที่เผยแพร่ยาวเกินกำหนด');}
        $bankName=trim((string)($input['bank_name']??''));
        $bankAccount=trim((string)($input['bank_account']??''));
        $bankAccountName=trim((string)($input['bank_account_name']??''));
        $customCode=isset($input['referral_code'])?trim((string)$input['referral_code']):null;

        if(mb_strlen($name)>100 || mb_strlen($headline)>180 || mb_strlen($bio)>2000 || mb_strlen($bankName)>100 || mb_strlen($bankAccount)>50 || mb_strlen($bankAccountName)>120 || !in_array($public,['0','1'],true)){
            throw new RuntimeException('ข้อมูลโปรไฟล์หรือสถานะไม่ถูกต้อง');
        }
        if($public==='1' && (mb_strlen($name)<2 || $line==='')){throw new RuntimeException('กรอกชื่อที่แสดงและลิงก์ LINE ก่อนเปิดเผยแพร่โปรไฟล์');}

        $validatedCode=null;
        if($customCode!==null && $customCode!==''){
            $current=$this->s->one('SELECT referral_code FROM users WHERE id=?',[$id]);
            if(!$current || strcasecmp($current['referral_code'],$customCode)!==0){
                $validatedCode=self::validateCustomCode($customCode,$id,$this->s);
            }
        }

        $this->s->transaction(function() use($id,$actor,$name,$headline,$bio,$line,$facebook,$province,$public,$bankName,$bankAccount,$bankAccountName,$validatedCode) {
            if($actor!==$id && !$this->s->one("SELECT id FROM users WHERE id=? AND role='admin' AND active=1",[$actor])){throw new RuntimeException('ไม่มีสิทธิ์แก้ไขโปรไฟล์นี้');}
            if(!$this->s->one('SELECT id FROM users WHERE id=? AND active=1',[$id])){throw new RuntimeException('ไม่พบสมาชิกที่ใช้งาน');}
            if($validatedCode!==null){
                $this->s->exec('UPDATE users SET public_name=?,line_url=?,facebook_url=?,public_province=?,affiliate_headline=?,affiliate_bio=?,affiliate_public=?,bank_name=?,bank_account=?,bank_account_name=?,referral_code=? WHERE id=?',[$name,$line,$facebook,$province,$headline,$bio,(int)$public,$bankName,$bankAccount,$bankAccountName,$validatedCode,$id]);
                $this->s->audit($actor,'affiliate_profile_updated','user',$id,($public==='1'?'published':'hidden').'; code:'.$validatedCode);
            } else {
                $this->s->exec('UPDATE users SET public_name=?,line_url=?,facebook_url=?,public_province=?,affiliate_headline=?,affiliate_bio=?,affiliate_public=?,bank_name=?,bank_account=?,bank_account_name=? WHERE id=?',[$name,$line,$facebook,$province,$headline,$bio,(int)$public,$bankName,$bankAccount,$bankAccountName,$id]);
                $this->s->audit($actor,'affiliate_profile_updated','user',$id,$public==='1'?'published':'hidden');
            }
        });
    }

    private function treeSql(): string
    {
        return "WITH RECURSIVE network(id,name,referral_code,sponsor_id,depth,created_at,active,path) AS (
            SELECT id,name,referral_code,sponsor_id,1,created_at,active,printf(',%d,%d,',sponsor_id,id) FROM users WHERE sponsor_id=? AND id<>sponsor_id
            UNION ALL SELECT u.id,u.name,u.referral_code,u.sponsor_id,n.depth+1,u.created_at,u.active,n.path||u.id||','
            FROM users u JOIN network n ON u.sponsor_id=n.id WHERE n.depth<30 AND instr(n.path,','||u.id||',')=0
        ) ";
    }

    public function network(int $owner,string $query='',int $page=1,bool $direct=false): array
    {
        $params=[$owner,$owner,'%'.$query.'%','%'.$query.'%'];
        $where=" WHERE (n.name LIKE ? OR n.referral_code LIKE ?)".($direct?' AND n.depth=1':'');
        $rows=$this->s->all($this->treeSql()."SELECT n.id,n.name,n.referral_code,n.sponsor_id,n.depth,n.created_at,n.active,p.name sponsor,COALESCE(a.stage,'new') stage,COALESCE(a.note,'') note FROM network n JOIN users p ON p.id=n.sponsor_id LEFT JOIN affiliate_notes a ON a.member_id=n.id AND a.owner_id=?".$where.' ORDER BY n.depth,n.id LIMIT 50 OFFSET '.((max(1,$page)-1)*50),$params);
        $total=$this->s->one($this->treeSql().'SELECT COUNT(*) n FROM network n'.$where,[$owner,'%'.$query.'%','%'.$query.'%'])['n'];
        return ['members'=>$rows,'total'=>$total];
    }

    public function stats(int $owner): array
    {
        $counts=$this->s->one($this->treeSql().'SELECT COUNT(*) total,COALESCE(SUM(depth=1),0) direct FROM network',[$owner]);
        return $counts+['clicks'=>$this->s->one('SELECT COUNT(*) n FROM referral_clicks WHERE sponsor_id=?',[$owner])['n'],
            'contacts'=>$this->s->one("SELECT COUNT(*) n FROM affiliate_events WHERE sponsor_id=? AND kind='contact'",[$owner])['n']];
    }

    public function saveNote(int $owner,int $member,string $stage,string $note): void
    {
        if(!in_array($stage,['new','contacted','following'],true) || mb_strlen($note)>1000){throw new RuntimeException('สถานะหรือบันทึกไม่ถูกต้อง');}
        $this->s->transaction(function() use($owner,$member,$stage,$note) {
            if(!$this->s->one($this->treeSql().'SELECT id FROM network WHERE id=?',[$owner,$member])){throw new RuntimeException('ไม่พบสมาชิกในสายงานของคุณ');}
            $this->s->exec('INSERT INTO affiliate_notes(owner_id,member_id,stage,note,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(owner_id,member_id) DO UPDATE SET stage=excluded.stage,note=excluded.note,updated_at=excluded.updated_at',[$owner,$member,$stage,trim($note),Store::now()]);
            $this->s->audit($owner,'affiliate_member_followup','user',$member,$stage);
        });
    }
}
