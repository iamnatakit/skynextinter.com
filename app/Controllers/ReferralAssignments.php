<?php
namespace App\Controllers;
use App\Libraries\Platform\{Access,PendingReferrals};
use Throwable;
class ReferralAssignments extends Platform
{
    public function index()
    {
        if(!Access::isAdmin($this->user)){return $this->response->setStatusCode(403)->setBody('เฉพาะ Admin เท่านั้น');}
        if($this->post()){
            try{(new PendingReferrals($this->store))->assign((int)$this->input('member_id'),$this->input('sponsor_code'),(int)$this->user['id']);return redirect()->to(site_url('admin/referral_assignments'))->with('success','กำหนดผู้แนะนำแล้ว สมาชิกออกจากกลุ่ม 00000');}
            catch(Throwable $e){return $this->fail($e,'admin/referral_assignments');}
        }
        $page=max(1,(int)$this->request->getGet('page'));
        return $this->page('referral_assignments',['title'=>'สมาชิก 00000 · รอ Admin กำหนดผู้แนะนำ',
            'members'=>$this->store->all("SELECT u.id,u.name,u.referral_code,u.created_at FROM pending_referrals p JOIN users u ON u.id=p.user_id WHERE p.assigned_at IS NULL AND u.sponsor_id IS NULL AND u.active=1 AND u.role!='admin' ORDER BY u.id DESC LIMIT 30 OFFSET ".(($page-1)*30)),
            'currentPage'=>$page,
            'history'=>$this->store->all('SELECT u.name,u.referral_code,s.referral_code sponsor_code,p.assigned_at FROM pending_referrals p JOIN users u ON u.id=p.user_id JOIN users s ON s.id=p.assigned_to WHERE p.assigned_at IS NOT NULL ORDER BY p.assigned_at DESC,p.user_id DESC LIMIT 20')]);
    }
}
