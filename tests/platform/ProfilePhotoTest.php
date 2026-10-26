<?php
namespace Tests\Platform;

use App\Libraries\Platform\{Store,Commerce,ProfilePhoto,Affiliate};
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ProfilePhotoTest extends TestCase
{
    private Store $s;private ProfilePhoto $photos;private int $a;private int $b;
    protected function setUp():void
    {
        $this->s=new Store(':memory:');$this->s->install();$this->photos=new ProfilePhoto($this->s);$c=new Commerce($this->s);
        $this->a=$c->register('Photo Alpha','alpha@example.test','Long-Password-123!');$this->b=$c->register('Photo Beta','beta@example.test','Long-Password-123!');
    }
    private function image():string
    { $im=imagecreatetruecolor(800,1000);imagefill($im,0,0,imagecolorallocate($im,20,90,65));ob_start();imagepng($im);$bytes=ob_get_clean();imagedestroy($im);return $bytes; }
    public function testPortraitIsSanitizedResizedAndOwnerScoped():void
    {
        $this->photos->save($this->a,$this->image().'<?php secret_metadata ?>');$p=$this->photos->own($this->a);
        self::assertNull($this->photos->own($this->b));self::assertStringNotContainsString('secret_metadata',$p['image']);
        $info=getimagesizefromstring($p['image']);self::assertSame('image/jpeg',$info['mime']);self::assertSame(640,$info[0]);self::assertSame(640,$info[1]);
        $this->photos->save($this->a,$this->image());self::assertNotSame($p['version'],$this->photos->own($this->a)['version']);
        self::assertNotEmpty((new Affiliate($this->s))->owner('0001')['avatar_version']);
    }
    public function testPublicPhotoRespectsPublicationActiveStatusAndExpiry():void
    {
        $this->photos->save($this->a,$this->image());self::assertNull($this->photos->published('0001'));
        (new Affiliate($this->s))->saveProfile($this->a,['public_name'=>'Photo Alpha','line_url'=>'@photoalpha','affiliate_public'=>'1'],$this->a);
        self::assertNotNull($this->photos->published('0001'));self::assertNull($this->photos->published('0002'));self::assertNull($this->photos->published('../0001'));
        $this->s->exec('UPDATE users SET active=0 WHERE id=?',[$this->a]);self::assertNull($this->photos->published('0001'));
        $this->s->exec('UPDATE users SET active=1 WHERE id=?',[$this->a]);
        $this->s->exec("UPDATE platform_subscriptions SET trial_ends_at='2020-01-01 00:00:00' WHERE user_id=?",[$this->a]);self::assertNull($this->photos->published('0001'));
    }
    public function testRemovingPhotoDoesNotTouchAnotherOwner():void
    {
        $this->photos->save($this->a,$this->image());$this->photos->save($this->b,$this->image());$this->photos->remove($this->a);
        self::assertNull($this->photos->own($this->a));self::assertNotNull($this->photos->own($this->b));
    }
    public function testSvgExecutableAndOversizedFilesAreRejected():void
    {
        foreach(['<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>','<?php echo "payload";',str_repeat('X',5*1024*1024+1)] as $bad){
            try{$this->photos->save($this->a,$bad);self::fail('Unsafe file accepted');}catch(RuntimeException $e){self::assertNotEmpty($e->getMessage());}
        }
        self::assertNull($this->photos->own($this->a));
    }
}
