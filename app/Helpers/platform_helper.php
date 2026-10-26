<?php
function p_money(mixed $value): string { return number_format((int)$value/100,2).' ฿'; }
function p_category(string $value): string
{
    $map=['fertilizer'=>'ปุ๋ยเคมี','balance-fertilizer'=>'ปุ๋ยสูตรเสมอ','fruit-fertilizer'=>'ปุ๋ยไม้ผล','npk-fertilizer'=>'แม่ปุ๋ย','palm-fertilizer'=>'ปุ๋ยปาล์ม','rubber-fertilizer'=>'ปุ๋ยยาง','flake-fertilizer'=>'ปุ๋ยเกล็ด','herbicide'=>'สารกำจัดวัชพืช','glufosinate-ammonium'=>'กลูโฟซิเนต','glyphosate'=>'ไกลโฟเซต','insecticide'=>'สารกำจัดแมลง','organicandchemical-fertilizer'=>'ปุ๋ยอินทรีย์เคมี','organic-chemical-fertilizer'=>'อินทรีย์เคมี','plant-diseases'=>'สารป้องกันโรคพืช','plantshormone'=>'ธาตุอาหารและฮอร์โมน','secondary-macronutrients'=>'ธาตุอาหารรอง','soil'=>'ปรับสภาพดิน'];
    return implode(' / ',array_map(fn($part)=>$map[trim($part)]??trim($part),explode('/',$value)));
}
function p_date(?string $value): string { if(!$value){return '—';} try{return (new DateTimeImmutable($value,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Bangkok'))->format('d/m/Y H:i');}catch(Throwable $e){return $value;} }
function p_label(mixed $value): string
{
    return ['draft'=>'แบบร่าง','published'=>'เผยแพร่','archived'=>'เก็บเข้าคลัง','awaiting_payment'=>'รอชำระเงิน','payment_review'=>'รอตรวจหลักฐาน','gateway_pending'=>'รอผู้ให้บริการยืนยัน','paid'=>'ชำระแล้ว','processing'=>'เตรียมจัดส่ง','shipped'=>'จัดส่งแล้ว','completed'=>'เสร็จสมบูรณ์','cancelled'=>'ยกเลิก','refund_pending'=>'รอคืนเงิน','refunded'=>'คืนเงินแล้ว','pending'=>'รอตรวจ','approved'=>'อนุมัติ','rejected'=>'ไม่ผ่าน','new'=>'ใหม่','contacted'=>'ติดต่อแล้ว','qualified'=>'มีคุณสมบัติ','proposal'=>'เสนอราคา','won'=>'สำเร็จ','lost'=>'ไม่สำเร็จ','open'=>'เปิด','done'=>'เสร็จแล้ว','auto'=>'อัตโนมัติ','human'=>'เจ้าหน้าที่','queued'=>'รอส่ง','sent'=>'ส่งแล้ว','requires_review'=>'ต้องตรวจสอบ','member'=>'สมาชิก','affiliate'=>'Affiliate','downline_team'=>'ทีมงานใต้สายงาน','external_team'=>'ทีมงานต่างสายงาน','sales'=>'ฝ่ายขาย','manager'=>'ผู้จัดการ','admin'=>'ผู้ดูแล','in'=>'ลูกค้า','staff'=>'เจ้าหน้าที่','bot'=>'ผู้ช่วยอัตโนมัติ','percent'=>'เปอร์เซ็นต์','fixed'=>'จำนวนเงิน'][$value]??(string)$value;
}

/** Stable first-party redirect: resolve the same member as the affiliate link. */
function p_affiliate_contact_url(string $channel='line'): string
{
    $store=new \App\Libraries\Platform\Store();
    $route=$channel==='facebook'?'contact-facebook':'contact-us';
    if(!$store->installed()){return site_url($route);}
    $self=null;
    if(session('user_id')){
        $user=$store->one('SELECT id,auth_version FROM users WHERE id=? AND active=1',[(int)session('user_id')]);
        if($user && (int)$user['auth_version']===(int)session('auth_version')){$self=(int)$user['id'];}
    }
    $context=session('affiliate_context');
    $owner=(new \App\Libraries\Platform\Affiliate($store))->resolve(is_array($context)?$context:null,(string)service('request')->getCookie('sn_affiliate'),$self);
    return site_url($route.($owner?'/'.$owner['referral_code']:''));
}
