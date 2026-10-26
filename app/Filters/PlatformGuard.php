<?php
namespace App\Filters;

use App\Libraries\Platform\Access;
use App\Libraries\Platform\Store;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PlatformGuard implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $store = new Store();
        if (!$store->installed()) { return service('response')->setStatusCode(503)->setBody('Run php spark platform:install first.'); }
        $user = $store->one('SELECT * FROM users WHERE id=? AND active=1', [(int)session('user_id')]);
        if (!$user || (int)$user['auth_version'] !== (int)session('auth_version')) { session()->remove('user_id'); return redirect()->to(site_url('login')); }
        $path = trim($request->getUri()->getRoutePath(), '/');
        if($user['role']==='member'){
            if($path==='workspace/profile' && $request->getMethod()==='GET'){return redirect()->to(site_url('account'));}
            if(str_starts_with($path,'affiliate') || str_starts_with($path,'workspace/') || str_starts_with($path,'platform/billing')){
                return service('response')->setStatusCode(403)->setBody('บัญชี Member ใช้สั่งซื้อสินค้า ดูโปรไฟล์ตนเอง และชำระเงินสำหรับคำสั่งซื้อ');
            }
        }
        // Member workspaces never enter the shared shop administration. Staff roles retain their assigned modules.
        if (($path==='admin'||str_starts_with($path,'admin/')) && in_array($user['role'],['member','affiliate','downline_team','external_team'],true)) {
            if ($request->getMethod() !== 'GET') { return service('response')->setStatusCode(403)->setBody('สมาชิกจัดการได้เฉพาะข้อมูลใน Workspace ของตนเอง'); }
            $destination=match(explode('/',$path)[1]??''){'salespages'=>'workspace/salespages','affiliate_profile'=>'workspace/profile','affiliates'=>'affiliate',default=>'workspace'};
            return redirect()->to(site_url($destination));
        }
        if ($path === 'admin' || str_starts_with($path, 'admin/') || $path === 'affiliate' || str_starts_with($path, 'affiliate/')) {
            $membership = new \App\Libraries\Platform\Membership($store);
            if (!$membership->status((int)$user['id'])['allowed']) {
                $membership->queueExpirations();
                return redirect()->to(site_url('platform/billing'))->with('error','สิทธิ์ใช้งาน Platform หมดอายุหรือถูกระงับ กรุณาตรวจสอบสถานะและต่ออายุ');
            }
        }
        if ($path === 'admin' || str_starts_with($path, 'admin/')) {
            $module = explode('/', $path)[1] ?? 'dashboard';
            if (!Access::can($user, $module)) { return service('response')->setStatusCode(403)->setBody('คุณไม่มีสิทธิ์เข้าถึงส่วนนี้'); }
        }
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
