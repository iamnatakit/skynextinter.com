<?php
namespace Tests\Platform;

use App\Libraries\Platform\{Store,Commerce,ThaiProvinces};
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RegistrationFieldsTest extends TestCase
{
    public function testProvinceListHasSeventySevenUniqueChoices():void
    {self::assertCount(77,ThaiProvinces::ALL);self::assertCount(77,array_unique(ThaiProvinces::ALL));self::assertContains('กรุงเทพมหานคร',ThaiProvinces::ALL);self::assertContains('บึงกาฬ',ThaiProvinces::ALL);}
    public function testMissingPhoneOrInvalidProvinceCannotCreateAccountOrConsumeAffiliateId():void
    {
        $s=new Store(':memory:');$s->install();$c=new Commerce($s);
        foreach([['เชียงใหม่',''],['เชียงใหม่','letters'],['','0812345678'],['Fake province','0812345678']] as [$province,$phone]){
            try{$c->register('Required member','required@example.test','Long-Password-123!','',$phone,['province'=>$province,'membership_kind'=>'new']);self::fail('Incomplete registration accepted');}catch(RuntimeException $e){self::assertNotEmpty($e->getMessage());}
        }
        self::assertSame(0,(int)$s->one('SELECT COUNT(*) n FROM users')['n']);
        $id=$c->register('Valid member','valid@example.test','Long-Password-123!','','081-234-5678',['province'=>'เชียงใหม่','membership_kind'=>'new']);
        $u=$s->one('SELECT referral_code,phone,public_province,affiliate_public FROM users WHERE id=?',[$id]);
        self::assertSame('0001',$u['referral_code']);self::assertSame('0812345678',$u['phone']);self::assertSame('เชียงใหม่',$u['public_province']);self::assertSame(0,$u['affiliate_public']);
    }
    public function testProvinceAndPhoneDoNotBypassExistingMemberRequirements():void
    {
        $s=new Store(':memory:');$s->install();
        $this->expectException(RuntimeException::class);
        (new Commerce($s))->register('Existing member','existing@example.test','Long-Password-123!','','0812345678',['province'=>'เชียงใหม่','membership_kind'=>'existing']);
    }
}
