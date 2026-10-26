<?php
namespace Tests\Platform;
use App\Libraries\Platform\{Store,Commerce,Access,Membership,MemberWorkspace,ProductPricing,Affiliate,RegistrationPlan};
use PHPUnit\Framework\TestCase;
final class CustomerRegistrationTest extends TestCase
{
    public function testCustomerSelectionCannotGrantAffiliatePricesPagesOrAnnualCommitment():void
    {
        $s=new Store(':memory:');$s->install();$c=new Commerce($s);
        for($i=1;$i<=5;$i++){$c->register('Seed '.$i,'seed'.$i.'@example.test','Long-Password-123!');}
        $id=$c->register('Buyer Customer','buyer@example.test','Long-Password-123!','0005','',['membership_kind'=>'customer','role'=>'admin','annual_opt_in'=>'1']);
        $u=$s->one('SELECT * FROM users WHERE id=?',[$id]);self::assertSame('member',$u['role']);self::assertSame(5,(int)$u['sponsor_id']);
        self::assertFalse(ProductPricing::eligible($u));self::assertNull((new Affiliate($s))->owner($u['referral_code']));
        foreach(array_keys(Access::MODULES) as $module){self::assertFalse(Access::can($u,$module,$s));}
        $w=new MemberWorkspace($s);self::assertFalse($w->canPages($u));self::assertArrayHasKey('payment',$w->menus($u));self::assertArrayNotHasKey('affiliate',$w->menus($u));self::assertArrayNotHasKey('platform/billing',$w->menus($u));
        self::assertNull((new RegistrationPlan($s))->details($id)['renewal']);self::assertSame('customer',(new Membership($s))->status($id,'2030-01-01 00:00:00')['state']);self::assertFalse((new Membership($s))->isDownline($id));
        $product=$s->insert('products',['sku'=>'BUYER-PRODUCT','name'=>'Buyer product','price_satang'=>159000,'member_price_satang'=>89000,'pv'=>100,'stock'=>10,'active'=>1,'created_at'=>Store::now()]);
        $order=$c->checkout($id,[$product=>1],'',['recipient'=>'Buyer Customer','phone'=>'0812345678','address'=>'Test address'],str_repeat('c',32));
        self::assertSame(159000,(int)$s->one('SELECT total_satang FROM orders WHERE id=?',[$order])['total_satang']);
        (new Membership($s))->qualifyAnnual($id);self::assertSame('member',$s->one('SELECT role FROM users WHERE id=?',[$id])['role']);
    }
}
