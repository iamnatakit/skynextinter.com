<?php
namespace Tests\Platform;

use App\Libraries\Platform\{Store,Commerce,Access,Membership};
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TeamRolesTest extends TestCase
{
    private Store $s;
    private Commerce $c;
    private int $buyer;
    private int $staff;
    private int $product;
    protected function setUp(): void
    {
        $this->s=new Store(':memory:');$this->s->install();$this->c=new Commerce($this->s);
        for($i=1;$i<=5;$i++){$this->c->register('Root '.$i,'root'.$i.'@example.test','Long-Password-123!');}
        $this->buyer=$this->c->register('Buyer','buyer@example.test','Long-Password-123!','0005');
        $this->staff=$this->c->register('Staff','staff@example.test','Long-Password-123!');
        $this->s->exec("UPDATE users SET role='downline_team' WHERE id=?",[$this->staff]);
        $this->product=$this->s->insert('products',['sku'=>'PACKET-TEST','name'=>'Test Packet','category'=>'Packet','price_satang'=>10000,'pv'=>100,'stock'=>10,'active'=>1,'created_at'=>Store::now()]);
    }
    private function order(int $quantity=1): int
    { return $this->c->checkout($this->buyer,[$this->product=>$quantity],'',['recipient'=>'Test buyer','phone'=>'0812345678','address'=>'Test address'],bin2hex(random_bytes(16))); }
    private function payment(int $order): int
    { return $this->c->submitPayment($order,$this->buyer,['amount_satang'=>(int)$this->s->one('SELECT total_satang FROM orders WHERE id=?',[$order])['total_satang'],'reference'=>'fixture','slip'=>'fixture.png','slip_hash'=>hash('sha256','order-'.$order),'mime'=>'image/png']); }
    private function role(): string {return $this->s->one('SELECT role FROM users WHERE id=?',[$this->buyer])['role'];}
    public function testBothTeamsHaveAllBusinessModulesAndNoAdminModules(): void
    {
        foreach(['downline_team','external_team'] as $role){
            $user=['role'=>$role,'active'=>1];
            foreach(array_keys(Access::MODULES) as $module){self::assertSame(!in_array($module,Access::ADMIN_ONLY,true),Access::can($user,$module,$this->s),$role.':'.$module);}
        }
    }
    public function testConfirmedPacketPurchasePromotesAndRestoresExpiredAffiliate(): void
    {
        $this->s->exec("UPDATE users SET created_at='2020-01-01 00:00:00' WHERE id=?",[$this->buyer]);
        $m=new Membership($this->s);self::assertFalse($m->status($this->buyer)['allowed']);
        $order=$this->order();$payment=$this->payment($order);
        self::assertSame('affiliate',$this->role());
        $this->c->reviewPayment($payment,$this->staff,true,'Confirmed bank transfer');
        self::assertSame('downline_team',$this->role());self::assertSame('free',$m->status($this->buyer)['state']);
        self::assertTrue(Access::can($this->s->one('SELECT * FROM users WHERE id=?',[$this->buyer]),'products',$this->s));
    }
    public function testOutsideLineExistingMemberAndRootCannotQualifyEvenWithConfirmed100PV(): void
    {
        $root=(int)$this->s->one("SELECT id FROM users WHERE referral_code='0005'")['id'];
        $outside=$this->c->register('Outside','outside@example.test','Long-Password-123!','0001');
        $existing=$this->c->register('Existing','existing@example.test','Long-Password-123!','0005','',['membership_kind'=>'existing','skynext_member_code'=>'SKN-OLD','annual_opt_in'=>'1']);
        foreach([$root,$outside,$existing] as $id){
            $this->buyer=$id;$order=$this->order();$this->c->reviewPayment($this->payment($order),$this->staff,true,'Confirmed transfer');
            self::assertSame('affiliate',$this->role());self::assertFalse((new Membership($this->s))->isDownline($id));
        }
    }
    public function testIndirectNewMemberQualifiesButLegacyRoleAndConfigCannotBypassRule(): void
    {
        $code=$this->s->one('SELECT referral_code FROM users WHERE id=?',[$this->buyer])['referral_code'];
        $this->buyer=$this->c->register('Grandchild','grandchild@example.test','Long-Password-123!',$code);
        $m=new Membership($this->s);self::assertTrue($m->isDownline($this->buyer));
        $order=$this->order();$this->c->reviewPayment($this->payment($order),$this->staff,true,'Confirmed transfer');self::assertSame('free',$m->status($this->buyer)['state']);
        $this->s->set('platform_free_roots',json_encode([$this->staff]));
        self::assertFalse($m->isDownline($this->staff));self::assertSame('trial',$m->status($this->staff)['state']);
        $this->s->exec('DELETE FROM member_enrollments WHERE user_id=?',[$this->buyer]);self::assertFalse($m->isDownline($this->buyer));self::assertSame('trial',$m->status($this->buyer)['state']);
    }
    public function testUnpaidAndRejectedOrdersNeverPromote(): void
    {
        $order=$this->order();(new Membership($this->s))->qualifyPurchase($order);self::assertSame('affiliate',$this->role());
        $payment=$this->payment($order);$this->c->reviewPayment($payment,$this->staff,false,'Wrong transfer');self::assertSame('affiliate',$this->role());
    }
    public function testAnnualQualificationCannotChangeAdminOrDownlineRoles(): void
    {
        $m=new Membership($this->s);$m->qualifyAnnual($this->staff);
        self::assertSame('downline_team',$this->s->one('SELECT role FROM users WHERE id=?',[$this->staff])['role']);
        $this->s->exec("UPDATE users SET role='admin' WHERE id=?",[$this->buyer]);$m->qualifyAnnual($this->buyer);self::assertSame('admin',$this->role());
    }
    public function testExternalTeamPurchaseConvertsToFreeDownlineTeam(): void
    {
        $this->s->exec("UPDATE users SET role='external_team' WHERE id=?",[$this->buyer]);
        $order=$this->order();$this->c->reviewPayment($this->payment($order),$this->staff,true,'Confirmed transfer');self::assertSame('downline_team',$this->role());
    }
    public function testTeamCannotApproveOwnPurchase(): void
    {
        $this->s->exec("UPDATE users SET role='external_team' WHERE id=?",[$this->buyer]);$order=$this->order();$p=$this->payment($order);
        try{$this->c->reviewPayment($p,$this->buyer,true,'Own approval');self::fail('Self approval accepted');}catch(RuntimeException $e){self::assertSame('external_team',$this->role());self::assertSame('pending',$this->s->one('SELECT status FROM payments WHERE id=?',[$p])['status']);}
    }
    public function testTeamRoleDoesNotClearManualLockOrGiveAdmin(): void
    {
        $m=new Membership($this->s);$m->setLock($this->buyer,true,$this->staff);
        $order=$this->order();$this->c->reviewPayment($this->payment($order),$this->staff,true,'Confirmed transfer');
        self::assertSame('downline_team',$this->role());self::assertSame('locked',$m->status($this->buyer)['state']);
        self::assertFalse(Access::can($this->s->one('SELECT * FROM users WHERE id=?',[$this->buyer]),'users',$this->s));
    }
    public function testNinetyNinePVAndUnconfiguredPVNeverPromoteEvenForHighPrice(): void
    {
        foreach([99,0] as $pv){
            $this->s->exec('UPDATE products SET pv=?,price_satang=999000 WHERE id=?',[$pv,$this->product]);
            $order=$this->order();$this->c->reviewPayment($this->payment($order),$this->staff,true,'Confirmed transfer');
            self::assertSame('affiliate',$this->role());
        }
    }
    public function testQuantityCountsAndPVIsFrozenAtCheckout(): void
    {
        $this->s->exec('UPDATE products SET pv=50 WHERE id=?',[$this->product]);
        self::assertSame(100,$this->c->quote([$this->product=>2])['pv']);$order=$this->order(2);
        $this->s->exec('UPDATE products SET pv=0,price_satang=1 WHERE id=?',[$this->product]);
        $this->c->reviewPayment($this->payment($order),$this->staff,true,'Confirmed transfer');
        self::assertSame('downline_team',$this->role());
    }
    public function testPVChangesCannotRetroactivelyUpgradeOldLowPVOrder(): void
    {
        $this->s->exec('UPDATE products SET pv=0 WHERE id=?',[$this->product]);$order=$this->order();
        $this->s->exec('UPDATE products SET pv=100 WHERE id=?',[$this->product]);
        $this->c->reviewPayment($this->payment($order),$this->staff,true,'Confirmed transfer');self::assertSame('affiliate',$this->role());
    }
}
