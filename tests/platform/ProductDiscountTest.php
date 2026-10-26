<?php
namespace Tests\Platform;

use App\Libraries\Platform\{Admin,AnnualWebsitePackage,AnnualWebsiteOffer,Commerce,ProductPricing,Store};
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ProductDiscountTest extends TestCase
{
    private Store $store;
    private Commerce $commerce;
    private int $product;
    private int $member;
    private int $customer;
    private array $admin;

    protected function setUp(): void
    {
        $this->store=new Store(':memory:');
        $this->store->install();
        $this->commerce=new Commerce($this->store);
        $this->product=$this->store->insert('products',[
            'sku'=>'DISCOUNT-TEST','name'=>'Discount test product','price_satang'=>10000,
            'member_price_satang'=>8000,'stock'=>20,'active'=>1,'created_at'=>Store::now(),
        ]);
        $this->member=$this->commerce->register('Member','discount-member@example.test','Long-Password-123!');
        $this->customer=$this->commerce->register('Customer','discount-customer@example.test','Long-Password-123!','','', ['membership_kind'=>'customer']);
        $adminId=$this->commerce->register('Admin','discount-admin@example.test','Long-Password-123!');
        $this->store->exec("UPDATE users SET role='admin' WHERE id=?",[$adminId]);
        $this->admin=$this->store->one('SELECT * FROM users WHERE id=?',[$adminId]);
    }

    public function testPercentDiscountAppliesToPublicPriceButNotMemberPrice(): void
    {
        $this->store->exec("UPDATE products SET discount_kind='percent',discount_value=1500 WHERE id=?",[$this->product]);
        $public=$this->commerce->quote([$this->product=>2]);
        self::assertSame(17000,$public['subtotal']);
        self::assertSame(8500,$public['items'][0]['price_satang']);
        self::assertSame(10000,$public['items'][0]['original_price_satang']);
        self::assertSame(1500,$public['items'][0]['discount_satang']);
        self::assertSame('15%',ProductPricing::discountLabel($this->store->one('SELECT * FROM products WHERE id=?',[$this->product])));

        $member=$this->commerce->quote([$this->product=>1],'',$this->member);
        self::assertSame(8000,$member['subtotal']);
        self::assertSame(0,$member['items'][0]['discount_satang']);
        self::assertTrue($member['items'][0]['is_member_price']);

        $order=$this->commerce->checkout($this->customer,[$this->product=>2],'',['recipient'=>'Customer Test','phone'=>'0812345678','address'=>'Test delivery address'],str_repeat('b',32));
        self::assertSame(17000,(int)$this->store->one('SELECT total_satang FROM orders WHERE id=?',[$order])['total_satang']);
        self::assertSame(8500,(int)$this->store->one('SELECT price_satang FROM order_items WHERE order_id=?',[$order])['price_satang']);
    }

    public function testAdminCanSetFixedDiscountThroughProductEditorAndClearIt(): void
    {
        $admin=new Admin($this->store);
        $admin->save('products','save',$this->product,[
            'name'=>'Discount test product','sku'=>'DISCOUNT-TEST','price'=>'100.00','member_price'=>'80.00',
            'discount_kind'=>'fixed','discount_value'=>'25.50','pv'=>'0','stock'=>'20','category'=>'',
            'short_description'=>'','description'=>'','inquiry_only'=>'0','inquiry_label'=>'','image'=>'','active'=>'1',
        ],$this->admin);
        self::assertSame(7450,$this->commerce->quote([$this->product=>1])['items'][0]['price_satang']);
        $data=$admin->data('products',$this->product,'',1,$this->admin);
        self::assertSame('fixed',$data['record']['discount_kind']);
        self::assertSame('25.50',$data['record']['discount_value']);
        self::assertSame('25.50 บาท',$data['rows'][0]['discount']);

        $admin->save('products','save',$this->product,[
            'name'=>'Discount test product','sku'=>'DISCOUNT-TEST','price'=>'100.00','member_price'=>'80.00',
            'discount_kind'=>'','discount_value'=>'','pv'=>'0','stock'=>'20','category'=>'',
            'short_description'=>'','description'=>'','inquiry_only'=>'0','inquiry_label'=>'','image'=>'','active'=>'1',
        ],$this->admin);
        self::assertSame(10000,$this->commerce->quote([$this->product=>1])['items'][0]['price_satang']);
    }

    public function testPercentDiscountCannotExceedOneHundredPercent(): void
    {
        $admin=new Admin($this->store);
        try {
            $admin->save('products','save',$this->product,[
                'name'=>'Discount test product','sku'=>'DISCOUNT-TEST','price'=>'100.00','member_price'=>'80.00',
                'discount_kind'=>'percent','discount_value'=>'100.01','pv'=>'0','stock'=>'20','category'=>'',
                'short_description'=>'','description'=>'','inquiry_only'=>'0','inquiry_label'=>'','image'=>'','active'=>'1',
            ],$this->admin);
            self::fail('Percent discounts over 100% must be rejected');
        } catch (RuntimeException $e) {
            self::assertSame('ส่วนลดเปอร์เซ็นต์ต้องไม่เกิน 100%',$e->getMessage());
        }
        self::assertSame(10000,$this->commerce->quote([$this->product=>1])['subtotal']);
    }

    public function testAnnualWebsiteDiscountMatchesTheAnnualInvoicePrice(): void
    {
        $annualId=AnnualWebsitePackage::add($this->store);
        $this->store->exec("UPDATE products SET discount_kind='fixed',discount_value=12500 WHERE id=?",[$annualId]);
        self::assertSame(86500,(new AnnualWebsiteOffer($this->store))->current()['amount_satang']);
    }

    public function testExistingProductSchemaReceivesDiscountColumnsDuringInstall(): void
    {
        $store=new Store(':memory:');
        $schema=str_replace(",\n    discount_kind TEXT NOT NULL DEFAULT '' CHECK(discount_kind IN ('','fixed','percent')),\n    discount_value INTEGER NOT NULL DEFAULT 0 CHECK(discount_value>=0)",'',file_get_contents(APPPATH.'Database/platform.sql'));
        $store->db->exec($schema);
        $store->install();
        $columns=array_column($store->all('PRAGMA table_info(products)'),'name');
        self::assertContains('discount_kind',$columns);
        self::assertContains('discount_value',$columns);
        $id=$store->insert('products',['sku'=>'MIGRATED','name'=>'Migrated product','price_satang'=>1000,'stock'=>1,'active'=>1,'created_at'=>Store::now()]);
        self::assertSame('', $store->one('SELECT discount_kind FROM products WHERE id=?',[$id])['discount_kind']);
        self::assertSame(0,(int)$store->one('SELECT discount_value FROM products WHERE id=?',[$id])['discount_value']);
    }
}
