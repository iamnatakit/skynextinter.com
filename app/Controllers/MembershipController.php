<?php
namespace App\Controllers;

use App\Libraries\Platform\{Store,Access,Membership,PlatformBilling,PlatformMailer,PromptPay,SlipVerifier,RegistrationPlan};
use CodeIgniter\Exceptions\PageNotFoundException;
use RuntimeException;
use Throwable;

class MembershipController extends Platform
{
    public function billing()
    {
        $member=new Membership($this->store);$billing=new PlatformBilling($this->store);
        $this->store->exec("UPDATE platform_invoices SET status='expired' WHERE user_id=? AND status='pending' AND expires_at<=?",[$this->user['id'],Store::now()]);
        $invoice=$this->store->one("SELECT * FROM platform_invoices WHERE user_id=? AND status IN ('pending','checking','review') ORDER BY id DESC",[$this->user['id']]);
        return $this->page('membership_billing',['annualOffer'=>(new \App\Libraries\Platform\AnnualWebsiteOffer($this->store))->current(),'title'=>'สิทธิ์ใช้งานและต่ออายุ Platform','membership'=>$member->status($this->user['id']),
            'registration'=>(new RegistrationPlan($this->store))->details($this->user['id']),'invoice'=>$invoice,'ready'=>$billing->ready(),'payee'=>$this->store->setting('platform_payee_name'),
            'qr'=>$invoice && $invoice['status']==='pending' && $billing->ready() ? PromptPay::payload($this->store->setting('platform_promptpay'),$invoice['amount_satang']) : '',
            'provider'=>$this->store->setting('platform_slip_provider','easyslip'),
            'slip'=>$invoice?$this->store->one('SELECT status,note FROM platform_slips WHERE invoice_id=?',[$invoice['id']]):null,
            'history'=>$this->store->all('SELECT number,amount_satang,status,created_at,paid_at FROM platform_invoices WHERE user_id=? ORDER BY id DESC LIMIT 20',[$this->user['id']])]);
    }
    public function create()
    {
        try {
            (new PlatformBilling($this->store))->invoice((int)$this->user['id']);
            return redirect()->to(site_url('platform/billing'))->with('success','สร้างรายการต่ออายุ 1 ปีแล้ว กรุณาสแกน QR และส่งสลิป');
        } catch (Throwable $e) { return $this->fail($e,'platform/billing'); }
    }
    public function upload(string $invoice)
    {
        $filename=null;
        try {
            $this->store->rate('platform-slip:'.$this->user['id'],8,600);
            $bill=$this->store->one('SELECT id FROM platform_invoices WHERE id=? AND user_id=?',[(int)$invoice,$this->user['id']]);
            if (!$bill) { throw new RuntimeException('ไม่พบรายการชำระเงินของคุณ'); }
            $file=$this->request->getFile('slip');
            if (!$file || !$file->isValid() || $file->getSize()>4*1024*1024) { throw new RuntimeException('ใช้สลิป JPG, PNG หรือ WebP ไม่เกิน 4 MB'); }
            $mime=$file->getMimeType();$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
            if (!$ext || !getimagesize($file->getTempName())) { throw new RuntimeException('ใช้ไฟล์ภาพสลิปจริง'); }
            $hash=hash_file('sha256',$file->getTempName());$filename=bin2hex(random_bytes(16)).'.'.$ext;
            $file->move(WRITEPATH.'platform_slips',$filename);
            $billing=new PlatformBilling($this->store);
            $id=$billing->submit((int)$invoice,(int)$this->user['id'],['slip'=>$filename,'mime'=>$mime,'slip_hash'=>$hash]);
            $filename=null;
            $result=$billing->check($id);
            return redirect()->to(site_url('platform/billing'))->with('success',$result==='verified'?'ตรวจสลิปสำเร็จ เปิดสิทธิ์ใช้งานรายปีแล้ว':'รับสลิปแล้ว ดูสถานะตรวจสอบได้ที่หน้านี้');
        } catch (Throwable $e) {
            if ($filename && is_file(WRITEPATH.'platform_slips/'.$filename)) { unlink(WRITEPATH.'platform_slips/'.$filename); }
            return $this->fail($e,'platform/billing');
        }
    }
    public function admin(string $group='all')
    {
        if (!Access::isAdmin($this->user)) { return $this->response->setStatusCode(403)->setBody('เฉพาะผู้ดูแลระบบ'); }
        $member=new Membership($this->store);$query=trim((string)$this->request->getGet('q'));
        $users=[];$counts=['downline'=>0,'external'=>0,'expired'=>0,'paid'=>0];
        foreach ($this->store->all('SELECT id,name,email,role,active,referral_code,sponsor_id,created_at FROM users ORDER BY id DESC') as $u) {
            $u['membership']=$member->status($u['id']);
            if ($u['role']==='admin') { continue; }
            $counts[$u['membership']['type']]++;
            if (in_array($u['membership']['state'],['expired','locked'],true)) {$counts['expired']++;}
            if ($u['membership']['state']==='paid') {$counts['paid']++;}
            if (($group==='all' || $group===$u['membership']['type']) && ($query==='' || mb_stripos($u['name'].' '.$u['email'].' '.$u['referral_code'],$query)!==false)) {$users[]=$u;}
        }
        $module=['all'=>'user_management','downline'=>'downline_users','external'=>'external_users'][$group]??'user_management';
        $keys=['platform_promptpay','platform_payee_name','platform_slip_provider','slip_receiver_bank','slip_receiver_account','slipok_branch_id',
            'platform_smtp_host','platform_smtp_user','platform_smtp_port','platform_smtp_crypto','platform_smtp_from'];
        $settings=[];foreach($keys as $key){$settings[$key]=$this->store->setting($key);}
        return $this->page('membership_admin',['title'=>Access::MODULES[$module],'module'=>$module,'store'=>$this->store,'group'=>$group,'users'=>$users,'counts'=>$counts,
            'query'=>$query,'roots'=>$member->roots(),'settings'=>$settings,
            'allUsers'=>$this->store->all('SELECT id,name,email,referral_code FROM users WHERE active=1 ORDER BY name'),
            'paymentReady'=>(new PlatformBilling($this->store))->ready(),'mailReady'=>(new PlatformMailer($this->store))->ready(),
            'invoices'=>$this->store->all('SELECT i.*,u.name,u.email,s.id slip_id,s.status slip_status,s.note FROM platform_invoices i JOIN users u ON u.id=i.user_id LEFT JOIN platform_slips s ON s.invoice_id=i.id ORDER BY i.id DESC LIMIT 50'),
            'emails'=>$this->store->all('SELECT id,recipient,subject,status,attempts,created_at,sent_at,error FROM platform_emails ORDER BY id DESC LIMIT 30')]);
    }
    public function action(string $id='0')
    {
        if (!Access::isAdmin($this->user)) { return $this->response->setStatusCode(403)->setBody('เฉพาะผู้ดูแลระบบ'); }
        try {
            $action=$this->input('action');$member=new Membership($this->store);
            if ($action==='config') {
                $this->saveConfig($member);
            } elseif ($action==='lock' || $action==='unlock') {
                $member->setLock((int)$id,$action==='lock',(int)$this->user['id']);
            } elseif ($action==='extend_trial') {
                $days=$this->input('trial_days');
                if (!ctype_digit($days) || (int)$days<1 || (int)$days>3650) { throw new RuntimeException('จำนวนวันที่ขยายต้องอยู่ระหว่าง 1 ถึง 3650 วัน'); }
                $member->extendTrial((int)$id,(int)$days,(int)$this->user['id']);
            } elseif ($action==='retry_slip') {
                $billing=new PlatformBilling($this->store);$billing->retry((int)$id,(int)$this->user['id']);$billing->check((int)$id);
            } elseif ($action==='queue_notices') {
                $member->queueExpirations();
            } elseif ($action==='retry_email') {
                $this->store->exec("UPDATE platform_emails SET status='queued',attempts=0,next_attempt_at=0 WHERE id=? AND status='review'",[(int)$id]);
                $this->store->audit($this->user['id'],'platform_email_retry','platform_emails',(int)$id);
            } else { throw new RuntimeException('คำสั่งไม่ถูกต้อง'); }
            return redirect()->to(site_url('admin/user_management'))->with('success','บันทึกการจัดการผู้ใช้และ Platform แล้ว');
        } catch (Throwable $e) { return $this->fail($e,'admin/user_management'); }
    }
    private function saveConfig(Membership $member): void
    {
        $promptpay=$this->input('platform_promptpay');
        if ($promptpay!=='') { PromptPay::payload($promptpay,Membership::ANNUAL_SATANG); }
        $provider=$this->input('platform_slip_provider','easyslip');
        if (!in_array($provider,['easyslip','slipok'],true)) { throw new RuntimeException('เลือก EasySlip หรือ SlipOK'); }
        foreach (['slip_receiver_bank'=>'/^\d{3}$/D','slip_receiver_account'=>'/^\d{6,20}$/D','slipok_branch_id'=>'/^\d{1,20}$/D'] as $key=>$pattern) {
            if ($this->input($key)!=='' && !preg_match($pattern,$this->input($key))) { throw new RuntimeException('รหัสธนาคาร เลขบัญชี หรือ Branch ID ไม่ถูกต้อง'); }
        }
        $from=$this->input('platform_smtp_from');
        if ($from!=='' && !filter_var($from,FILTER_VALIDATE_EMAIL)) { throw new RuntimeException('อีเมลผู้ส่งไม่ถูกต้อง'); }
        $port=$this->input('platform_smtp_port','587');
        if (!ctype_digit($port) || (int)$port<1 || (int)$port>65535) { throw new RuntimeException('พอร์ต SMTP ไม่ถูกต้อง'); }
        if (!in_array($this->input('platform_smtp_crypto','tls'),['tls','ssl'],true)) { throw new RuntimeException('เลือกการเข้ารหัส SMTP'); }
        $roots=$this->request->getPost('free_roots')??$member->roots();
        if (!is_array($roots)) { throw new RuntimeException('ต้นสายไม่ถูกต้อง'); }
        $keys=['platform_promptpay','platform_payee_name','slip_receiver_bank','slip_receiver_account','slipok_branch_id','platform_smtp_host','platform_smtp_user','platform_smtp_from'];
        foreach ($keys as $key) {
            if (strlen($this->input($key))>250 || preg_match('/[\r\n]/',$this->input($key))) { throw new RuntimeException('ค่าตั้งค่ายาวเกินไปหรือมีอักขระไม่ถูกต้อง'); }
        }
        $this->store->transaction(function() use ($member,$roots,$keys,$provider,$port) {
            $member->saveRoots($roots,(int)$this->user['id']);
            foreach($keys as $key){$this->store->set($key,$this->input($key));}
            $this->store->set('platform_slip_provider',$provider);$this->store->set('platform_smtp_port',$port);
            $this->store->set('platform_smtp_crypto',$this->input('platform_smtp_crypto','tls'));
            foreach(['easyslip_api_key','slipok_api_key','platform_smtp_pass'] as $key){
                if($this->input($key)!==''){
                    if(strlen($this->input($key))>500 || preg_match('/[\r\n]/',$this->input($key))){throw new RuntimeException('ข้อมูลลับไม่ถูกต้อง');}
                    $this->store->set($key,$this->input($key));
                }
            }
            $this->store->audit($this->user['id'],'platform_config_updated','settings',0,'Updated roots, receiver, verifier and SMTP; secrets omitted.');
        });
    }
    public function slip(string $id)
    {
        if (!Access::isAdmin($this->user)) { return $this->response->setStatusCode(403)->setBody('เฉพาะผู้ดูแลระบบ'); }
        $row=$this->store->one('SELECT slip,mime FROM platform_slips WHERE id=?',[(int)$id]);
        if (!$row || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D',$row['slip']) || !is_file(WRITEPATH.'platform_slips/'.$row['slip'])) { throw PageNotFoundException::forPageNotFound(); }
        return $this->response->setHeader('Cache-Control','no-store')->setHeader('Content-Disposition','inline')->setContentType($row['mime'])->setBody(file_get_contents(WRITEPATH.'platform_slips/'.$row['slip']));
    }
}
