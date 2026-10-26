<?php
namespace Tests\Platform;
use App\Libraries\Platform\{Store,AnnualWebsitePackage,Commerce};
use PHPUnit\Framework\TestCase;
use RuntimeException;
final class AnnualWebsitePackageTest extends TestCase
{
    public function testServiceIsIdempotentAndHasNoStockPVOrPhysicalCheckout(): void
    {
        $s=new Store(':memory:');$s->install();$id=AnnualWebsitePackage::add($s);
        self::assertSame($id,AnnualWebsitePackage::add($s));
        $p=$s->one('SELECT * FROM products WHERE id=?',[$id]);
        self::assertTrue(AnnualWebsitePackage::matches($p));self::assertSame(99000,(int)$p['price_satang']);
        self::assertSame(0,(int)$p['pv']);self::assertSame(0,(int)$p['stock']);
        self::assertSame(1,(int)$p['active']);
        $s->exec('UPDATE products SET inquiry_only=0,stock=99 WHERE id=?',[$id]);
        $this->expectException(RuntimeException::class);$this->expectExceptionMessage('Platform');
        (new Commerce($s))->quote([$id=>1]);
    }
    public function testRepeatedInstallPreservesAdminEdits(): void
    {
        $s=new Store(':memory:');$s->install();$id=AnnualWebsitePackage::add($s);
        $s->exec("UPDATE products SET name='Admin revised service',active=0 WHERE id=?",[$id]);
        AnnualWebsitePackage::add($s);self::assertSame('Admin revised service',$s->one('SELECT name FROM products WHERE id=?',[$id])['name']);
        self::assertSame(0,(int)$s->one('SELECT active FROM products WHERE id=?',[$id])['active']);
    }
}
