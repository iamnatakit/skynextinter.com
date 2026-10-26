<?php
namespace App\Controllers;

use App\Libraries\Platform\{Access,ActivityHighlights};
use Throwable;

class HomeActivities extends Platform
{
    public function index()
    {
        if(!Access::isAdmin($this->user)){return $this->response->setStatusCode(403)->setBody('เฉพาะ Admin เท่านั้น');}
        $highlights=new ActivityHighlights($this->store);
        if($this->post()){
            try{$ids=$this->request->getPost('activities')??[];if(!is_array($ids)){throw new \RuntimeException('รูปแบบกิจกรรมไม่ถูกต้อง');}$highlights->save($this->user,$this->input('mode'),$ids);return redirect()->to(site_url('admin/home_activities'))->with('success','บันทึกกิจกรรม Section ที่ 3 บนหน้าแรกแล้ว');}
            catch(Throwable $e){return $this->fail($e,'admin/home_activities');}
        }
        return $this->page('home_activities_admin',['title'=>'กิจกรรมหน้าแรก · Section ที่ 3','config'=>$highlights->config(),'activities'=>ActivityHighlights::CATALOG]);
    }
}
