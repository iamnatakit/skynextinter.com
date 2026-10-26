<?php
namespace Tests\Platform;

use App\Libraries\Platform\{Store,Blog,Commerce,Access,Admin};
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BlogTest extends TestCase
{
    private Store $s;
    private Blog $blog;
    private array $actor;
    protected function setUp(): void
    {
        $this->s=new Store(':memory:');$this->s->install();$this->blog=new Blog($this->s);
        $id=(new Commerce($this->s))->register('Blog Editor','editor@example.test','Long-Password-123!');
        $this->s->exec("UPDATE users SET role='admin' WHERE id=?",[$id]);
        $this->actor=$this->s->one('SELECT * FROM users WHERE id=?',[$id]);
    }
    private function data(array $changes=[]): array
    {return $changes+['title'=>'Test article','slug'=>'test-article','excerpt'=>'An original article summary','body'=>str_repeat('Article text. ',10),'category'=>'Farming','cover_image'=>'','status'=>'draft'];}
    public function testDraftPublishArchiveAndRestore(): void
    {
        $id=$this->blog->save(0,$this->data(),$this->actor);
        self::assertSame([],$this->blog->published());self::assertNull($this->blog->article('test-article'));
        $this->blog->save($id,$this->data(['status'=>'published']),$this->actor);
        self::assertCount(1,$this->blog->published());$first=$this->blog->article('test-article');
        $this->blog->archive($id,$this->actor);self::assertNull($this->blog->article('test-article'));
        $this->blog->save($id,$this->data(['status'=>'published','title'=>'Updated title']),$this->actor);
        self::assertSame('Updated title',$this->blog->article('test-article')['title']);
        self::assertSame($first['published_at'],$this->blog->article('test-article')['published_at']);
        self::assertSame($first['author_id'],$this->blog->article('test-article')['author_id']);
    }
    public function testAllPublishedArticlesAppearAndFiltersExcludeDraftsAndFutureDates(): void
    {
        for($i=0;$i<55;$i++){$this->blog->save(0,$this->data(['slug'=>'article-'.$i,'status'=>'published']),$this->actor);}
        $this->blog->save(0,$this->data(['slug'=>'draft-only']),$this->actor);
        $this->s->exec("UPDATE blog SET published_at='2099-01-01 00:00:00' WHERE slug='article-0'");
        self::assertCount(54,$this->blog->published());self::assertCount(54,$this->blog->published('original','Farming'));
        self::assertSame([],$this->blog->published('no-match'));self::assertSame([],$this->blog->published('','Other'));
        self::assertNull($this->blog->article('article-0'));
    }
    public function testDuplicateSlugDoesNotCreateSecondArticle(): void
    {
        $this->blog->save(0,$this->data(),$this->actor);
        try{$this->blog->save(0,$this->data(),$this->actor);self::fail('Duplicate slug accepted');}
        catch(RuntimeException $e){self::assertCount(1,$this->s->all('SELECT * FROM blog'));}
    }
    public function testExperienceCategoryOnlyListsPublishedExperiencesAndKeepsSearchScoped(): void
    {
        $this->blog->save(0,$this->data(['slug'=>'experience-one','title'=>'Product story','category'=>Blog::EXPERIENCE_CATEGORY,'status'=>'published']),$this->actor);
        $this->blog->save(0,$this->data(['slug'=>'general-one','title'=>'Product story','status'=>'published']),$this->actor);
        $this->blog->save(0,$this->data(['slug'=>'experience-draft','category'=>Blog::EXPERIENCE_CATEGORY]),$this->actor);
        $future=$this->blog->save(0,$this->data(['slug'=>'experience-future','category'=>Blog::EXPERIENCE_CATEGORY,'status'=>'published']),$this->actor);
        $this->s->exec("UPDATE blog SET published_at='2099-01-01 00:00:00' WHERE id=?",[$future]);
        $items=$this->blog->published('Product',Blog::EXPERIENCE_CATEGORY);
        self::assertSame(['experience-one'],array_column($items,'slug'));
        self::assertSame([],$this->blog->published('no-match',Blog::EXPERIENCE_CATEGORY));
        self::assertCount(2,$this->blog->published());
        self::assertContains(Blog::EXPERIENCE_CATEGORY,Admin::fields('blog')['category'][2]);
    }
    public function testPermissionsAndAdminIntegration(): void
    {
        self::assertTrue(Access::can($this->actor,'blog'));
        self::assertTrue(Access::can(['active'=>1,'role'=>'manager'],'blog'));
        foreach(['member','sales'] as $role){
            try{$this->blog->save(0,$this->data(),array_replace($this->actor,['role'=>$role]));self::fail('Role bypass');}
            catch(RuntimeException $e){self::assertCount(0,$this->s->all('SELECT * FROM blog'));}
        }
        (new Admin($this->s))->save('blog','save',0,$this->data(['status'=>'published']),$this->actor);
        $result=(new Admin($this->s))->data('blog',1,'Test',1);
        self::assertCount(1,$result['rows']);self::assertSame('Test article',$result['record']['title']);
    }
    public function testInvalidInputCannotPublish(): void
    {
        foreach([['title'=>''],['body'=>'short'],['slug'=>'../bad'],['status'=>'other'],['cover_image'=>'https://example.test/image.jpg'],['body'=>['bad']]] as $changes){
            try{$this->blog->save(0,$this->data($changes),$this->actor);self::fail('Invalid article accepted');}
            catch(RuntimeException $e){self::assertCount(0,$this->s->all('SELECT * FROM blog'));}
        }
    }
    public function testStarterContentContainsEightValidOriginalArticles(): void
    {
        $articles=json_decode(file_get_contents(APPPATH.'Database/blog_seed.json'),true,512,JSON_THROW_ON_ERROR);
        self::assertCount(8,$articles);
        foreach($articles as $article){$this->blog->save(0,$article+['status'=>'published'],$this->actor);}
        self::assertCount(8,$this->blog->published());
    }
}
