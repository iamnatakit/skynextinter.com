<?php
namespace Tests\Platform;

use App\Libraries\Platform\{Store,Commerce,Affiliate};
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AffiliateSequenceTest extends TestCase
{
    public function testAllCreationPathsAllocateSequentialIdsAndNeverReuseDeletedIds(): void
    {
        $s=new Store(':memory:');$s->install();$c=new Commerce($s);
        $one=$c->register('First User','first@example.test','Long-Password-123!');
        $two=$s->insert('users',['name'=>'Second User','email'=>'second@example.test','password_hash'=>'test','referral_code'=>'RANDOM123','created_at'=>Store::now()]);
        self::assertSame('0001',$s->one('SELECT referral_code FROM users WHERE id=?',[$one])['referral_code']);
        self::assertSame('0002',$s->one('SELECT referral_code FROM users WHERE id=?',[$two])['referral_code']);
        $s->exec('DELETE FROM users WHERE id=?',[$two]);
        $three=$c->register('Third User','third@example.test','Long-Password-123!');
        self::assertSame('0003',$s->one('SELECT referral_code FROM users WHERE id=?',[$three])['referral_code']);
        $this->expectException(RuntimeException::class);
        Affiliate::validateCustomCode('CUSTOM',$one,$s);
    }
    public function testSequenceContinuesAfterFourDigitsAndOldLinksKeepTheirOwner(): void
    {
        $s=new Store(':memory:');$s->install();$s->set('affiliate_sequence','9999');
        $id=(new Commerce($s))->register('Next User','next@example.test','Long-Password-123!');
        self::assertSame('10000',$s->one('SELECT referral_code FROM users WHERE id=?',[$id])['referral_code']);
        $s->set('affiliate_legacy_codes',json_encode(['old123'=>$id]));
        self::assertSame($id,(new Affiliate($s))->owner('OLD123')['id']);
        self::assertSame('10000',Affiliate::validateCustomCode('10000',$id,$s));
    }
    public function testDirectDatabaseWritesCannotBypassTheRule(): void
    {
        $s=new Store(':memory:');$s->install();
        $id=(new Commerce($s))->register('First User','first@example.test','Long-Password-123!');
        foreach ([
            "INSERT INTO users(name,email,password_hash,referral_code,created_at) VALUES('Bad','bad@example.test','test','RANDOM','2026-01-01')",
            "UPDATE users SET referral_code='8888' WHERE id=".$id,
        ] as $sql) {
            try { $s->exec($sql); self::fail('Direct write bypassed Affiliate ID rule'); }
            catch (\PDOException $e) { self::assertStringContainsString('Affiliate ID',$e->getMessage()); }
        }
        self::assertSame('0001',$s->one('SELECT referral_code FROM users WHERE id=?',[$id])['referral_code']);
    }
}
