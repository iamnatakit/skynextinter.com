<?php
namespace Tests\Platform;

use App\Libraries\Platform\{Store,Commerce,Affiliate};
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AffiliateTest extends TestCase
{
    private Store $s;
    private Commerce $commerce;
    private Affiliate $affiliate;
    private int $one;
    private int $two;
    protected function setUp(): void
    {
        $this->s=new Store(':memory:');$this->s->install();$this->commerce=new Commerce($this->s);$this->affiliate=new Affiliate($this->s);
        $this->one=$this->commerce->register('Member One','one@example.test','Long-Password-123!');
        $this->two=$this->commerce->register('Member Two','two@example.test','Long-Password-123!');
    }
    private function code(int $id): string{return $this->s->one('SELECT referral_code FROM users WHERE id=?',[$id])['referral_code'];}
    public function testFacebookIsSavedPerOwnerAndReferralOwnerWinsOverViewer(): void
    {
        $this->affiliate->saveProfile($this->one,$this->profile()+['facebook_url'=>'https://m.facebook.com/Member.One?mibextid=tracking'],$this->one);
        $this->affiliate->saveProfile($this->two,$this->profile()+['facebook_url'=>'https://www.facebook.com/profile.php?id=123456789'],$this->two);
        $owner=$this->affiliate->resolve(['code'=>$this->code($this->one),'expires'=>time()+60],'',$this->two);
        self::assertSame($this->one,$owner['id']);self::assertSame('https://www.facebook.com/Member.One',$owner['facebook_url']);
        self::assertSame('https://www.facebook.com/profile.php?id=123456789',$this->affiliate->owner($this->code($this->two))['facebook_url']);
        $this->affiliate->saveProfile($this->one,$this->profile(),$this->one);
        self::assertSame('https://www.facebook.com/Member.One',$this->affiliate->owner($this->code($this->one))['facebook_url']);
        $this->affiliate->saveProfile($this->one,$this->profile()+['facebook_url'=>''],$this->one);
        self::assertSame('',$this->affiliate->owner($this->code($this->one))['facebook_url']);
    }
    public function testFacebookRejectsExternalAndRedirectLinks(): void
    {
        foreach(['https://facebook.com.evil.test/member','https://facebook.com@evil.test/member','javascript:alert(1)','https://www.facebook.com/l.php?u=https://evil.test','https://www.facebook.com/profile.php?id[]=123','http://facebook.com/member'] as $url){
            try{Affiliate::facebookUrl($url);self::fail('Invalid Facebook URL accepted');}catch(RuntimeException $e){self::assertNotEmpty($e->getMessage());}
        }
    }
    private function profile(string $line='https://lin.ee/MemberOne'): array
    {return ['public_name'=>'Public Member','line_url'=>$line,'affiliate_headline'=>'Welcome to our network','affiliate_bio'=>'Our public introduction.','affiliate_public'=>'1'];}

    public function testOnlyLineDestinationsAreAccepted(): void
    {
        self::assertSame('https://line.me/R/ti/p/%40shop',Affiliate::lineUrl('@shop'));
        self::assertSame('https://line.me/ti/p/abc_DEF-123',Affiliate::lineUrl('https://line.me/ti/p/abc_DEF-123'));
        foreach(['javascript:alert(1)','https://line.me.evil.test/ti/p/abc','https://line.me@evil.test/ti/p/abc','http://lin.ee/abc','https://line.me/evil','https://lin.ee/abc#bad',"https://lin.ee/abc\r\nX: y"] as $url){
            try{Affiliate::lineUrl($url);self::fail('Unsafe destination accepted');}catch(RuntimeException $e){self::assertNotEmpty($e->getMessage());}
        }
    }
    public function testProfileCannotReparentUserOrChangeAnotherMember(): void
    {
        $this->affiliate->saveProfile($this->one,$this->profile()+['sponsor_id'=>$this->two],$this->one);
        self::assertNull($this->s->one('SELECT sponsor_id FROM users WHERE id=?',[$this->one])['sponsor_id']);
        try{$this->affiliate->saveProfile($this->one,$this->profile(),$this->two);self::fail('Member edited another profile');}
        catch(RuntimeException $e){self::assertSame('https://lin.ee/MemberOne',$this->affiliate->owner($this->code($this->one))['line_url']);}
        $this->s->exec("UPDATE users SET role='admin' WHERE id=?",[$this->two]);
        $this->affiliate->saveProfile($this->one,$this->profile('https://lin.ee/Updated'),$this->two);
        self::assertSame('https://lin.ee/Updated',$this->affiliate->owner($this->code($this->one))['line_url']);
    }
    public function testPublishingRequiresContactAndPublicName(): void
    {
        $this->expectException(RuntimeException::class);
        $this->affiliate->saveProfile($this->one,array_replace($this->profile(),['line_url'=>'']),$this->one);
    }
    public function testAttributionWinsOverSignedInUserAndContactUsesLatestProfile(): void
    {
        $context=['code'=>$this->code($this->one),'expires'=>time()+3600];
        $this->affiliate->saveProfile($this->one,$this->profile(),$this->one);
        self::assertSame($this->one,$this->affiliate->resolve($context,'',$this->two)['id']);
        $this->affiliate->saveProfile($this->one,$this->profile('https://lin.ee/NewLine'),$this->one);
        self::assertSame('https://lin.ee/NewLine',$this->affiliate->resolve($context,'',$this->two)['line_url']);
        $this->s->exec('UPDATE users SET active=0 WHERE id=?',[$this->one]);
        self::assertNull($this->affiliate->resolve($context,'',$this->two));
    }
    public function testOpaqueCookieRestoresOwnerAndExpiredCookieCannotSelectAnotherUser(): void
    {
        $token=bin2hex(random_bytes(24));$this->s->insert('referral_tokens',['token_hash'=>hash('sha256',$token),'sponsor_id'=>$this->one,'expires'=>time()+3600]);
        self::assertSame($this->one,$this->affiliate->resolve(null,$token,$this->two)['id']);
        $this->s->exec('UPDATE referral_tokens SET expires=1');
        self::assertNull($this->affiliate->resolve(null,$token,$this->two));
        self::assertNull($this->affiliate->resolve(null,'forged-token',$this->two));
    }
    public function testNetworkNotesOnlyAffectOwnDescendantsAndArePrivatePerOwner(): void
    {
        $child=$this->commerce->register('Child Member','child@example.test','Long-Password-123!',$this->code($this->one));
        $grandchild=$this->commerce->register('Grandchild Member','grand@example.test','Long-Password-123!',$this->code($child));
        $this->affiliate->saveNote($this->one,$grandchild,'contacted','Private follow-up');
        self::assertSame(2,$this->affiliate->stats($this->one)['total']);
        self::assertSame(1,$this->affiliate->stats($this->one)['direct']);
        self::assertSame('Private follow-up',$this->affiliate->network($this->one,'Grandchild')['members'][0]['note']);
        self::assertSame('',$this->affiliate->network($child)['members'][0]['note']);
        $this->expectException(RuntimeException::class);$this->affiliate->saveNote($this->two,$grandchild,'contacted','Not my network');
    }
    public function testNetworkPaginationAndUpgradePreserveMembers(): void
    {
        for($i=0;$i<55;$i++){$this->s->insert('users',['name'=>'Child '.$i,'email'=>'child'.$i.'@example.test','password_hash'=>'unused-test-hash','referral_code'=>strtoupper(bin2hex(random_bytes(6))),'sponsor_id'=>$this->one,'created_at'=>Store::now()]);}
        self::assertSame(55,$this->affiliate->network($this->one)['total']);
        self::assertCount(50,$this->affiliate->network($this->one)['members']);
        self::assertCount(5,$this->affiliate->network($this->one,'',2)['members']);
        $this->affiliate->saveProfile($this->one,$this->profile(),$this->one);$this->s->install();
        self::assertSame('https://lin.ee/MemberOne',$this->affiliate->owner($this->code($this->one))['line_url']);
    }
}
