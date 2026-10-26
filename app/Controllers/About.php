<?php
namespace App\Controllers;

use App\Libraries\Platform\Store;

class About extends Platform
{
    public function ethics()
    {
        return $this->page('about_business_ethics',[
            'title'=>'จรรยาบรรณของนักธุรกิจ สกายเน็กซ์ อินเตอร์',
            'indexable'=>true,
            'description'=>'แนวปฏิบัติสำหรับสมาชิกสกายเน็กซ์ อินเตอร์ ทั้ง 8 ข้อ เรียบเรียงจากคู่มือธุรกิจหน้า 17 พร้อมภาพอ้างอิงต้นฉบับ',
        ]);
    }
    public function index()
    {
        return $this->company();
    }

    public function company()
    {
        return $this->page('about_company', [
            'title' => 'เกี่ยวกับบริษัท · SKYNEXT INTER',
            'indexable' => true,
            'description' => 'บริษัท สกายเน็กซ์ อินเตอร์ จำกัด ผู้นำนวัตกรรมปัจจัยการผลิตทางการเกษตรครบวงจร เคียงคู่เกษตรกรไทยเพื่อผลผลิตที่ยั่งยืน',
        ]);
    }

    public function marketingPlan()
    {
        return $this->page('about_marketing_plan', [
            'title' => 'แผนการตลาดและระบบพันธมิตร · SKYNEXT INTER',
            'indexable' => true,
            'description' => 'แผนการตลาดสร้างรายได้ที่มั่นคง โปร่งใส และเติบโตอย่างยั่งยืนไปกับระบบพันธมิตร Affiliate สกายเน็กซ์',
        ]);
    }

    public function documents()
    {
        return $this->page('about_documents', [
            'title' => 'เอกสารเกี่ยวกับบริษัทและใบอนุญาต · SKYNEXT INTER',
            'indexable' => true,
            'description' => 'ข้อมูลจดทะเบียน บริษัท สกายเน็กซ์ อินเตอร์ จำกัด เลขทะเบียน 0605557001314 พร้อมแหล่งอ้างอิงและช่องทางขอเอกสารราชการจาก DBD',
        ]);
    }

    public function activities()
    {
        return $this->page('activities', [
            'title' => 'กิจกรรม ทริปทีม และ OPP ออนไลน์ · SKYNEXT INTER',
            'indexable' => true,
            'description' => 'รวมกิจกรรม SKYNEXT INTER ทริปล่องเรือยอร์ชพัทยา 23–25 ตุลาคม 2569 กิจกรรม OPP ออนไลน์ทุกวันพุธ และโครงการเพื่อเกษตรกร พร้อมรายละเอียดตามประกาศผู้จัด',
        ]);
    }
}
