<?php
namespace Tests\Platform;

use App\Libraries\Platform\{Store,Commerce,Access,Membership,PlatformBilling,PlatformMailer,SlipVerifier,PromptPay};
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MembershipTest extends TestCase
{
    private Store $s;
    private Membership $m;
    private int $root;
    private int $child;
    private int $outside;
    protected function setUp(): void
    {
        $this->s=new Store(':memory:');$this->s->install();$this->m=new Membership($this->s);
        $c=new Commerce($this->s);
        for($i=1;$i<=4;$i++){$c->register('Seed '.$i,'seed'.$i.'@example.test','Long-Password-123!');}
        $this->root=$c->register('Root user','root@example.test','Long-Password-123!');
        $code=$this->s->one('SELECT referral_code FROM users WHERE id=?',[$this->root])['referral_code'];
        $this->child=$c->register('Child user','child@example.test','Long-Password-123!',$code);
        $this->outside=$c->register('Other user','other@example.test','Long-Password-123!');
    }
    private function configure(): void
    {
        foreach(['platform_promptpay'=>'0812345678','platform_slip_provider'=>'easyslip','easyslip_api_key'=>'fixture-test-key','slip_receiver_bank'=>'004','slip_receiver_account'=>'1234567890'] as $key=>$value){$this->s->set($key,$value);}
    }
    private function expire(int $user): void
    {
        $this->s->exec("UPDATE users SET created_at='2020-01-01 00:00:00' WHERE id=?",[$user]);
        $this->s->exec('DELETE FROM platform_subscriptions WHERE user_id=?',[$user]);
        $this->m->ensure($user);
    }
    private function response(string $reference='TX12345678', int $amount=990): array
    {
        return ['success'=>true,'data'=>['isDuplicate'=>false,'isAmountMatched'=>true,'amountInSlip'=>$amount,
            'matchedAccount'=>['bank'=>['code'=>'004'],'bankNumber'=>'1234567890'],
            'rawSlip'=>['transRef'=>$reference,'date'=>gmdate('Y-m-d\TH:i:s\Z'),'countryCode'=>'TH',
                'amount'=>['amount'=>$amount,'local'=>['currency'=>'THB']],'sender'=>['bank'=>['id'=>'014']],'receiver'=>['bank'=>['id'=>'004']]]]];
    }
    private function billing(array $response): PlatformBilling
    {
        $fake=new class($response) extends SlipVerifier {
            public function __construct(private array $response) {}
            public function verify(string $provider,string $path,string $mime,array $order): array
            {return $this->validate($provider,$this->response,$order,'004','1234567890');}
        };
        return new PlatformBilling($this->s,$fake);
    }
    private function slip(PlatformBilling $billing,int $invoice,int $user): int
    {
        return $billing->submit($invoice,$user,['slip'=>bin2hex(random_bytes(16)).'.png','mime'=>'image/png','slip_hash'=>bin2hex(random_bytes(32))]);
    }
    public function testConfiguredRootsIncludeAllDescendantsButNotOtherLines(): void
    {
        $code=$this->s->one('SELECT referral_code FROM users WHERE id=?',[$this->child])['referral_code'];
        $grandchild=(new Commerce($this->s))->register('Grandchild','grandchild@example.test','Long-Password-123!',$code);
        $this->expire($this->root);$this->expire($this->child);$this->expire($grandchild);$this->expire($this->outside);
        self::assertSame('expired',$this->m->status($this->child)['state']);
        $this->m->saveRoots([$this->root],$this->root);
        self::assertFalse($this->m->isDownline($this->root));
        foreach([$this->child,$grandchild] as $id){self::assertTrue($this->m->isDownline($id));self::assertSame('expired',$this->m->status($id)['state']);}
        self::assertSame('expired',$this->m->status($this->outside)['state']);
        self::assertFalse(Access::can($this->s->one('SELECT * FROM users WHERE id=?',[$this->child]),'users',$this->s));
    }
    public function testTrialIsExactlyThirtyDaysAndExpiresAtBoundary(): void
    {
        $this->s->exec("UPDATE users SET created_at='2026-01-01 00:00:00' WHERE id=?",[$this->outside]);
        self::assertSame('trial',$this->m->status($this->outside,'2026-01-30 23:59:59')['state']);
        self::assertSame('expired',$this->m->status($this->outside,'2026-01-31 00:00:00')['state']);
        self::assertSame('2026-01-31 00:00:00',$this->m->ensure($this->outside)['trial_ends_at']);
    }
    public function testExpiryNotificationsAreDeduplicatedAndKeptWhenSmtpMissing(): void
    {
        $this->expire($this->outside);
        self::assertSame(1,$this->m->queueExpirations());self::assertSame(0,$this->m->queueExpirations());
        self::assertFalse((new PlatformMailer($this->s))->ready());self::assertSame(0,(new PlatformMailer($this->s))->work());
        self::assertSame('queued',$this->s->one('SELECT status FROM platform_emails')['status']);
    }
    public function testAdminIsExemptAndCannotBeLocked(): void
    {
        $this->expire($this->root);$this->s->exec("UPDATE users SET role='admin' WHERE id=?",[$this->root]);
        self::assertSame('admin',$this->m->status($this->root)['state']);
        $this->expectException(RuntimeException::class);$this->m->setLock($this->root,true,$this->root);
    }
    public function testManualLockOverridesFreeStatusAndUnlockRechecksPlan(): void
    {
        $this->s->exec("UPDATE users SET role='downline_team' WHERE id=?",[$this->child]);$this->m->setLock($this->child,true,$this->root);
        self::assertFalse($this->m->status($this->child)['allowed']);
        $this->m->setLock($this->child,false,$this->root);self::assertSame('free',$this->m->status($this->child)['state']);
    }
    public function testAdminCanExtendTrialAndScheduledRenewalTogether(): void
    {
        $this->s->exec("UPDATE member_enrollments SET kind='existing',annual_opt_in=1 WHERE user_id=?",[$this->outside]);
        $before=$this->m->status($this->outside);
        $this->s->insert('platform_renewal_orders',['user_id'=>$this->outside,'amount_satang'=>(new \App\Libraries\Platform\AnnualWebsiteOffer($this->s))->current()['amount_satang'],'due_at'=>$before['trial_ends_at'],'created_at'=>Store::now()]);
        $extended=$this->m->extendTrial($this->outside,30,$this->root);
        self::assertSame($before['trial_started_at'],$extended['trial_started_at']);
        self::assertSame(Membership::utc($before['trial_ends_at'])->modify('+30 days')->format('Y-m-d H:i:s'),$extended['trial_ends_at']);
        self::assertSame($extended['trial_ends_at'],$this->s->one('SELECT due_at FROM platform_renewal_orders WHERE user_id=?',[$this->outside])['due_at']);
        self::assertSame('trial',$this->m->status($this->outside)['state']);
        $this->s->exec("UPDATE platform_renewal_orders SET status='due',due_at='2020-01-01 00:00:00' WHERE user_id=?",[$this->outside]);
        $extended=$this->m->extendTrial($this->outside,5,$this->root);
        $renewal=$this->s->one('SELECT due_at,status FROM platform_renewal_orders WHERE user_id=?',[$this->outside]);
        self::assertSame($extended['trial_ends_at'],$renewal['due_at']);
        self::assertSame('scheduled',$renewal['status']);
    }
    public function testExpiredTrialCanBeExtendedFromCurrentTimeButPaidAndLockedUsersCannot(): void
    {
        $this->expire($this->outside);
        $extended=$this->m->extendTrial($this->outside,30,$this->root);
        self::assertGreaterThan(Store::now(),$extended['trial_ends_at']);
        self::assertSame('trial',$this->m->status($this->outside)['state']);
        $this->s->exec('UPDATE platform_subscriptions SET paid_until=? WHERE user_id=?',[Store::now(),$this->outside]);
        try {
            $this->m->extendTrial($this->outside,10,$this->root);
            self::fail('Paid subscriptions must not be extended as trials');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('ขยายได้เฉพาะบัญชีทดลองใช้',$e->getMessage());
        }
    }
    public function testValidBankProofActivatesOneYearAndExternalTeamOnlyOnce(): void
    {
        $this->configure();$this->expire($this->outside);$billing=$this->billing($this->response());
        $invoice=$billing->invoice($this->outside);self::assertSame($invoice['id'],$billing->invoice($this->outside)['id']);
        $slip=$this->slip($billing,$invoice['id'],$this->outside);self::assertFalse($this->m->status($this->outside)['allowed']);
        self::assertSame('verified',$billing->check($slip));$state=$this->m->status($this->outside);
        self::assertSame('paid',$state['state']);self::assertTrue($state['allowed']);
        self::assertSame(Membership::utc(Store::now())->modify('+1 year')->format('Y-m-d'),substr($state['paid_until'],0,10));
        self::assertSame('unchanged',$billing->check($slip));self::assertSame($state['paid_until'],$this->m->status($this->outside)['paid_until']);
        self::assertSame('external_team',$this->s->one('SELECT role FROM users WHERE id=?',[$this->outside])['role']);
    }
    public function testVerifiedCatalogPricePaymentGrantsOneYearAtExactInvoiceAmount():void
    {
        $this->configure();$this->expire($this->outside);$this->s->exec("UPDATE users SET role='admin' WHERE id=?",[$this->root]);
        $product=\App\Libraries\Platform\AnnualWebsitePackage::add($this->s);$this->s->exec('UPDATE products SET price_satang=72500 WHERE id=?',[$product]);
        $billing=$this->billing($this->response('CATALOG-PRICE-VERIFIED',725));$invoice=$billing->invoice($this->outside);
        self::assertSame(72500,(int)$invoice['amount_satang']);self::assertSame('verified',$billing->check($this->slip($billing,$invoice['id'],$this->outside)));
        self::assertSame('paid',$this->m->status($this->outside)['state']);self::assertSame('external_team',$this->s->one('SELECT role FROM users WHERE id=?',[$this->outside])['role']);
    }
    public function testWrongAmountNeverUnlocks(): void
    {
        $this->configure();$this->expire($this->outside);$billing=$this->billing($this->response('TXBAD123',989));
        $invoice=$billing->invoice($this->outside);$slip=$this->slip($billing,$invoice['id'],$this->outside);
        self::assertSame('review',$billing->check($slip));self::assertSame('expired',$this->m->status($this->outside)['state']);
        self::assertNull($this->s->one('SELECT * FROM platform_transfers'));
    }
    public function testDuplicateTransactionOnAnotherInvoiceNeverUnlocks(): void
    {
        $this->configure();$billing=$this->billing($this->response());$this->expire($this->outside);$this->expire($this->child);
        $first=$billing->invoice($this->outside);self::assertSame('verified',$billing->check($this->slip($billing,$first['id'],$this->outside)));
        $second=$billing->invoice($this->child);self::assertSame('review',$billing->check($this->slip($billing,$second['id'],$this->child)));
        self::assertFalse($this->m->status($this->child)['allowed']);
    }
    public function testCannotSubmitToAnotherUsersInvoice(): void
    {
        $this->configure();$billing=$this->billing($this->response());$invoice=$billing->invoice($this->outside);
        $this->expectException(RuntimeException::class);$this->slip($billing,$invoice['id'],$this->child);
    }
    public function testMissingPaymentConfigurationCannotCreatePayableInvoice(): void
    {
        $billing=new PlatformBilling($this->s);self::assertFalse($billing->ready());
        $this->expectException(RuntimeException::class);$billing->invoice($this->outside);
    }
    public function testExistingMemberAnnualOrderIsPaidOnlyAfterVerifiedSlip(): void
    {
        $id=(new Commerce($this->s))->register('Existing member','annual-existing@example.test','Long-Password-123!','','',['membership_kind'=>'existing','skynext_member_code'=>'SKN-990','annual_opt_in'=>'1']);
        $this->expire($id);$this->configure();
        $this->s->exec("UPDATE platform_renewal_orders SET due_at='2020-01-31 00:00:00' WHERE user_id=?",[$id]);
        $billing=$this->billing($this->response('EXISTING-MEMBER-990'));
        $invoice=$billing->invoice($id);$slip=$this->slip($billing,$invoice['id'],$id);
        $plan=new \App\Libraries\Platform\RegistrationPlan($this->s);
        self::assertSame('awaiting_payment',$plan->details($id)['renewal']['status']);
        self::assertSame('expired',$this->m->status($id)['state']);
        self::assertSame('verified',$billing->check($slip));
        self::assertSame('paid',$plan->details($id)['renewal']['status']);
        self::assertSame('paid',$this->m->status($id)['state']);
        self::assertSame('external_team',$this->s->one('SELECT role FROM users WHERE id=?',[$id])['role']);
        self::assertSame('unchanged',$billing->check($slip));
    }
    public function testPromptPayPayloadHasAmountAndCrcAndRejectsInvalidTargets(): void
    {
        $qr=PromptPay::payload('0812345678',99000);
        self::assertStringContainsString('0016A00000067701011101130066812345678',$qr);
        self::assertStringContainsString('5406990.00',$qr);
        self::assertMatchesRegularExpression('/6304[A-F0-9]{4}$/',$qr);
        $this->expectException(RuntimeException::class);PromptPay::payload('arbitrary target',99000);
    }
}
