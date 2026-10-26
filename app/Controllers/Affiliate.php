<?php
namespace App\Controllers;

use App\Libraries\Platform\{Affiliate as Network,Store};
use CodeIgniter\Exceptions\PageNotFoundException;
use Throwable;

class Affiliate extends Platform
{
    public function promote(string $code)
    {
        $owner=(new Network($this->store))->owner($code);if(!$owner){throw PageNotFoundException::forPageNotFound();}
        if(!(new \App\Libraries\Platform\Membership($this->store))->status((int)$owner['id'])['allowed']){return $this->page('membership_unavailable',['title'=>'โปรไฟล์อยู่ระหว่างต่ออายุ']);}
        $full=$this->store->one('SELECT * FROM users WHERE id=?',[$owner['id']]);
        $path=(new \App\Libraries\Platform\MemberPromotion($this->store))->destination($full);
        $token=$this->remember($owner);
        return $this->cookies(redirect()->to(site_url($path)),$token)->setHeader('Cache-Control','no-store');
    }
    public function photo(string $code)
    {
        $photo=(new \App\Libraries\Platform\ProfilePhoto($this->store))->published($code);
        if(!$photo){throw PageNotFoundException::forPageNotFound();}
        return $this->response->setContentType('image/jpeg')->setHeader('Cache-Control','no-store')->setBody($photo['image']);
    }
    protected function remember(array $owner): string
    {
        $context=['code'=>$owner['referral_code'],'expires'=>time()+30*86400];
        session()->set('referral',$context);session()->set('affiliate_context',$context);
        $token=(string)$this->request->getCookie('sn_affiliate');
        $existing=preg_match('/^[a-f0-9]{48}$/D',$token)?$this->store->one('SELECT token_hash FROM referral_tokens WHERE token_hash=? AND sponsor_id=? AND expires>=?',[hash('sha256',$token),$owner['id'],time()]):null;
        if($existing){
            $this->store->exec('UPDATE referral_tokens SET expires=? WHERE token_hash=?',[$context['expires'],$existing['token_hash']]);
        } else {
            $token=bin2hex(random_bytes(24));
            $this->store->insert('referral_tokens',['token_hash'=>hash('sha256',$token),'sponsor_id'=>$owner['id'],'expires'=>$context['expires']]);
        }
        $seen=session('affiliate_visit');
        if(!is_array($seen) || $seen['code']!==$owner['referral_code'] || $seen['at']<time()-1800){
            $this->store->insert('referral_clicks',['sponsor_id'=>$owner['id'],'created_at'=>Store::now()]);
            session()->set('affiliate_visit',['code'=>$owner['referral_code'],'at'=>time()]);
        }
        return $token;
    }
    protected function cookies($response,string $token)
    {
        foreach(['sn_ref','sn_affiliate'] as $name){$response->setCookie($name,$token,30*86400,'','/','',$this->request->isSecure(),true,'Lax');}
        return $response;
    }
    public function landing(string $code)
    {
        $owner=(new Network($this->store))->owner($code);
        if(!$owner){throw PageNotFoundException::forPageNotFound();}
        if (!(new \App\Libraries\Platform\Membership($this->store))->status((int)$owner['id'])['allowed']) {
            return $this->page('membership_unavailable',['title'=>'โปรไฟล์อยู่ระหว่างต่ออายุ']);
        }
        $token=$this->remember($owner);
        return $this->cookies($this->response,$token)->setBody($this->page('affiliate_landing',['owner'=>$owner,'title'=>$owner['affiliate_public']?$owner['public_name'].' · SKYNEXT INTER':'แนะนำสมาชิก SKYNEXT INTER']));
    }
    public function join(string $code)
    {
        $owner=(new Network($this->store))->owner($code);
        if(!$owner){throw PageNotFoundException::forPageNotFound();}
        if (!(new \App\Libraries\Platform\Membership($this->store))->status((int)$owner['id'])['allowed']) {
            return $this->page('membership_unavailable',['title'=>'โปรไฟล์อยู่ระหว่างต่ออายุ']);
        }
        $token=$this->remember($owner);
        return $this->cookies(redirect()->to(site_url('register')),$token);
    }
    public function contact(?string $code=null)
    { return $this->socialContact($code,'line'); }
    public function facebook(?string $code=null)
    { return $this->socialContact($code,'facebook'); }
    private function socialContact(?string $code,string $channel)
    {
        $network=new Network($this->store);
        if($code!==null){
            $owner=$network->owner($code);
            if(!$owner){throw PageNotFoundException::forPageNotFound();}
        } else {
            $context=session('affiliate_context');
            $owner=$network->resolve(is_array($context)?$context:null,(string)$this->request->getCookie('sn_affiliate'),$this->user['id']??null);
        }
        if ($owner && !(new \App\Libraries\Platform\Membership($this->store))->status((int)$owner['id'])['allowed']) {
            return $this->page('membership_unavailable',['title'=>'โปรไฟล์อยู่ระหว่างต่ออายุ']);
        }
        $field=$channel==='facebook'?'facebook_url':'line_url';
        if($owner && $owner['affiliate_public'] && $owner[$field]){
            try{$line=$channel==='facebook'?Network::facebookUrl($owner[$field]):Network::lineUrl($owner[$field]);}catch(Throwable $e){$line='';}
            if($line){
                $this->store->insert('affiliate_events',['sponsor_id'=>$owner['id'],'kind'=>'contact','created_at'=>Store::now()]);
                return redirect()->to($line)->setHeader('Cache-Control','no-store')->setHeader('Referrer-Policy','no-referrer');
            }
        }
        return $this->page('affiliate_contact',['owner'=>$owner,'title'=>'ติดต่อเรา','contactChannel'=>$channel==='facebook'?'Facebook':'LINE']);
    }
    public function dashboard()
    {
        $network=new Network($this->store);
        return $this->page('affiliate_dashboard',['title'=>'โปรโมทลิงก์และขยายสายงาน','stats'=>$network->stats($this->user['id'])]);
    }
    public function profile()
    {
        if($this->post()){
            try{
                (new Network($this->store))->saveProfile($this->user['id'],$this->request->getPost(),$this->user['id']);
                return redirect()->to(site_url('affiliate'))->with('success','บันทึกโปรไฟล์และ LINE สำหรับลิงก์ Affiliate แล้ว');
            }catch(Throwable $e){return $this->fail($e,'affiliate/profile');}
        }
        return $this->page('affiliate_profile',['title'=>'โปรไฟล์สาธารณะและ LINE']);
    }
    public function network(?string $member=null)
    {
        $network=new Network($this->store);
        if($this->post()){
            try{
                $network->saveNote($this->user['id'],(int)$member,$this->input('stage'),$this->input('note'));
                return redirect()->to(site_url('affiliate/network'))->with('success','บันทึกการติดตามสมาชิกแล้ว');
            }catch(Throwable $e){return $this->fail($e,'affiliate/network');}
        }
        $q=$this->request->getGet('q');$q=is_string($q)?mb_substr(trim($q),0,120):'';
        $page=max(1,(int)$this->request->getGet('page'));$direct=$this->request->getGet('direct')==='1';
        return $this->page('affiliate_network',$network->network($this->user['id'],$q,$page,$direct)+['title'=>'จัดการสายงานของฉัน','stats'=>$network->stats($this->user['id']),'query'=>$q,'currentPage'=>$page,'direct'=>$direct]);
    }
}
