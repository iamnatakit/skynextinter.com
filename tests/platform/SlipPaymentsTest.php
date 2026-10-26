<?php
namespace Tests\Platform;

use App\Libraries\Platform\{Store,Commerce,SlipVerifier,SlipPayments};
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SlipPaymentsTest extends TestCase
{
    private Store $s;
    private Commerce $commerce;
    private int $user;
    private int $admin;
    private int $product;
    private array $previousEnvironment=[];
    protected function setUp(): void
    {
        foreach(['SLIP_AUTO_VERIFY'=>'1','SLIP_PROVIDER'=>'easyslip'] as $key=>$value) {
            $this->previousEnvironment[$key]=[$_ENV[$key]??null,$_SERVER[$key]??null,getenv($key)];
            $_ENV[$key]=$value;$_SERVER[$key]=$value;putenv($key.'='.$value);
        }
        $this->s=new Store(':memory:');$this->s->install();$this->commerce=new Commerce($this->s);
        $this->user=$this->commerce->register('Test Buyer','buyer@example.test','Long-Password-123!');
        $this->admin=$this->commerce->register('Test Admin','admin@example.test','Long-Password-123!');
        $this->s->exec("UPDATE users SET role='admin' WHERE id=?",[$this->admin]);
        $this->product=$this->s->insert('products',['sku'=>'TEST','name'=>'Product','price_satang'=>10000,'stock'=>10,'active'=>1,'created_at'=>Store::now()]);
    }
    protected function tearDown(): void {
        foreach($this->previousEnvironment as $key=>[$env,$server,$process]) {
            if($env===null){unset($_ENV[$key]);}else{$_ENV[$key]=$env;}
            if($server===null){unset($_SERVER[$key]);}else{$_SERVER[$key]=$server;}
            putenv($process===false?$key:$key.'='.$process);
        }
    }
    private function payment(): int
    {
        $id=$this->commerce->checkout($this->user,[$this->product=>1],'',['recipient'=>'Buyer','phone'=>'0812345678','address'=>'Address 123'],bin2hex(random_bytes(16)));
        return $this->commerce->submitPayment($id,$this->user,['amount_satang'=>10000,'reference'=>'USER-INPUT','slip'=>str_repeat('a',32).'.png','slip_hash'=>bin2hex(random_bytes(32)),'mime'=>'image/png']);
    }
    private function response(): array
    {
        return ['success'=>true,'data'=>['isDuplicate'=>false,'isAmountMatched'=>true,'amountInSlip'=>100,
            'matchedAccount'=>['bank'=>['code'=>'004'],'bankNumber'=>'123-4-56789-0'],
            'rawSlip'=>['transRef'=>'TRANS123456','date'=>gmdate('Y-m-d\TH:i:s\Z'),'countryCode'=>'TH',
                'amount'=>['amount'=>100,'local'=>['currency'=>'THB']], 'sender'=>['bank'=>['id'=>'014']], 'receiver'=>['bank'=>['id'=>'004']]]]];
    }
    private function order(): array { return ['number'=>'TEST','total_satang'=>10000,'created_at'=>Store::now()]; }
    private function fake(?callable $before=null): SlipVerifier
    {
        $response=$this->response();
        return new class($response,$before) extends SlipVerifier {
            public function __construct(private array $response,private $before) {}
            public function verify(string $provider,string $path,string $mime,array $order): array {
                if($this->before){($this->before)();}
                return $this->validate($provider,$this->response,$order,'004','1234567890');
            }
        };
    }
    public function testSuccessfulVerificationIsAtomicAndReplayDoesNotIssueAnotherPayment(): void
    {
        $p=$this->payment();$service=new SlipPayments($this->s,$this->fake());
        self::assertSame('verified',$service->check($p));self::assertSame('unchanged',$service->check($p));
        self::assertSame('paid',$this->s->one('SELECT status FROM orders')['status']);
        self::assertCount(1,$this->s->all('SELECT * FROM verified_transfers'));
        self::assertCount(1,$this->s->all('SELECT * FROM payment_tokens'));
        self::assertMatchesRegularExpression('/^SNP-[a-f0-9]{64}$/D',$this->s->one('SELECT token FROM payment_tokens')['token']);
        self::assertSame('approved',$this->s->one('SELECT status FROM payments')['status']);
    }
    public function testSameTransactionInDifferentImageCannotPayTwoOrders(): void
    {
        $one=$this->payment();$two=$this->payment();$service=new SlipPayments($this->s,$this->fake());
        self::assertSame('verified',$service->check($one));self::assertSame('review',$service->check($two));
        self::assertSame('pending',$this->s->one('SELECT status FROM payments WHERE id=?',[$two])['status']);
        self::assertCount(1,$this->s->all('SELECT * FROM payment_tokens'));
        self::assertCount(1,$this->s->all('SELECT * FROM verified_transfers'));
    }
    public function testCancellationDuringProviderCallCannotBeOverwritten(): void
    {
        $p=$this->payment();$order=$this->s->one('SELECT order_id FROM payments WHERE id=?',[$p])['order_id'];
        $service=new SlipPayments($this->s,$this->fake(fn()=> $this->commerce->transition($order,$this->user,'cancelled')));
        self::assertSame('review',$service->check($p));self::assertSame('cancelled',$this->s->one('SELECT status FROM orders')['status']);
        self::assertCount(0,$this->s->all('SELECT * FROM verified_transfers'));
        self::assertCount(0,$this->s->all('SELECT * FROM payment_tokens'));
    }
    public function testNetworkFailureRemainsPendingAndDoesNotRetryAutomatically(): void
    {
        $p=$this->payment();$calls=0;
        $service=new SlipPayments($this->s,$this->fake(function() use(&$calls){$calls++;throw new RuntimeException('Network unavailable');}));
        self::assertSame('review',$service->check($p));self::assertSame('unchanged',$service->check($p));self::assertSame(1,$calls);
        self::assertSame('payment_review',$this->s->one('SELECT status FROM orders')['status']);
        self::assertCount(0,$this->s->all('SELECT * FROM verified_transfers'));
    }
    public function testExpiredLeaseIsSentToStaffWithoutAnotherApiCall(): void
    {
        $p=$this->payment();$this->s->exec("UPDATE slip_checks SET status='checking',lease_until=1 WHERE payment_id=?",[$p]);
        self::assertSame(0,(new SlipPayments($this->s,$this->fake()))->work());self::assertSame('review',$this->s->one('SELECT status FROM slip_checks')['status']);
    }
    public function testInvalidProviderEvidenceCannotApprove(): void
    {
        $mutations=[
            fn(&$r)=>$r['success']='true',
            fn(&$r)=>$r['data']['isDuplicate']=true,
            fn(&$r)=>$r['data']['isAmountMatched']=false,
            fn(&$r)=>$r['data']['matchedAccount']=null,
            fn(&$r)=>$r['data']['matchedAccount']['bankNumber']='9999999999',
            fn(&$r)=>$r['data']['rawSlip']['receiver']['bank']['id']='002',
            fn(&$r)=>$r['data']['rawSlip']['amount']['amount']=99.99,
            fn(&$r)=>$r['data']['rawSlip']['date']='2020-01-01T00:00:00Z',
            fn(&$r)=>$r['data']['rawSlip']['date']=gmdate('Y-m-d\TH:i:s\Z',time()+3600),
            fn(&$r)=>$r['data']['rawSlip']['amount']['local']['currency']='USD',
        ];
        foreach($mutations as $mutation) {
            $r=$this->response();$mutation($r);
            try{(new SlipVerifier())->validate('easyslip',$r,$this->order(),'004','1234567890');self::fail('Untrusted evidence accepted');}
            catch(RuntimeException $e){self::assertNotEmpty($e->getMessage());}
        }
    }
    public function testSlipOkContractChecksMaskedAccountAndRejectsProviderError(): void
    {
        $r=['success'=>true,'data'=>['success'=>true,'receivingBank'=>'004','sendingBank'=>'014','receiver'=>['account'=>['value'=>'xxx-x-x6789-0']],
            'amount'=>'100.00','countryCode'=>'TH','transRef'=>'TRANS123456','transTimestamp'=>gmdate('Y-m-d\TH:i:s\Z')]];
        self::assertArrayHasKey('transaction_key',(new SlipVerifier())->validate('slipok',$r,$this->order(),'004','1234567890'));
        $r['data']['receiver']['account']['value']='xxx-x-x6788-0';
        $this->expectException(RuntimeException::class);(new SlipVerifier())->validate('slipok',$r,$this->order(),'004','1234567890');
    }
    public function testRefundRevokesConfirmationToken(): void
    {
        $p=$this->payment();(new SlipPayments($this->s,$this->fake()))->check($p);
        $order=$this->s->one('SELECT order_id FROM payments WHERE id=?',[$p])['order_id'];
        $this->commerce->transition($order,$this->user,'refund_pending','Refund requested');
        $this->commerce->transition($order,$this->user,'refunded','Bank refund confirmed');
        self::assertNotNull($this->s->one('SELECT revoked_at FROM payment_tokens')['revoked_at']);
    }
    public function testStaffApprovalDuringCallCannotGenerateAutomaticToken(): void
    {
        $p=$this->payment();
        self::assertSame('review',(new SlipPayments($this->s,$this->fake(fn()=> $this->commerce->reviewPayment($p,$this->admin,true,'Staff verified'))))->check($p));
        self::assertSame('paid',$this->s->one('SELECT status FROM orders')['status']);
        self::assertCount(0,$this->s->all('SELECT * FROM payment_tokens'));
    }
    public function testManualDecisionBeforeWorkerClosesQueuedCheck(): void
    {
        $p=$this->payment();$this->commerce->reviewPayment($p,$this->admin,true,'Staff verified');
        self::assertSame(0,(new SlipPayments($this->s,$this->fake()))->work());
        self::assertSame('review',$this->s->one('SELECT status FROM slip_checks')['status']);
        self::assertCount(0,$this->s->all('SELECT * FROM payment_tokens'));
    }
}
