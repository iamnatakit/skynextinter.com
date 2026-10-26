<?php
namespace App\Libraries\Platform;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/** Platform access is separate from staff roles: a subscription never grants admin permissions. */
class Membership
{
    public const ANNUAL_SATANG = 99000;
    public function __construct(private Store $s) {}
    public static function utc(string $value): DateTimeImmutable { return new DateTimeImmutable($value, new DateTimeZone('UTC')); }
    public function ensure(int $user): array
    {
        $u = $this->s->one('SELECT id,created_at FROM users WHERE id=?', [$user]);
        if (!$u) { throw new RuntimeException('ไม่พบผู้ใช้'); }
        $start = self::utc($u['created_at']);
        $this->s->exec('INSERT OR IGNORE INTO platform_subscriptions(user_id,trial_started_at,trial_ends_at,created_at) VALUES(?,?,?,?)',
            [$user, $start->format('Y-m-d H:i:s'), $start->modify('+30 days')->format('Y-m-d H:i:s'), Store::now()]);
        return $this->s->one('SELECT * FROM platform_subscriptions WHERE user_id=?', [$user]);
    }
    public function roots(): array
    {
        $root=$this->s->one("SELECT id FROM users WHERE referral_code='0005' AND active=1");
        return $root ? [(int)$root['id']] : [];
    }
    /** New SKYNEXT members only; the root itself is not its own downline. */
    public function isDownline(int $user): bool
    {
        if(($this->s->one('SELECT role FROM users WHERE id=?',[$user])['role']??'')==='member'){return false;}
        if (!$this->s->one("SELECT user_id FROM member_enrollments WHERE user_id=? AND kind='new' AND annual_opt_in=0",[$user])) { return false; }
        $roots=$this->roots();
        if (!$roots || $user===$roots[0]) { return false; }
        return (bool)$this->s->one("WITH RECURSIVE ancestors(id,sponsor_id,path,depth) AS (
            SELECT id,sponsor_id,printf(',%d,',id),0 FROM users WHERE id=? AND active=1
            UNION ALL SELECT u.id,u.sponsor_id,a.path||u.id||',',a.depth+1 FROM users u JOIN ancestors a ON u.id=a.sponsor_id
            WHERE a.depth<100 AND instr(a.path,','||u.id||',')=0
        ) SELECT id FROM ancestors WHERE id=? AND depth>0 LIMIT 1",[$user,$roots[0]]);
    }
    public function status(int $user, ?string $now = null): array
    {
        $u = $this->s->one('SELECT id,role,active FROM users WHERE id=?', [$user]);
        if (!$u) { throw new RuntimeException('ไม่พบผู้ใช้'); }
        $subscription = $this->ensure($user);
        $now ??= Store::now();
        $type = $this->isDownline($user) ? 'downline' : 'external';
        if (!$u['active']) { $state = 'locked'; }
        elseif ($u['role'] === 'admin') { $state = 'admin'; }
        elseif ($subscription['manual_lock']) { $state = 'locked'; }
        elseif ($u['role'] === 'member') { $state = 'customer'; }
        elseif ($type==='downline' && $u['role']==='downline_team') { $state = 'free'; }
        elseif ($subscription['paid_until'] && $subscription['paid_until'] > $now) { $state = 'paid'; }
        elseif ($subscription['trial_ends_at'] > $now && !$subscription['paid_until']) { $state = 'trial'; }
        else { $state = 'expired'; }
        return $subscription + ['type' => $type, 'state' => $state, 'allowed' => in_array($state, ['admin','free','paid','trial','customer'], true),
            'access_until' => $state === 'trial' ? $subscription['trial_ends_at'] : $subscription['paid_until']];
    }
    public function queueExpirations(): int
    {
        (new RegistrationPlan($this->s))->refreshDue();
        $count = 0;
        foreach ($this->s->all("SELECT id,email FROM users WHERE active=1 AND role NOT IN ('admin','member')") as $user) {
            $state = $this->status($user['id']);
            if ($state['state'] !== 'expired') { continue; }
            $end = $state['paid_until'] ?: $state['trial_ends_at'];
            $renewalText=(new RegistrationPlan($this->s))->annual($user['id'])
                ? 'แผนที่คุณเลือกไว้: Packet 990 บาท/ปี กรุณาเปิดหน้าต่ออายุเพื่อชำระเงินและยืนยันสลิป'
                : ($this->isDownline($user['id']) ? 'สมาชิกใหม่ใต้สายงาน 0005: ซื้อสินค้าอย่างน้อย 100 PV ต่อคำสั่งซื้อและยืนยันชำระเงิน หรือชำระค่า Platform 990 บาท/ปี' : 'ต่ออายุ Platform 990 บาท/ปี เงื่อนไขสมาชิกใต้สายงานสงวนสำหรับสมาชิกใหม่ภายใต้สายงาน 0005');
            $count += $this->s->exec('INSERT OR IGNORE INTO platform_emails(user_id,dedupe,recipient,subject,body,created_at) VALUES(?,?,?,?,?,?)',
                [$user['id'], 'expired:'.$user['id'].':'.$end, $user['email'], 'SkyNext: สิทธิ์ใช้งาน Platform หมดอายุ',
                "สิทธิ์ใช้งาน Platform ของคุณหมดอายุแล้ว ระบบจัดการถูกล็อกชั่วคราว\n".$renewalText."\n".site_url('platform/billing')."\nบัญชีและข้อมูลของคุณยังคงอยู่", Store::now()]);
        }
        return $count;
    }
    /** Called inside the existing payment transaction, never on checkout/slip upload. */
    public function qualifyPurchase(int $order): void
    {
        $o=$this->s->one("SELECT * FROM orders WHERE id=? AND status IN ('paid','processing','shipped','completed') AND total_satang>0",[$order]);
        if ($o && !$this->isDownline((int)$o['user_id'])) { return; }
        if (!$o || !$this->s->one("SELECT id FROM payments WHERE order_id=? AND status='approved' AND amount_satang=?",[$order,$o['total_satang']]) || !$this->s->one('SELECT id FROM order_items WHERE order_id=? AND quantity>0',[$order])) { return; }
        $pv=(int)($this->s->one('SELECT COALESCE(SUM(pv*quantity),0) total FROM order_items WHERE order_id=?',[$order])['total']??0);
        if ($pv<100) { return; }
        if ($this->s->exec("UPDATE users SET role='downline_team' WHERE id=? AND role IN ('affiliate','member','external_team')",[$o['user_id']])) {
            $this->s->exec("UPDATE platform_invoices SET status='expired' WHERE user_id=? AND status='pending'",[$o['user_id']]);
            $this->s->audit(null,'purchase_team_qualified','users',$o['user_id'],'order='.$o['number']);
        }
    }
    public function qualifyAnnual(int $user): void
    {
        if ($this->s->exec("UPDATE users SET role='external_team' WHERE id=? AND role='affiliate'",[$user])) {
            $this->s->audit(null,'annual_team_qualified','users',$user);
        }
    }
    public function saveRoots(array $ids, int $actor): void
    {
        $ids=array_values(array_unique(array_map('intval',$ids)));
        if ($ids!==$this->roots()) { throw new RuntimeException('ต้นสายสมาชิกใหม่กำหนดเป็น Affiliate 0005 เท่านั้น'); }
    }
    public function setLock(int $user, bool $locked, int $actor): void
    {
        $u = $this->s->one('SELECT role FROM users WHERE id=?', [$user]);
        if (!$u || $u['role'] === 'admin') { throw new RuntimeException('ไม่สามารถล็อกผู้ดูแลระบบ'); }
        $this->ensure($user);
        $this->s->exec('UPDATE platform_subscriptions SET manual_lock=? WHERE user_id=?', [(int)$locked, $user]);
        $this->s->audit($actor, $locked ? 'platform_locked' : 'platform_lock_cleared', 'users', $user);
    }
    public function extendTrial(int $user, int $days, int $actor): array
    {
        if ($days < 1 || $days > 3650) { throw new RuntimeException('จำนวนวันที่ขยายต้องอยู่ระหว่าง 1 ถึง 3650 วัน'); }
        return $this->s->transaction(function () use ($user, $days, $actor): array {
            $account = $this->s->one('SELECT role FROM users WHERE id=? AND active=1', [$user]);
            if (!$account || in_array($account['role'], ['admin', 'member'], true)) {
                throw new RuntimeException('บัญชีนี้ไม่สามารถขยายเวลาทดลองใช้ได้');
            }
            $state = $this->status($user);
            if (!in_array($state['state'], ['trial', 'expired'], true) || $state['manual_lock'] || !empty($state['paid_until'])) {
                throw new RuntimeException('ขยายได้เฉพาะบัญชีทดลองใช้หรือหมดทดลองที่ยังไม่ชำระรายปีและไม่ถูกระงับ');
            }
            $now = self::utc(Store::now());
            $currentEnd = self::utc($state['trial_ends_at']);
            $newEnd = ($currentEnd > $now ? $currentEnd : $now)->modify('+'.$days.' days')->format('Y-m-d H:i:s');
            $this->s->exec('UPDATE platform_subscriptions SET trial_ends_at=? WHERE user_id=?', [$newEnd, $user]);
            $this->s->exec("UPDATE platform_renewal_orders SET due_at=?,status='scheduled' WHERE user_id=? AND status IN ('scheduled','due') AND invoice_id IS NULL",
                [$newEnd, $user]);
            $this->s->audit($actor, 'platform_trial_extended', 'users', $user, 'days='.$days.'; trial_ends_at='.$newEnd);
            $updated = $this->s->one('SELECT * FROM platform_subscriptions WHERE user_id=?', [$user]);
            if (!$updated) { throw new RuntimeException('ไม่สามารถบันทึกวันสิ้นสุดทดลองใช้ได้'); }
            return $updated;
        });
    }
    public static function label(string $state): string
    {
        return ['customer'=>'Member · สั่งซื้อสินค้า','admin'=>'ผู้ดูแลระบบ','free'=>'ใช้ฟรีใต้สายงาน','paid'=>'ชำระรายปีแล้ว','trial'=>'ทดลองใช้ 30 วัน','expired'=>'หมดอายุ / ล็อกระบบ','locked'=>'ระงับการใช้ Platform'][$state] ?? $state;
    }
}
