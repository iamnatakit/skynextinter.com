<?php
namespace Tests\Platform;

use App\Libraries\Platform\{Store,Commerce,Admin,Access,Bot,Gateways};
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CommerceTest extends TestCase
{
    private Store $s;
    private Commerce $commerce;
    private int $user;
    private int $admin;
    private int $product;
    protected function setUp(): void
    {
        $this->s=new Store(':memory:');$this->s->install();$this->commerce=new Commerce($this->s);
        $this->user=$this->commerce->register('Test Buyer','buyer@example.test','Long-Password-123!');
        $this->admin=$this->commerce->register('Test Admin','admin@example.test','Long-Password-123!');
        $this->s->exec("UPDATE users SET role='admin' WHERE id=?",[$this->admin]);
        $this->product=$this->s->insert('products',['sku'=>'TEST','name'=>'Test Product','price_satang'=>10000,'stock'=>10,'active'=>1,'created_at'=>Store::now()]);
    }
    private function order(int $quantity=2,string $coupon='',?string $key=null): int
    {return $this->commerce->checkout($this->user,[$this->product=>$quantity],$coupon,['recipient'=>'Test Buyer','phone'=>'0812345678','address'=>'Test address 123'], $key??bin2hex(random_bytes(16)));}
    private function payment(int $order): int
    { $o=$this->s->one('SELECT * FROM orders WHERE id=?',[$order]);return $this->commerce->submitPayment($order,$this->user,['amount_satang'=>$o['total_satang'],'reference'=>'BANK-123','slip'=>'test.png','slip_hash'=>hash('sha256','test'.$order),'mime'=>'image/png']); }
    public function testCheckoutUsesServerPriceAndIsIdempotent(): void
    {
        $key=bin2hex(random_bytes(16));$id=$this->order(2,'',$key);$again=$this->order(2,'',$key);
        self::assertSame($id,$again);self::assertSame(20000,$this->s->one('SELECT total_satang FROM orders WHERE id=?',[$id])['total_satang']);
        self::assertSame(8,$this->s->one('SELECT stock FROM products')['stock']);self::assertCount(1,$this->s->all('SELECT id FROM orders'));
    }
    public function testStockFailureRollsBackAllWrites(): void
    {
        try{$this->order(11);self::fail('Should reject insufficient stock');}catch(RuntimeException $e){self::assertStringContainsString('สต็อก',$e->getMessage());}
        self::assertSame(10,$this->s->one('SELECT stock FROM products')['stock']);self::assertCount(0,$this->s->all('SELECT id FROM orders'));
    }
    public function testInactiveProductCannotBePurchased(): void
    {$this->s->exec('UPDATE products SET active=0');$this->expectException(RuntimeException::class);$this->order();}
    public function testUnpaidExpiryRestoresStockButPreservesSubmittedPayments(): void
    {
        $one=$this->order();$two=$this->order();$this->payment($two);
        $this->s->exec("UPDATE orders SET created_at='2020-01-01 00:00:00'");
        self::assertSame(1,$this->commerce->expireUnpaid());self::assertSame(8,$this->s->one('SELECT stock FROM products')['stock']);
        self::assertSame('payment_review',$this->s->one('SELECT status FROM orders WHERE id=?',[$two])['status']);
        self::assertSame(0,$this->commerce->expireUnpaid());
    }
    public function testPromotionUsageAndCancellationAreAtomic(): void
    {
        $this->s->insert('promotions',['code'=>'SAVE10','kind'=>'percent','value'=>1000,'minimum_satang'=>10000,'starts_at'=>'2020-01-01 00:00:00','ends_at'=>'2099-01-01 00:00:00','usage_limit'=>1]);
        $id=$this->order(2,'SAVE10');self::assertSame(18000,$this->s->one('SELECT total_satang FROM orders')['total_satang']);
        try{$this->order(1,'SAVE10');self::fail('Coupon limit not enforced');}catch(RuntimeException $e){self::assertSame(1,$this->s->one('SELECT used FROM promotions')['used']);}
        $this->commerce->transition($id,$this->user,'cancelled');self::assertSame(10,$this->s->one('SELECT stock FROM products')['stock']);self::assertSame(0,$this->s->one('SELECT used FROM promotions')['used']);
        try{$this->commerce->transition($id,$this->user,'cancelled');self::fail('Duplicate cancellation');}catch(RuntimeException $e){self::assertSame(10,$this->s->one('SELECT stock FROM products')['stock']);}
    }
    public function testPaymentAmountAndOwnershipCannotBeForged(): void
    {
        $id=$this->order();$this->expectException(RuntimeException::class);
        $this->commerce->submitPayment($id,$this->user,['amount_satang'=>1,'reference'=>'fake','slip'=>'x','slip_hash'=>'fake','mime'=>'image/png']);
    }
    public function testPaymentApprovalCannotBeReplayedAndCannotSkipShipping(): void
    {
        $id=$this->order();$payment=$this->payment($id);$this->commerce->reviewPayment($payment,$this->admin,true,'Bank matched');
        self::assertSame('paid',$this->s->one('SELECT status FROM orders')['status']);
        try{$this->commerce->reviewPayment($payment,$this->admin,true,'Replay');self::fail('Replay approval');}catch(RuntimeException $e){self::assertCount(1,$this->s->all('SELECT id FROM payments'));}
        $this->expectException(RuntimeException::class);$this->commerce->transition($id,$this->user,'completed');
    }
    public function testRefundRestocksOnceAfterManualConfirmation(): void
    {
        $id=$this->order();$p=$this->payment($id);$this->commerce->reviewPayment($p,$this->admin,true,'Bank matched');
        $this->commerce->transition($id,$this->user,'refund_pending','Customer requested');self::assertSame(8,$this->s->one('SELECT stock FROM products')['stock']);
        $this->commerce->transition($id,$this->user,'refunded','Bank refund REF123');self::assertSame(10,$this->s->one('SELECT stock FROM products')['stock']);
    }
    public function testRejectedSlipCanBeResubmittedButIdenticalSlipIsBlocked(): void
    {
        $id=$this->order();$p=$this->payment($id);$this->commerce->reviewPayment($p,$this->admin,false,'Incorrect bank');
        self::assertSame('awaiting_payment',$this->s->one('SELECT status FROM orders')['status']);
        $this->expectException(RuntimeException::class);$this->payment($id);
    }
    public function testReferralCannotAssignOwnParentOrCreateIncomePlan(): void
    {
        $ref=$this->s->one('SELECT referral_code FROM users WHERE id=?',[$this->user])['referral_code'];
        $child=$this->commerce->register('Referral Child','child@example.test','Long-Password-123!',$ref);
        self::assertSame($this->user,$this->s->one('SELECT sponsor_id FROM users WHERE id=?',[$child])['sponsor_id']);
        self::assertNull($this->s->one("SELECT name FROM sqlite_master WHERE name='commissions'"));
    }
    public function testStaffCannotChangeUsersOrSettings(): void
    {
        $sales=['id'=>$this->user,'active'=>1,'role'=>'sales'];self::assertFalse(Access::can($sales,'users'));self::assertTrue(Access::can($sales,'leads'));
        $this->expectException(RuntimeException::class);(new Admin($this->s))->save('users','save',$this->user,['role'=>'admin'],$sales);
    }
    public function testLastAdministratorCannotBeDemoted(): void
    {
        $this->s->exec("UPDATE users SET role='admin' WHERE id=?",[$this->user]);$actor=$this->s->one('SELECT * FROM users WHERE id=?',[$this->user]);
        $this->expectException(RuntimeException::class);(new Admin($this->s))->save('users','save',$this->user,['name'=>'Admin','email'=>'buyer@example.test','phone'=>'','password'=>'','role'=>'member','active'=>'1'],$actor);
    }
    public function testWebhookSignaturesAndReplayProtection(): void
    {
        $raw='{"test":true}';$secret='test-secret';$line=base64_encode(hash_hmac('sha256',$raw,$secret,true));
        self::assertTrue(Bot::signature('line',$raw,$line,$secret));self::assertFalse(Bot::signature('line',$raw.'x',$line,$secret));self::assertFalse(Bot::signature('line',$raw,'',''));
        $now=time();$sig='t='.$now.',v1='.hash_hmac('sha256',$now.'.'.$raw,$secret);
        self::assertTrue(Gateways::stripeSignature($raw,$sig,$secret,$now));self::assertFalse(Gateways::stripeSignature($raw,$sig,$secret,$now+301));
        $bot=new Bot($this->s);$bot->inbound('line','event-1','U123','Hello');$bot->inbound('line','event-1','U123','Hello');
        self::assertCount(1,$this->s->all('SELECT id FROM messages'));self::assertCount(1,$this->s->all('SELECT id FROM inbox_jobs'));
    }
    public function testLeadCaptureRequiresConsentAndDeduplicatesContact(): void
    {
        $bot=new Bot($this->s);$c=$bot->conversation('web','visitor1');
        try{$bot->captureLead($c['id'],'Test Lead','lead@example.test',false);self::fail('Consent bypass');}catch(RuntimeException $e){self::assertCount(0,$this->s->all('SELECT id FROM leads'));}
        $one=$bot->captureLead($c['id'],'Test Lead','lead@example.test',true);$two=$bot->captureLead($c['id'],'Test Lead','lead@example.test',true);
        self::assertSame($one,$two);self::assertCount(1,$this->s->all('SELECT id FROM leads'));
    }
    public function testHumanHandoffStopsBotAndWorkerDeduplicates(): void
    {
        $bot=new Bot($this->s);$bot->inbound('line','event-1','U123','แอดมิน');$bot->work();$bot->work();
        $c=$this->s->one('SELECT * FROM conversations');self::assertSame('human',$c['mode']);self::assertNull($bot->reply($c,'test'));self::assertCount(1,$this->s->all('SELECT id FROM outbox'));
        $this->s->exec('UPDATE conversations SET ai_consent=1 WHERE id=?',[$c['id']]);
        $bot->reply($c,'หยุด AI');self::assertSame(0,$this->s->one('SELECT ai_consent FROM conversations')['ai_consent']);
    }
    public function testGatewayCannotMarkWrongAmountPaid(): void
    {
        $id=$this->order();$this->s->exec("UPDATE orders SET status='gateway_pending' WHERE id=?",[$id]);
        $this->s->insert('gateway_attempts',['order_id'=>$id,'provider'=>'stripe','external_id'=>'cs_test_123','status'=>'ready','created_at'=>Store::now()]);
        try{(new Gateways($this->s))->settle('stripe','evt_1','cs_test_123',1,'thb',true);self::fail('Amount bypass');}catch(RuntimeException $e){self::assertSame('gateway_pending',$this->s->one('SELECT status FROM orders')['status']);self::assertCount(0,$this->s->all('SELECT id FROM payments'));}
        (new Gateways($this->s))->settle('stripe','evt_1','cs_test_123',20000,'thb',true);(new Gateways($this->s))->settle('stripe','evt_1','cs_test_123',20000,'thb',true);
        self::assertSame('paid',$this->s->one('SELECT status FROM orders')['status']);self::assertCount(1,$this->s->all('SELECT id FROM payments'));
    }
}
