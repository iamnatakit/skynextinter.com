<?php
namespace Tests\Platform;

use App\Libraries\Platform\{Store,Commerce,MemberWorkspace};
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MemberWorkspaceTest extends TestCase
{
    private Store $s;private MemberWorkspace $w;private array $a;private array $b;private string $root;
    protected function setUp():void
    {
        $this->s=new Store(':memory:');$this->s->install();$c=new Commerce($this->s);
        $a=$c->register('User Alpha','alpha@example.test','Long-Password-123!');$b=$c->register('User Beta','beta@example.test','Long-Password-123!');
        $this->a=$this->s->one('SELECT * FROM users WHERE id=?',[$a]);$this->b=$this->s->one('SELECT * FROM users WHERE id=?',[$b]);
        $this->root=WRITEPATH.'testing/workspace-'.bin2hex(random_bytes(8));$this->w=new MemberWorkspace($this->s,$this->root);
    }
    protected function tearDown():void
    { if(is_dir($this->root)){foreach(glob($this->root.'/*') as $file){unlink($file);}rmdir($this->root);} }
    private function pageInput():array{return ['slug'=>'my-page','title'=>'My page','headline'=>'My products','status'=>'published','intro'=>'Plain text','product_ids'=>[]];}
    public function testPagesAreOwnerScopedAndCannotBeReassigned():void
    {
        $id=$this->w->savePage($this->a,0,$this->pageInput()+['user_id'=>$this->b['id']]);
        self::assertNotNull($this->w->page($this->a['id'],$id));self::assertNull($this->w->page($this->b['id'],$id));self::assertCount(0,$this->w->pages($this->b['id']));
        $this->expectException(RuntimeException::class);$this->w->savePage($this->b,$id,$this->pageInput());
    }
    public function testSameSlugIsAllowedForDifferentOwnersButNotOneOwner():void
    {
        $this->w->savePage($this->a,0,$this->pageInput());$this->w->savePage($this->b,0,$this->pageInput());
        self::assertCount(1,$this->w->pages($this->a['id']));self::assertCount(1,$this->w->pages($this->b['id']));
        $this->expectException(RuntimeException::class);$this->w->savePage($this->a,0,$this->pageInput());
    }
    public function testRoleAndSubscriptionControlMenuAndSave():void
    {
        self::assertArrayHasKey('workspace/salespages',$this->w->menus($this->a));
        self::assertArrayNotHasKey('workspace/review',$this->w->menus($this->a));
        $this->s->set('role_menus',json_encode(['affiliate'=>['affiliates']]));
        self::assertArrayNotHasKey('workspace/salespages',$this->w->menus($this->a));
        $this->expectException(RuntimeException::class);$this->w->savePage($this->a,0,$this->pageInput());
    }
    public function testExpiredMembersKeepProfileAndDocumentAccessButCannotPublish():void
    {
        $this->w->menus($this->a);$this->s->exec("UPDATE platform_subscriptions SET trial_ends_at='2000-01-01' WHERE user_id=?",[$this->a['id']]);
        $menus=$this->w->menus($this->a);self::assertArrayHasKey('workspace/profile',$menus);self::assertArrayHasKey('workspace/documents',$menus);self::assertArrayNotHasKey('workspace/salespages',$menus);
        $this->expectException(RuntimeException::class);$this->w->savePage($this->a,0,$this->pageInput());
    }
    public function testPrivateProfileIsEncryptedAndOwnerSeparated():void
    {
        $this->w->saveProfile($this->a['id'],['private_consent'=>'1','first_name'=>'Private Alpha','bank_account'=>'1234567890']);
        $raw=$this->s->one('SELECT private_data FROM member_profiles WHERE user_id=?',[$this->a['id']])['private_data'];
        self::assertStringNotContainsString('Private Alpha',$raw);self::assertStringNotContainsString('1234567890',$raw);
        self::assertSame('Private Alpha',$this->w->profile($this->a['id'])['data']['first_name']);self::assertSame([],$this->w->profile($this->b['id'])['data']);
    }
    public function testDocumentsRequireConsentAndAreEncryptedAndCannotBeDownloadedByAnotherMember():void
    {
        $bytes="%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF";
        try{$this->w->upload($this->a['id'],'identity',$bytes,false);self::fail('Missing consent accepted');}catch(RuntimeException $e){self::assertNotEmpty($e->getMessage());}
        $id=$this->w->upload($this->a['id'],'identity',$bytes,true);
        $row=$this->s->one('SELECT storage_key FROM member_documents WHERE id=?',[$id]);self::assertStringNotContainsString('%PDF',file_get_contents($this->root.'/'.$row['storage_key']));
        self::assertSame($bytes,$this->w->download($this->a,$id)['bytes']);
        $admin=$this->b;$admin['role']='admin';self::assertSame($bytes,$this->w->download($admin,$id)['bytes']);
        $this->expectException(RuntimeException::class);$this->w->download($this->b,$id);
    }
    public function testDocumentReplacementResetsReviewAndAnotherMemberCannotApprove():void
    {
        $bytes="%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF";$id=$this->w->upload($this->a['id'],'bankbook',$bytes,true);
        try{$this->w->review($this->b,$id,'approved','valid document');self::fail('Other member approved');}catch(RuntimeException $e){self::assertNotEmpty($e->getMessage());}
        $admin=$this->b;$admin['role']='admin';$this->w->review($admin,$id,'approved','valid document');
        self::assertSame('approved',$this->w->documents($this->a['id'])[0]['status']);$this->w->upload($this->a['id'],'bankbook',$bytes,true);
        self::assertSame('pending',$this->w->documents($this->a['id'])[0]['status']);self::assertCount(1,$this->w->documents($this->a['id']));
    }
    public function testExecutableUploadsAndInvalidIdentityNumbersAreRejected():void
    {
        try{$this->w->upload($this->a['id'],'identity','<?php echo "not a document";',true);self::fail('PHP accepted');}catch(RuntimeException $e){self::assertNotEmpty($e->getMessage());}
        $this->expectException(RuntimeException::class);$this->w->saveProfile($this->a['id'],['private_consent'=>'1','national_id'=>'1111111111111']);
    }
    public function testApplicationCannotSubmitWithoutRequiredFieldsAndDocuments():void
    {
        $this->expectException(RuntimeException::class);$this->w->saveProfile($this->a['id'],['private_consent'=>'1','submit_application'=>'1','first_name'=>'Alpha']);
    }
    public function testCompleteApplicationSubmitsAndSensitiveChangesResetDocumentReview():void
    {
        $pdf="%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF";
        foreach(['identity','bankbook'] as $kind){$this->w->upload($this->a['id'],$kind,$pdf,true);}
        $digits='123456789012';$sum=0;for($i=0;$i<12;$i++){$sum+=(int)$digits[$i]*(13-$i);}
        $input=['private_consent'=>'1','submit_application'=>'1','first_name'=>'Test','last_name'=>'User','national_id'=>$digits.((11-$sum%11)%10),'birth_date'=>'1990-01-01','mobile'=>'0800000000','address'=>'Test address','subdistrict'=>'Test','district'=>'Test','province'=>'Test','postcode'=>'10000','bank_name'=>'Test bank','bank_account'=>'1234567890','bank_account_name'=>'Test User'];
        $this->w->saveProfile($this->a['id'],$input);self::assertNotNull($this->w->profile($this->a['id'])['submitted_at']);
        $admin=$this->b;$admin['role']='admin';foreach($this->w->documents($this->a['id']) as $doc){$this->w->review($admin,$doc['id'],'approved','Test check');}
        $input['bank_account']='9999999999';$this->w->saveProfile($this->a['id'],$input);
        $docs=array_column($this->w->documents($this->a['id']),'status','kind');self::assertSame('pending',$docs['bankbook']);self::assertSame('approved',$docs['identity']);
    }
    public function testPublicPageEscapesUserHtmlAndDoesNotExposePrivateProfileFields():void
    {
        helper(['url','platform']);$id=$this->w->savePage($this->a,0,$this->pageInput()+['benefits'=>'<script>alert(1)</script>']);
        $page=$this->w->page($this->a['id'],$id);$page['headline']='<script>alert(2)</script>';
        $html=view('platform/salespage_public',['salespage'=>$page,'owner'=>$this->a+['national_id'=>'PRIVATE-ID'],'products'=>[]]);
        self::assertStringNotContainsString('<script>alert(',$html);self::assertStringContainsString('&lt;script&gt;',$html);self::assertStringNotContainsString('PRIVATE-ID',$html);
    }
}
