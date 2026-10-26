<?php
namespace Tests\Platform;
use App\Libraries\Platform\{Store,Commerce};
use PHPUnit\Framework\TestCase;
final class MemberPricingTest extends TestCase
{
    public function testGuestMemberInactivePricesAndFrozenCheckoutPV():void
    {
        $s=new Store(':memory:');$s->install();$c=new Commerce($s);
        $id=$s->insert('products',['sku'=>'SKN-U-CALIN','name'=>'U-CALIN','price_satang'=>159000,'member_price_satang'=>89000,'pv'=>100,'stock'=>10000,'active'=>1,'created_at'=>Store::now()]);
        $member=$c->register('Member','member@example.test','Long-Password-123!');
        self::assertSame(318000,$c->quote([$id=>2])['subtotal']);self::assertSame(178000,$c->quote([$id=>2],'',$member)['subtotal']);
        self::assertSame(318000,$c->quote([$id=>2],'',99999)['subtotal']);
        $s->exec('UPDATE users SET active=0 WHERE id=?',[$member]);self::assertSame(159000,$c->quote([$id=>1],'',$member)['subtotal']);
        $s->exec('UPDATE users SET active=1 WHERE id=?',[$member]);
        $order=$c->checkout($member,[$id=>1],'',['recipient'=>'Member Test','phone'=>'0812345678','address'=>'Test Address'],str_repeat('a',32));
        $s->exec('UPDATE products SET member_price_satang=1,pv=0 WHERE id=?',[$id]);
        $item=$s->one('SELECT price_satang,pv FROM order_items WHERE order_id=?',[$order]);
        self::assertSame(89000,(int)$item['price_satang']);self::assertSame(100,(int)$item['pv']);
        self::assertSame(89000,(int)$s->one('SELECT subtotal_satang FROM orders WHERE id=?',[$order])['subtotal_satang']);
        self::assertSame(9999,(int)$s->one('SELECT stock FROM products WHERE id=?',[$id])['stock']);
    }
    public function testAffiliateRolesOnlyReceiveMemberPriceAndPublicCopyHidesPrice():void
    {
        $s=new Store(':memory:');$s->install();$c=new Commerce($s);$member=$c->register('Member','roles@example.test','Long-Password-123!');
        $id=$s->insert('products',['sku'=>'ROLE-PRICE','name'=>'Role price','price_satang'=>159000,'member_price_satang'=>89000,'stock'=>10,'active'=>1,'created_at'=>Store::now()]);
        foreach(['member','manager','sales','affiliate','downline_team','external_team','admin'] as $role){
            $s->exec('UPDATE users SET role=? WHERE id=?',[$role,$member]);
            self::assertSame(in_array($role,['admin','affiliate','downline_team','external_team'],true)?89000:159000,$c->quote([$id=>1],'',$member)['subtotal']);
        }
        $copy="ข้อมูลสินค้า\nราคา: 1,590 บาท\nราคาสมาชิก: 890 บาท\nคะแนน: 100 PV";
        $public=\App\Libraries\Platform\ProductPricing::publicDescription($copy,false);
        self::assertStringNotContainsString('890',$public);self::assertStringContainsString('1,590',$public);
        self::assertSame($copy,\App\Libraries\Platform\ProductPricing::publicDescription($copy,true));
        self::assertFalse(\App\Libraries\Platform\ProductPricing::eligible(null));
    }
    public function testProductWithoutMemberPriceKeepsItsPrice():void
    {
        $s=new Store(':memory:');$s->install();$c=new Commerce($s);$member=$c->register('Member','member@example.test','Long-Password-123!');
        $id=$s->insert('products',['sku'=>'REGULAR','name'=>'Regular','price_satang'=>50000,'pv'=>100,'stock'=>10,'active'=>1,'created_at'=>Store::now()]);
        self::assertSame(50000,$c->quote([$id=>1],'',$member)['subtotal']);
    }
}
