<?php
namespace App\Libraries\Platform;

use RuntimeException;

/** Original SKYNEXT membership number is separate from the immutable website Affiliate ID. */
class RegistrationPlan
{
    public function __construct(private Store $s) {}
    public static function validate(array $input): array
    {
        $kind=(string)($input['membership_kind']??'new');
        if(!in_array($kind,['new','existing','customer'],true)){throw new RuntimeException('เลือกประเภทสมาชิกให้ถูกต้อง');}
        if($kind==='customer'){return ['kind'=>'new','code'=>null,'opt_in'=>0,'role'=>'member'];}
        if($kind==='new'){return ['kind'=>'new','code'=>null,'opt_in'=>0];}
        $code=strtoupper(trim((string)($input['skynext_member_code']??'')));
        if(!preg_match('~^[A-Z0-9][A-Z0-9/_-]{0,39}$~D',$code)){throw new RuntimeException('กรุณากรอกรหัสสมาชิก SKYNEXT เดิม ไม่เกิน 40 ตัวอักษร (ตัวอักษรอังกฤษ ตัวเลข / - _)');}
        if(($input['annual_opt_in']??'')!=='1'){throw new RuntimeException('กรุณายืนยันความประสงค์ทดลองใช้ 30 วัน และต่ออายุ Packet 990 บาท/ปี');}
        return ['kind'=>'existing','code'=>$code,'opt_in'=>1];
    }
    public function attach(int $user,array $plan): void
    {
        if($plan['code'] && $this->s->one('SELECT user_id FROM member_enrollments WHERE skynext_member_code=?',[$plan['code']])){throw new RuntimeException('รหัสสมาชิก SKYNEXT นี้ผูกกับบัญชีอื่นแล้ว กรุณาเข้าสู่ระบบบัญชีเดิมหรือติดต่อผู้ดูแล');}
        $now=Store::now();$this->s->insert('member_enrollments',['user_id'=>$user,'kind'=>$plan['kind'],'skynext_member_code'=>$plan['code'],'annual_opt_in'=>$plan['opt_in'],'accepted_at'=>$plan['opt_in']?$now:null,'created_at'=>$now]);
        if($plan['opt_in']){$state=(new Membership($this->s))->ensure($user);$this->s->insert('platform_renewal_orders',['user_id'=>$user,'amount_satang'=>(new AnnualWebsiteOffer($this->s))->current()['amount_satang'],'due_at'=>$state['trial_ends_at'],'created_at'=>$now]);}
    }
    public function annual(int $user): bool
    { return (bool)$this->s->one("SELECT user_id FROM member_enrollments WHERE user_id=? AND kind='existing' AND annual_opt_in=1",[$user]); }
    public function refreshDue(?int $user=null,?string $now=null): void
    { $sql="UPDATE platform_renewal_orders SET status='due' WHERE status='scheduled' AND due_at<=?";$params=[$now??Store::now()];if($user!==null){$sql.=' AND user_id=?';$params[]=$user;}$this->s->exec($sql,$params); }
    public function details(int $user): ?array
    { $this->refreshDue($user);$row=$this->s->one('SELECT * FROM member_enrollments WHERE user_id=?',[$user]);if(!$row){return null;}$row['renewal']=$this->s->one('SELECT * FROM platform_renewal_orders WHERE user_id=? ORDER BY id DESC LIMIT 1',[$user]);return $row; }
    public function bindInvoice(int $user,int $invoice): void
    {
        $this->refreshDue($user);
        $this->s->exec("UPDATE platform_renewal_orders SET status='awaiting_payment',invoice_id=?,amount_satang=(SELECT amount_satang FROM platform_invoices WHERE id=?) WHERE user_id=? AND status IN ('due','awaiting_payment')",[$invoice,$invoice,$user]);
    }
    public function paid(int $invoice): void
    { $this->s->exec("UPDATE platform_renewal_orders SET status='paid',paid_at=? WHERE invoice_id=? AND status='awaiting_payment'",[Store::now(),$invoice]); }
}
