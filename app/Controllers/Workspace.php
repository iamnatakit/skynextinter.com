<?php
namespace App\Controllers;

use App\Libraries\Platform\{MemberWorkspace,Affiliate as Network,Membership,Access,Store,RegistrationPlan};
use CodeIgniter\Exceptions\PageNotFoundException;
use Throwable;

class Workspace extends Affiliate
{
    public function nameVisibility()
    {
        try{(new \App\Libraries\Platform\MemberNamePublication($this->store))->save((int)$this->user['id'],$this->input('publish_full_name')==='1');return redirect()->to(site_url('workspace/profile'))->with('success','บันทึกการแสดงชื่อ–นามสกุลบนหน้าสมาชิกใหม่แล้ว');}
        catch(Throwable $e){return $this->fail($e,'workspace/profile');}
    }
    public function promotion()
    {
        try{(new \App\Libraries\Platform\MemberPromotion($this->store))->save($this->user,$this->input('target'),(int)$this->input('salespage_id','0'));return redirect()->to(site_url('workspace'))->with('success','บันทึกหน้าโปรโมทแล้ว ใช้ลิงก์ส่วนตัวเดิมแชร์ได้ทันที');}
        catch(Throwable $e){return $this->fail($e,'workspace');}
    }
    public function photo(string $code='')
    {
        $photos=new \App\Libraries\Platform\ProfilePhoto($this->store);$owner=(int)$this->user['id'];
        if($this->post()){
            try{
                $this->store->rate('profile-photo:'.$owner,15,600);
                if($this->input('action')==='remove'){$photos->remove($owner);}
                else{$file=$this->request->getFile('photo');if(!$file||!$file->isValid()||$file->getSize()>5*1024*1024){throw new \RuntimeException('แนบรูป JPG, PNG หรือ WebP ไม่เกิน 5 MB');}$photos->save($owner,file_get_contents($file->getTempName()));}
                return redirect()->to(site_url('workspace/profile'))->with('success','บันทึกรูปส่วนตัวแล้ว');
            }catch(Throwable $e){return $this->fail($e,'workspace/profile');}
        }
        $photo=$photos->own($owner);if(!$photo){throw PageNotFoundException::forPageNotFound();}
        return $this->response->setContentType('image/jpeg')->setHeader('Cache-Control','no-store')->setBody($photo['image']);
    }
    private function service(): MemberWorkspace { return new MemberWorkspace($this->store); }
    private function render(string $view,array $data=[]): string
    { return $this->page($view,$data+['workspaceMenus'=>$this->service()->menus($this->user),'workspacePath'=>trim($this->request->getUri()->getRoutePath(),'/')]); }
    private function pageAccess()
    { return $this->service()->canPages($this->user)?null:$this->response->setStatusCode(403)->setBody('ไม่มีสิทธิ์สร้าง Sales Page กรุณาตรวจสอบสิทธิ์และสถานะ Platform'); }
    public function index()
    { if($this->user['role']==='member'){return $this->render('customer_home',['title'=>'ระบบ Member · สมาชิกผู้ซื้อ']);} return $this->render('workspace_home',['title'=>'Workspace ของฉัน','pendingReferral'=>(new \App\Libraries\Platform\PendingReferrals($this->store))->pending((int)$this->user['id']),'promotion'=>(new \App\Libraries\Platform\MemberPromotion($this->store))->preference($this->user['id']),'registration'=>(new RegistrationPlan($this->store))->details($this->user['id']),'pages'=>$this->service()->pages($this->user['id']),'documents'=>$this->service()->documents($this->user['id']),'membership'=>(new Membership($this->store))->status($this->user['id'])]); }
    public function profile()
    {
        if($this->post()){
            try{
                $private=(array)$this->request->getPost('private');
                $public=array_intersect_key((array)$this->request->getPost('public'),array_flip(['public_name','line_url','facebook_url','public_province','affiliate_headline','affiliate_bio','affiliate_public']));
                $public+=['bank_name'=>$this->user['bank_name'],'bank_account'=>$this->user['bank_account'],'bank_account_name'=>$this->user['bank_account_name']];
                $this->store->transaction(function()use($private,$public){$this->service()->saveProfile($this->user['id'],$private);(new Network($this->store))->saveProfile($this->user['id'],$public,$this->user['id']);});
                return redirect()->to(site_url('workspace/profile'))->with('success','บันทึกโปรไฟล์แล้ว');
            }catch(Throwable $e){return $this->fail($e,'workspace/profile');}
        }
        return $this->render('workspace_profile',['title'=>'โปรไฟล์สมาชิก','pendingReferral'=>(new \App\Libraries\Platform\PendingReferrals($this->store))->pending((int)$this->user['id']),'registration'=>(new RegistrationPlan($this->store))->details($this->user['id']),'profile'=>$this->service()->profile($this->user['id']),'fields'=>MemberWorkspace::FIELDS,'sponsor'=>$this->user['sponsor_id']?$this->store->one('SELECT name,referral_code FROM users WHERE id=?',[$this->user['sponsor_id']]):null]);
    }
    public function documents()
    {
        if($this->post()){
            try{$this->store->rate('member-doc:'.$this->user['id'],10,600);$file=$this->request->getFile('document');if(!$file||!$file->isValid()||$file->getSize()>5*1024*1024){throw new \RuntimeException('แนบไฟล์ JPG, PNG หรือ PDF ไม่เกิน 5 MB');}
                $this->service()->upload($this->user['id'],$this->input('kind'),file_get_contents($file->getTempName()),$this->input('consent')==='1');
                return redirect()->to(site_url('workspace/documents'))->with('success','รับเอกสารแล้ว รอผู้ดูแลตรวจสอบ');
            }catch(Throwable $e){return $this->fail($e,'workspace/documents');}
        }
        return $this->render('workspace_documents',['title'=>'เอกสารสมัครสมาชิก','documents'=>$this->service()->documents($this->user['id'])]);
    }
    public function document(string $id)
    {
        try{$doc=$this->service()->download($this->user,(int)$id);}catch(Throwable $e){throw PageNotFoundException::forPageNotFound();}
        return $this->response->setContentType($doc['mime'])->setHeader('Content-Disposition','attachment; filename="'.$doc['name'].'"')->setHeader('Cache-Control','private, no-store, max-age=0')->setHeader('Content-Security-Policy',"default-src 'none'; sandbox")->setBody($doc['bytes']);
    }
    public function pages()
    { if($deny=$this->pageAccess()){return $deny;}return $this->render('workspace_pages',['title'=>'Sales Page ของฉัน','pages'=>$this->service()->pages($this->user['id'])]); }
    public function edit(string $id='0')
    {
        if($deny=$this->pageAccess()){return $deny;}$id=(int)$id;$page=$id?$this->service()->page($this->user['id'],$id):null;
        if($id&&!$page){throw PageNotFoundException::forPageNotFound();}
        if($this->post()){try{$id=$this->service()->savePage($this->user,$id,(array)$this->request->getPost());return redirect()->to(site_url('workspace/salespages/'.$id))->with('success','บันทึก Sales Page แล้ว');}catch(Throwable $e){return $this->fail($e,$id?'workspace/salespages/'.$id:'workspace/salespages/new');}}
        return $this->render('workspace_page_editor',['title'=>$id?'แก้ไข Sales Page':'สร้าง Sales Page','salespage'=>$page,'products'=>$this->store->all('SELECT id,name FROM products WHERE active=1 ORDER BY name')]);
    }
    private function publicData(array $page,array $owner): array
    {
        $ids=json_decode($page['product_ids'],true)?:[];$products=$ids?$this->store->all('SELECT * FROM products WHERE active=1 AND id IN ('.implode(',',array_fill(0,count($ids),'?')).')',$ids):[];
        return ['title'=>$page['title'],'description'=>mb_substr($page['intro'],0,160),'salespage'=>$page,'owner'=>(new \App\Libraries\Platform\ProfilePhoto($this->store))->decorate($owner),'products'=>$products];
    }
    public function preview(string $id)
    { if($deny=$this->pageAccess()){return $deny;}$page=$this->service()->page($this->user['id'],(int)$id);if(!$page){throw PageNotFoundException::forPageNotFound();}return $this->page('salespage_public',$this->publicData($page,$this->user)+['preview'=>true]); }
    public function show(string $code,string $slug)
    {
        $owner=(new Network($this->store))->owner($code);if(!$owner){throw PageNotFoundException::forPageNotFound();}
        $full=$this->store->one('SELECT * FROM users WHERE id=?',[$owner['id']]);$page=$this->store->one("SELECT * FROM member_salespages WHERE user_id=? AND slug=? AND status='published'",[$owner['id'],$slug]);
        if(!$page||!$this->service()->canPages($full)){throw PageNotFoundException::forPageNotFound();}
        $token=$this->remember($owner);return $this->cookies($this->response,$token)->setBody($this->page('salespage_public',$this->publicData($page,$owner)));
    }
    public function review()
    {
        if(!Access::isAdmin($this->user)){return $this->response->setStatusCode(403)->setBody('เฉพาะผู้ดูแลระบบ');}
        $owner=(int)$this->request->getGet('member');$member=$owner?$this->store->one('SELECT id,name,referral_code FROM users WHERE id=?',[$owner]):null;
        $profile=null;if($member){$profile=$this->service()->profile($owner);$this->store->audit($this->user['id'],'private_member_profile_viewed','user',$owner);}
        return $this->render('workspace_review',['title'=>'ตรวจเอกสารสมาชิก','members'=>$this->store->all('SELECT u.id,u.name,u.referral_code,p.submitted_at,COUNT(d.id) documents FROM users u LEFT JOIN member_profiles p ON p.user_id=u.id LEFT JOIN member_documents d ON d.user_id=u.id GROUP BY u.id ORDER BY u.id DESC'),'member'=>$member,'profile'=>$profile,'fields'=>MemberWorkspace::FIELDS,'documents'=>$member?$this->service()->documents($owner):[]]);
    }
    public function reviewDocument(string $id)
    {
        if(!Access::isAdmin($this->user)){return $this->response->setStatusCode(403)->setBody('เฉพาะผู้ดูแลระบบ');}
        try{$this->service()->review($this->user,(int)$id,$this->input('status'),$this->input('note'));return redirect()->to(site_url('workspace/review'))->with('success','บันทึกผลตรวจเอกสารแล้ว');}catch(Throwable $e){return $this->fail($e,'workspace/review');}
    }
}
