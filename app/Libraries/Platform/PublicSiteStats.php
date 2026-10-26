<?php
namespace App\Libraries\Platform;

use DateTimeImmutable;
use DateTimeZone;

class PublicSiteStats
{
    public function __construct(private Store $s) {}
    public function visitors(?string $now=null): array
    {
        $day=(new DateTimeImmutable($now??'now',new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Bangkok'))->setTime(0,0);
        $utc=new DateTimeZone('UTC');$from=$day->setTimezone($utc)->format('Y-m-d H:i:s');$until=$day->modify('+1 day')->setTimezone($utc)->format('Y-m-d H:i:s');
        return $this->s->one('SELECT COUNT(*) pageviews,COUNT(DISTINCT visitor) visitors,COUNT(DISTINCT CASE WHEN created_at>=? AND created_at<? THEN visitor END) today FROM visits',[$from,$until]);
    }
    public function recentMembers(): array
    {
        // A full name is public only after a separate owner opt-in; never select the private users.name here.
        return $this->s->all("SELECT u.referral_code,CASE WHEN n.full_name IS NOT NULL THEN n.full_name WHEN u.affiliate_public=1 AND u.public_name<>'' THEN u.public_name ELSE 'สมาชิกใหม่' END display_name,CASE WHEN u.affiliate_public=1 AND u.public_province<>'' THEN u.public_province ELSE 'ไม่ระบุจังหวัด' END province FROM users u LEFT JOIN member_name_publications n ON n.user_id=u.id WHERE u.active=1 AND u.role!='admin' ORDER BY u.created_at DESC,u.id DESC LIMIT 10");
    }
}
