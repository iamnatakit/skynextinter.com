<?php
namespace App\Libraries\Platform;
use RuntimeException;

/** 00000 is a holding group, never an issued Affiliate ID or a login account. */
class PendingReferrals
{
    public const GROUP='00000';
    public function __construct(private Store $s) {}
    public function pending(int $user): bool
    {
        return (bool)$this->s->one('SELECT p.user_id FROM pending_referrals p JOIN users u ON u.id=p.user_id WHERE p.user_id=? AND p.assigned_at IS NULL AND u.sponsor_id IS NULL',[$user]);
    }
    public function enqueue(int $user): void
    {
        if(!$this->s->one('SELECT id FROM users WHERE id=? AND sponsor_id IS NULL',[$user])){throw new RuntimeException('บัญชีนี้มีผู้แนะนำแล้ว');}
        $this->s->exec('INSERT OR IGNORE INTO pending_referrals(user_id,created_at) VALUES(?,?)',[$user,Store::now()]);
    }
    public function assign(int $member,string $code,int $actor): void
    {
        $this->s->transaction(function() use($member,$code,$actor){
            if(!$this->s->one("SELECT id FROM users WHERE id=? AND active=1 AND role='admin'",[$actor])){throw new RuntimeException('เฉพาะ Admin เท่านั้นที่กำหนดผู้แนะนำได้');}
            if(!$this->pending($member)){throw new RuntimeException('สมาชิกไม่ได้อยู่ในกลุ่ม 00000 หรือถูกกำหนดผู้แนะนำแล้ว');}
            if(!$this->s->one("SELECT id FROM users WHERE id=? AND active=1 AND role!='admin'",[$member])){throw new RuntimeException('เลือกสมาชิกที่ใช้งานและไม่ใช่ Admin');}
            $sponsor=$this->s->one('SELECT id FROM users WHERE referral_code=? AND active=1',[trim($code)]);
            if(!$sponsor){throw new RuntimeException('ไม่พบรหัสผู้แนะนำที่ใช้งาน');}
            $target=(int)$sponsor['id'];
            $cycle=$this->s->one('WITH RECURSIVE descendants(id) AS (SELECT id FROM users WHERE id=? UNION SELECT u.id FROM users u JOIN descendants d ON u.sponsor_id=d.id) SELECT id FROM descendants WHERE id=?',[$member,$target]);
            if($cycle){throw new RuntimeException('ห้ามเลือกตนเองหรือสมาชิกใต้สายงานตนเองเป็นผู้แนะนำ');}
            if(!$this->s->exec('UPDATE users SET sponsor_id=? WHERE id=? AND sponsor_id IS NULL',[$target,$member])){throw new RuntimeException('ข้อมูลผู้แนะนำเปลี่ยนแปลงแล้ว กรุณาโหลดหน้าใหม่');}
            $this->s->exec('UPDATE pending_referrals SET assigned_to=?,assigned_by=?,assigned_at=? WHERE user_id=? AND assigned_at IS NULL',[$target,$actor,Store::now(),$member]);
            $this->s->audit($actor,'pending_referral_assigned','users',$member,'from=00000; sponsor='.$code);
        });
    }
}
