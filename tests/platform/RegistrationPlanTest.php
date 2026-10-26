<?php
namespace Tests\Platform;

use App\Libraries\Platform\{Store,Commerce,RegistrationPlan,Membership,PlatformBilling};
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RegistrationPlanTest extends TestCase
{
    private Store $s;
    private Commerce $c;
    protected function setUp(): void
    { $this->s=new Store(':memory:');$this->s->install();$this->c=new Commerce($this->s); }
    private function register(string $email='existing@example.test',array $overrides=[]): int
    { return $this->c->register('Existing member',$email,'Long-Password-123!','','',array_replace(['membership_kind'=>'existing','skynext_member_code'=>'SKN-1234','annual_opt_in'=>'1'],$overrides)); }
    public function testNewMemberHasNoAnnualCommitment(): void
    {
        $id=$this->c->register('New member','new@example.test','Long-Password-123!');
        $plan=(new RegistrationPlan($this->s))->details($id);
        self::assertSame('new',$plan['kind']);self::assertNull($plan['renewal']);self::assertSame(0,$plan['annual_opt_in']);
    }
    public function testExistingMemberRequiresCodeAndConsentWithoutConsumingAffiliateId(): void
    {
        foreach([['skynext_member_code'=>''],['annual_opt_in'=>''],['membership_kind'=>'invalid'],['skynext_member_code'=>'<script>']] as $input){
            try{$this->register('invalid@example.test',$input);self::fail('Invalid enrollment accepted');}catch(RuntimeException $e){self::assertNotEmpty($e->getMessage());}
        }
        self::assertSame(0,(int)$this->s->one('SELECT COUNT(*) n FROM users')['n']);
        $id=$this->register();self::assertSame('0001',$this->s->one('SELECT referral_code FROM users WHERE id=?',[$id])['referral_code']);
    }
    public function testEnrollmentStoresSeparateOriginalCodeAndThirtyDayOrder(): void
    {
        $id=$this->register();$p=(new RegistrationPlan($this->s))->details($id);$m=(new Membership($this->s))->ensure($id);
        self::assertSame('SKN-1234',$p['skynext_member_code']);self::assertSame(1,$p['annual_opt_in']);self::assertNotEmpty($p['accepted_at']);
        self::assertSame('scheduled',$p['renewal']['status']);self::assertSame(99000,$p['renewal']['amount_satang']);self::assertSame('PACKET-990-ANNUAL',$p['renewal']['package_code']);
        self::assertSame($m['trial_ends_at'],$p['renewal']['due_at']);
        self::assertSame(30*86400,strtotime($m['trial_ends_at'].' UTC')-strtotime($m['trial_started_at'].' UTC'));
        self::assertSame('trial',(new Membership($this->s))->status($id)['state']);
    }
    public function testDuplicateOriginalCodeRollsBackUserAndAffiliateSequence(): void
    {
        $this->register();
        try{$this->register('duplicate@example.test',['skynext_member_code'=>'skn-1234']);self::fail('Duplicate accepted');}catch(RuntimeException $e){self::assertStringContainsString('ผูกกับบัญชีอื่น',$e->getMessage());}
        $next=$this->register('other@example.test',['skynext_member_code'=>'SKN-5678']);
        self::assertSame('0002',$this->s->one('SELECT referral_code FROM users WHERE id=?',[$next])['referral_code']);
        self::assertSame(2,(int)$this->s->one('SELECT COUNT(*) n FROM member_enrollments')['n']);
    }
    public function testDueBoundaryIsIdempotentAndScopedToOwner(): void
    {
        $a=$this->register();$b=$this->register('b@example.test',['skynext_member_code'=>'SKN-B']);$r=new RegistrationPlan($this->s);
        $due=$r->details($a)['renewal']['due_at'];
        $r->refreshDue($a,Membership::utc($due)->modify('-1 second')->format('Y-m-d H:i:s'));
        self::assertSame('scheduled',$this->s->one('SELECT status FROM platform_renewal_orders WHERE user_id=?',[$a])['status']);
        $r->refreshDue($a,$due);$r->refreshDue($a,$due);
        self::assertSame('due',$this->s->one('SELECT status FROM platform_renewal_orders WHERE user_id=?',[$a])['status']);
        self::assertSame('scheduled',$this->s->one('SELECT status FROM platform_renewal_orders WHERE user_id=?',[$b])['status']);
    }
    public function testBillingWaitsUntilTrialEndAndBindsSameRenewal(): void
    {
        $id=$this->register();
        foreach(['platform_promptpay'=>'0812345678','platform_slip_provider'=>'easyslip','easyslip_api_key'=>'fixture-test-key','slip_receiver_bank'=>'004','slip_receiver_account'=>'1234567890'] as $key=>$value){$this->s->set($key,$value);}
        $billing=new PlatformBilling($this->s);
        try{$billing->invoice($id);self::fail('Early invoice accepted');}catch(RuntimeException $e){self::assertStringContainsString('ครบ 30 วัน',$e->getMessage());}
        $this->s->exec("UPDATE platform_subscriptions SET trial_ends_at='2020-01-31 00:00:00' WHERE user_id=?",[$id]);
        $this->s->exec("UPDATE platform_renewal_orders SET due_at='2020-01-31 00:00:00' WHERE user_id=?",[$id]);
        $invoice=$billing->invoice($id);self::assertSame($invoice['id'],$billing->invoice($id)['id']);
        $r=new RegistrationPlan($this->s);$p=$r->details($id);
        self::assertSame('awaiting_payment',$p['renewal']['status']);self::assertSame($invoice['id'],$p['renewal']['invoice_id']);
        $r->paid($invoice['id']);self::assertSame('paid',$r->details($id)['renewal']['status']);
        self::assertNotEmpty($r->details($id)['renewal']['paid_at']);
    }
    public function testAnnualEnrollmentKeepsSelectedPlanEvenInsideFreeLine(): void
    {
        $id=$this->register();$this->s->exec("UPDATE users SET role='downline_team' WHERE id=?",[$id]);
        self::assertSame('trial',(new Membership($this->s))->status($id)['state']);
        self::assertSame('expired',(new Membership($this->s))->status($id,'2100-01-01 00:00:00')['state']);
    }
}
