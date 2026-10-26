<?php
namespace Tests\Platform;
use App\Libraries\Platform\{Store,Commerce,AnnualWebsiteOffer,AnnualWebsitePackage,PlatformBilling};
use PHPUnit\Framework\TestCase;
final class AnnualWebsiteOfferTest extends TestCase
{
    public function testCatalogPriceOverridesLegacyMonthlyRules():void
    {
        $s=new Store(':memory:');$s->install();$id=AnnualWebsitePackage::add($s);
        $s->set('annual_website_promotions','{"2026-10":true}');
        $offer=new AnnualWebsiteOffer($s);
        self::assertSame(99000,$offer->current('2026-10-01 00:00:00')['amount_satang']);
        $s->exec('UPDATE products SET price_satang=72550 WHERE id=?',[$id]);
        self::assertSame(72550,$offer->current()['amount_satang']);
        self::assertFalse($offer->current()['active']);
    }
    public function testNewInvoiceUsesCatalogPriceWhileExistingInvoiceKeepsSnapshot():void
    {
        $s=new Store(':memory:');$s->install();$id=AnnualWebsitePackage::add($s);
        $buyer=(new Commerce($s))->register('Buyer','buyer@example.test','Long-Password-123!');
        foreach(['platform_promptpay'=>'0812345678','platform_slip_provider'=>'easyslip','easyslip_api_key'=>'fixture','slip_receiver_bank'=>'004','slip_receiver_account'=>'1234567890'] as $k=>$v){$s->set($k,$v);}
        $s->exec('UPDATE products SET price_satang=72550 WHERE id=?',[$id]);
        $bill=new PlatformBilling($s);$invoice=$bill->invoice($buyer);self::assertSame(72550,(int)$invoice['amount_satang']);
        $s->exec('UPDATE products SET price_satang=85000 WHERE id=?',[$id]);
        self::assertSame(72550,(int)$bill->invoice($buyer)['amount_satang']);
        $s->exec("UPDATE platform_invoices SET status='expired' WHERE id=?",[$invoice['id']]);
        self::assertSame(85000,(int)$bill->invoice($buyer)['amount_satang']);
    }
    public function testLegacyConstraintsMigrateWithoutChangingHistoricalAmounts():void
    {
        foreach(['amount_satang=99000','amount_satang IN (39900,99000)'] as $constraint){
            $s=new Store(':memory:');$s->db->exec(file_get_contents(APPPATH.'Database/platform.sql'));
            $s->db->exec(str_replace('amount_satang>0',$constraint,file_get_contents(APPPATH.'Database/platform_billing.sql')));
            $id=$s->insert('users',['name'=>'Old buyer','email'=>'old@example.test','password_hash'=>'fixture','created_at'=>Store::now()]);
            $invoice=$s->insert('platform_invoices',['number'=>'OLD-990','user_id'=>$id,'amount_satang'=>99000,'created_at'=>Store::now(),'expires_at'=>'2030-01-01 00:00:00']);
            $s->install();self::assertSame(99000,(int)$s->one('SELECT amount_satang FROM platform_invoices WHERE id=?',[$invoice])['amount_satang']);self::assertSame([],$s->all('PRAGMA foreign_key_check'));
            self::assertStringContainsString('amount_satang>0',$s->one("SELECT sql FROM sqlite_master WHERE name='platform_invoices'")['sql']);
        }
    }
}
