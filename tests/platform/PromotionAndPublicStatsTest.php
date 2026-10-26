<?php
namespace Tests\Platform;

use App\Libraries\Platform\{Store,Commerce,MemberWorkspace,MemberPromotion,PublicSiteStats,Affiliate};
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PromotionAndPublicStatsTest extends TestCase
{
    private Store $s;private array $a;private array $b;private MemberPromotion $promotion;
    protected function setUp():void
    {
        $this->s=new Store(':memory:');$this->s->install();$c=new Commerce($this->s);
        $a=$c->register('Private Alpha','alpha@example.test','Long-Password-123!');$b=$c->register('Private Beta','beta@example.test','Long-Password-123!');
        $this->a=$this->s->one('SELECT * FROM users WHERE id=?',[$a]);$this->b=$this->s->one('SELECT * FROM users WHERE id=?',[$b]);$this->promotion=new MemberPromotion($this->s);
    }
    private function page(array $owner,string $status='published'):int
    {return (new MemberWorkspace($this->s))->savePage($owner,0,['slug'=>'promo-demo','title'=>'Promo page','headline'=>'Products for you','status'=>$status]);}
    public function testHomepageAndPublishedOwnPageCanBeSelected():void
    {
        self::assertSame('',$this->promotion->destination($this->a));$page=$this->page($this->a);
        $this->promotion->save($this->a,'salespage',$page);self::assertSame('s/0001/promo-demo',$this->promotion->destination($this->a));
        self::assertSame('',$this->promotion->destination($this->b));
        $this->promotion->save($this->a,'home',0);self::assertSame('',$this->promotion->destination($this->a));self::assertNull($this->promotion->preference($this->a['id'])['salespage_id']);
    }
    public function testAnotherOwnersPageCannotBeSelected():void
    {$page=$this->page($this->b);$this->expectException(RuntimeException::class);$this->promotion->save($this->a,'salespage',$page);}
    public function testDraftCannotBeSelected():void
    {$page=$this->page($this->a,'draft');$this->expectException(RuntimeException::class);$this->promotion->save($this->a,'salespage',$page);}
    public function testUnavailableSelectedPageFallsBackToHomepage():void
    {
        $page=$this->page($this->a);$this->promotion->save($this->a,'salespage',$page);
        $this->s->exec("UPDATE member_salespages SET status='archived' WHERE id=?",[$page]);self::assertSame('',$this->promotion->destination($this->a));
        $this->s->exec("UPDATE member_salespages SET status='published' WHERE id=?",[$page]);
        $this->s->exec("UPDATE platform_subscriptions SET trial_ends_at='2020-01-01 00:00:00' WHERE user_id=?",[$this->a['id']]);self::assertSame('',$this->promotion->destination($this->a));
    }
    public function testVisitorCountsDeduplicatePeopleAndUseThailandMidnight():void
    {
        foreach([['one','2026-10-25 16:59:59'],['one','2026-10-25 17:00:00'],['one','2026-10-26 01:00:00'],['two','2026-10-26 10:00:00']] as [$visitor,$at]){$this->s->insert('visits',['visitor'=>$visitor,'path'=>'/','created_at'=>$at]);}
        $stats=(new PublicSiteStats($this->s))->visitors('2026-10-26 12:00:00');
        self::assertSame(4,$stats['pageviews']);self::assertSame(2,$stats['visitors']);self::assertSame(2,$stats['today']);
        self::assertSame(0,(new PublicSiteStats($this->s))->visitors('2026-10-26 18:00:00')['today']);
    }
    public function testRecentMembersExposeOnlyPublicNameAndProvince():void
    {
        $n=new Affiliate($this->s);$n->saveProfile($this->a['id'],['public_name'=>'Public Alpha','public_province'=>'เชียงใหม่','affiliate_public'=>'1','line_url'=>'@alpha'],$this->a['id']);
        $n->saveProfile($this->b['id'],['public_name'=>'Hidden Beta','public_province'=>'Hidden province','affiliate_public'=>'0'],$this->b['id']);
        $rows=(new PublicSiteStats($this->s))->recentMembers();self::assertSame('0002',$rows[0]['referral_code']);self::assertSame('สมาชิกใหม่',$rows[0]['display_name']);self::assertSame('ไม่ระบุจังหวัด',$rows[0]['province']);
        self::assertSame('Public Alpha',$rows[1]['display_name']);self::assertSame('เชียงใหม่',$rows[1]['province']);self::assertArrayNotHasKey('email',$rows[0]);self::assertArrayNotHasKey('name',$rows[0]);
    }
    public function testRecentMembersAreLimitedToTenAndExcludeInactiveAndAdmin():void
    {
        $c=new Commerce($this->s);for($i=0;$i<11;$i++){$c->register('Latest member '.$i,'latest'.$i.'@example.test','Long-Password-123!');}
        $this->s->exec("UPDATE users SET role='admin' WHERE id=13");$this->s->exec('UPDATE users SET active=0 WHERE id=12');
        $rows=(new PublicSiteStats($this->s))->recentMembers();self::assertCount(10,$rows);self::assertSame('0011',$rows[0]['referral_code']);
    }
    public function testRegisteredFullNameRequiresSeparateOwnerConsentAndCanBeRevoked():void
    {
        $publication=new \App\Libraries\Platform\MemberNamePublication($this->s);$stats=new PublicSiteStats($this->s);
        self::assertNull($publication->current($this->b['id']));self::assertSame('สมาชิกใหม่',$stats->recentMembers()[0]['display_name']);
        $publication->save($this->b['id'],true);
        self::assertSame('Private Beta',$stats->recentMembers()[0]['display_name']);self::assertSame('สมาชิกใหม่',$stats->recentMembers()[1]['display_name']);
        self::assertNotEmpty($publication->current($this->b['id'])['consent_at']);
        $this->s->exec('UPDATE users SET name=? WHERE id=?',['New unapproved name',$this->b['id']]);
        self::assertSame('Private Beta',$stats->recentMembers()[0]['display_name']);
        $publication->save($this->b['id'],false);
        self::assertNull($publication->current($this->b['id']));self::assertSame('สมาชิกใหม่',$stats->recentMembers()[0]['display_name']);
    }
}
