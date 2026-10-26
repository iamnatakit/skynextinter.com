<?php
namespace Tests\Platform;
use App\Libraries\Platform\{Store,Commerce,PendingReferrals,Membership,Access};
use PHPUnit\Framework\TestCase;
use RuntimeException;
final class PendingReferralsTest extends TestCase
{
    private Store $s;
    private Commerce $c;
    private PendingReferrals $queue;
    private int $admin;
    protected function setUp(): void
    {
        $this->s=new Store(':memory:');$this->s->install();$this->c=new Commerce($this->s);$this->queue=new PendingReferrals($this->s);
        $this->admin=$this->register('admin');$this->s->exec("UPDATE users SET role='admin' WHERE id=?",[$this->admin]);
        for($i=2;$i<=5;$i++){$this->register('root'.$i);}
    }
    private function register(string $name,string $ref=''):int {return $this->c->register('Fixture '.$name,$name.'@example.test','Long-Password-123!',$ref);}
    public function testDirectSignupIsQueuedWithoutConsumingAnotherAffiliateId():void
    {
        $member=$this->register('direct');$row=$this->s->one('SELECT referral_code,sponsor_id FROM users WHERE id=?',[$member]);
        self::assertSame('0006',$row['referral_code']);self::assertNull($row['sponsor_id']);self::assertTrue($this->queue->pending($member));
        self::assertSame('00000',$this->s->one('SELECT group_code FROM pending_referrals WHERE user_id=?',[$member])['group_code']);
        self::assertFalse((new Membership($this->s))->isDownline($member));
        self::assertNull($this->s->one("SELECT id FROM users WHERE referral_code='00000'"));
        self::assertSame('0007',$this->s->one('SELECT referral_code FROM users WHERE id=?',[$this->register('next')])['referral_code']);
    }
    public function testReferralSignupRetainsSponsorAndNeverEntersQueue():void
    {
        $member=$this->register('referred','0005');self::assertFalse($this->queue->pending($member));
        self::assertSame(5,(int)$this->s->one('SELECT sponsor_id FROM users WHERE id=?',[$member])['sponsor_id']);
        self::assertTrue((new Membership($this->s))->isDownline($member));
    }
    public function testAdminAssignmentPreservesAffiliateAndCreatesAuditWithoutGrantingFreeRole():void
    {
        $member=$this->register('waiting');$this->queue->assign($member,'0005',$this->admin);
        self::assertFalse($this->queue->pending($member));$row=$this->s->one('SELECT * FROM users WHERE id=?',[$member]);
        self::assertSame('0006',$row['referral_code']);self::assertSame(5,(int)$row['sponsor_id']);self::assertSame('affiliate',$row['role']);
        self::assertTrue((new Membership($this->s))->isDownline($member));self::assertSame('trial',(new Membership($this->s))->status($member)['state']);
        self::assertNotNull($this->s->one("SELECT id FROM audit WHERE action='pending_referral_assigned'"));
        $this->expectException(RuntimeException::class);$this->queue->assign($member,'0002',$this->admin);
    }
    public function testNonAdminCannotAssignEvenWhenPostingAnotherMember():void
    {
        $member=$this->register('waiting');
        try{$this->queue->assign($member,'0005',2);self::fail('Non-admin assigned sponsor');}catch(RuntimeException $e){self::assertTrue($this->queue->pending($member));}
        foreach(['affiliate','manager','sales','external_team','downline_team'] as $role){self::assertFalse(Access::can(['active'=>1,'role'=>$role],'referral_assignments',$this->s));}
    }
    public function testSelfDescendantInactiveAndUnknownSponsorsAreRejectedAtomically():void
    {
        $member=$this->register('waiting');$descendant=$this->register('child','0006');
        $this->s->exec('UPDATE users SET active=0 WHERE id=2');
        foreach(['0006','0007','0002','00000','9999'] as $code){
            try{$this->queue->assign($member,$code,$this->admin);self::fail('Unsafe sponsor accepted');}catch(RuntimeException $e){self::assertTrue($this->queue->pending($member));self::assertNull($this->s->one('SELECT sponsor_id FROM users WHERE id=?',[$member])['sponsor_id']);}
        }
    }
    public function testExistingEnrollmentAssignedTo0005StillDoesNotQualifyAsNewMember():void
    {
        $member=$this->c->register('Existing member','existing@example.test','Long-Password-123!','','',['membership_kind'=>'existing','skynext_member_code'=>'OLD-123','annual_opt_in'=>'1']);
        $this->queue->assign($member,'0005',$this->admin);self::assertFalse((new Membership($this->s))->isDownline($member));
    }
}
