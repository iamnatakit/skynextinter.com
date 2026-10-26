<?php
namespace Tests\Platform;

use App\Libraries\Platform\{Store,Commerce,Access,ActivityHighlights};
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ActivityHighlightsTest extends TestCase
{
    private Store $s;private ActivityHighlights $h;private array $admin;private array $member;
    protected function setUp():void
    {
        $this->s=new Store(':memory:');$this->s->install();$c=new Commerce($this->s);$a=$c->register('Admin user','admin@example.test','Long-Password-123!');$m=$c->register('Member user','member@example.test','Long-Password-123!');
        $this->s->exec("UPDATE users SET role='admin' WHERE id=?",[$a]);$this->admin=$this->s->one('SELECT * FROM users WHERE id=?',[$a]);$this->member=$this->s->one('SELECT * FROM users WHERE id=?',[$m]);$this->h=new ActivityHighlights($this->s);
    }
    public function testLatestDefaultsToFirstTwoPublishedAdditions():void
    {self::assertSame('latest',$this->h->config()['mode']);self::assertSame(['ruby-japan-skynext','tokyo-fuji-skynext'],array_keys($this->h->selected()));}
    public function testAdminCanSelectTwoDifferentActivitiesInOrder():void
    {$ids=['opp-weekly-rewards','yacht-pattaya-skynext'];$this->h->save($this->admin,'manual',$ids);self::assertSame($ids,array_keys($this->h->selected()));self::assertSame('manual',(new ActivityHighlights($this->s))->config()['mode']);}
    public function testRandomAlwaysHasTwoDistinctValidActivities():void
    {$this->h->save($this->admin,'random',[]);for($i=0;$i<5;$i++){$ids=array_keys($this->h->selected());self::assertCount(2,$ids);self::assertSame($ids,array_values(array_intersect($ids,array_keys(ActivityHighlights::CATALOG))));}}
    public function testNonAdminCannotSaveOrReceiveMenuPermission():void
    {self::assertFalse(Access::can($this->member,'home_activities',$this->s));$this->expectException(RuntimeException::class);$this->h->save($this->member,'latest',[]);}
    public function testInvalidSelectionsDoNotChangeSavedSetting():void
    {
        $this->h->save($this->admin,'manual',['opp-weekly-rewards','yacht-pattaya-skynext']);$before=$this->s->setting('home_activity_highlights');
        foreach([['manual',[]],['manual',['invalid','opp-weekly-rewards']],['manual',['opp-weekly-rewards','opp-weekly-rewards']],['invalid',[]]] as [$mode,$ids]){
            try{$this->h->save($this->admin,$mode,$ids);self::fail('Invalid selection accepted');}catch(RuntimeException $e){self::assertNotEmpty($e->getMessage());}
            self::assertSame($before,$this->s->setting('home_activity_highlights'));
        }
    }
    public function testCorruptConfigurationFallsBackToLatest():void
    {$this->s->set('home_activity_highlights','invalid json');self::assertSame(['ruby-japan-skynext','tokyo-fuji-skynext'],array_keys($this->h->selected()));}
    public function testCatalogViewsImagesAndAnchorsExist():void
    {foreach(ActivityHighlights::CATALOG as $id=>$activity){self::assertFileExists(APPPATH.'Views/platform/'.$activity['view'].'.php');self::assertFileExists(FCPATH.'platform/assets/'.$activity['cover']);self::assertStringContainsString('id="'.$id.'"',file_get_contents(APPPATH.'Views/platform/'.$activity['view'].'.php'));}}
}
