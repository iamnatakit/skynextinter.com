<?php
namespace App\Libraries\Platform;

class Access
{
    public const ADMIN_ONLY = ['users','menus','integrations','settings','audit','user_management','downline_users','external_users','member_documents','home_activities','referral_assignments','annual_promotion'];
    public const TEAM_MENUS = ['dashboard','salespages','affiliates','affiliate_profile','products','orders','payments','customers','leads','tasks','conversations','blog','promotions','analytics','ai'];
    public const MODULES = [
        'dashboard'         => 'ภาพรวมธุรกิจ',
        'salespages'        => 'Sales Page ส่วนตัว',
        'affiliates'        => 'เครือข่าย Affiliate',
        'affiliate_profile' => 'โปรไฟล์ Affiliate',
        'products'          => 'สินค้าและสต็อก',
        'orders'            => 'คำสั่งซื้อ',
        'payments'          => 'ตรวจชำระเงิน',
        'customers'         => 'ลูกค้า CRM',
        'leads'             => 'Lead Pipeline',
        'tasks'             => 'งานติดตามลูกค้า',
        'conversations'     => 'กล่องข้อความ',
        'blog'              => 'จัดการบทความ',
        'annual_promotion' => 'ราคาบริการเว็บไซต์รายปี',
        'referral_assignments' => 'สมาชิก 00000 / กำหนดผู้แนะนำ',
        'home_activities'   => 'กิจกรรมบนหน้าแรก',
        'promotions'        => 'โปรโมชั่น',
        'users'             => 'จัดการผู้ใช้และ Role',
        'user_management'   => 'จัดการผู้ใช้',
        'member_documents'  => 'ใบสมัคร / เอกสารสมาชิก',
        'downline_users'    => 'ผู้ใช้ใต้สายงาน',
        'external_users'    => 'ผู้ใช้ต่างสายงาน',
        'menus'             => 'จัดการเมนูและสิทธิ์',
        'analytics'         => 'สถิติการเข้าเว็บ',
        'ai'                => 'AI และ Chatbot',
        'integrations'      => 'เชื่อมต่อบริการ',
        'settings'          => 'ตั้งค่าร้านค้า',
        'audit'             => 'ประวัติการทำรายการ',
    ];

    public const DEFAULT_ROLE_MENUS = [
        'downline_team' => self::TEAM_MENUS,
        'external_team' => self::TEAM_MENUS,
        'affiliate' => ['affiliates', 'affiliate_profile', 'dashboard','salespages'],
        'sales'     => ['dashboard', 'customers', 'leads', 'tasks', 'conversations','salespages'],
        'manager'   => ['dashboard', 'blog', 'products', 'orders', 'payments', 'promotions', 'customers', 'leads', 'tasks', 'conversations', 'affiliates', 'analytics','salespages'],
        'member'    => [],
    ];

    public const ROLES = [
        'downline_team' => 'ทีมงานใต้สายงาน',
        'external_team' => 'ทีมงานต่างสายงาน',
        'affiliate' => 'Affiliate (พันธมิตร)',
        'sales'     => 'ฝ่ายขาย CRM',
        'manager'   => 'ผู้จัดการร้าน',
        'member'    => 'Member · ผู้ซื้อสินค้า',
    ];

    public static function getRoleMenus(?Store $store = null): array
    {
        try {
            $store ??= new Store();
            $raw = $store->setting('role_menus', '');
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $result = self::DEFAULT_ROLE_MENUS;
                    foreach ($decoded as $role => $menus) {
                        if (in_array($role,['downline_team','external_team'],true)) { continue; }
                        if (is_array($menus)) {
                            $result[$role] = array_values(array_intersect(array_keys(self::MODULES), $menus));
                        }
                    }
                    return $result;
                }
            }
        } catch (\Throwable $e) {}
        return self::DEFAULT_ROLE_MENUS;
    }

    public static function getMenusForUser(array $user, ?Store $store = null): array
    {
        if (empty($user['active'])) {
            return [];
        }
        if ($user['role'] === 'admin') {
            return array_keys(self::MODULES);
        }
        if (in_array($user['role'],['downline_team','external_team'],true)) { return self::TEAM_MENUS; }
        if($user['role']==='member'){return [];}
        $roleMenus = self::getRoleMenus($store);
        $allowed = $roleMenus[$user['role']] ?? [];
        return array_values(array_intersect(array_keys(self::MODULES), $allowed));
    }

    public static function can(array $user, string $module, ?Store $store = null): bool
    {
        if (empty($user['active']) || !isset(self::MODULES[$module])) {
            return false;
        }
        if ($user['role'] === 'admin') {
            return true;
        }
        // Admin-only modules
        if (in_array($module, self::ADMIN_ONLY, true)) {
            return false;
        }
        $allowed = self::getMenusForUser($user, $store);
        return in_array($module, $allowed, true);
    }
    public static function isAdmin(array $user): bool { return !empty($user['active']) && $user['role'] === 'admin'; }
}
