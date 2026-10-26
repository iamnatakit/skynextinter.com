<?php
namespace App\Libraries\Platform;

use RuntimeException;

/** Activities ordered by the latest published additions to the activities page. */
class ActivityHighlights
{
    public const CATALOG=[
        'ruby-japan-skynext'=>['view'=>'activity_ruby_japan','title'=>'SKYNEXT RUBY — รับสิทธิ์ทริปญี่ปุ่น 5 วัน 3 คืน','excerpt'=>'รายละเอียดแพ็กเกจ RUBY และสิทธิ์ท่องเที่ยวญี่ปุ่น พร้อมเงื่อนไขตามประกาศบริษัท','cover'=>'skynext-ruby-japan-trip.png','when'=>'วันเดินทางตามประกาศผู้จัด'],
        'tokyo-fuji-skynext'=>['view'=>'activity_tokyo_fuji','title'=>'ทริปโตเกียว–ภูเขาไฟฟูจิ 5 วัน 3 คืน','excerpt'=>'ชมฟูจิ เที่ยวโตเกียว แช่ออนเซ็น พร้อมรายละเอียดทริปและกำหนดการเดินทาง','cover'=>'skynext-tokyo-fuji-202611.png','when'=>'ปลายเดือนพฤศจิกายน 2569'],
        'opp-weekly-rewards'=>['view'=>'activity_opp_rewards','title'=>'OPP สกายเน็กซ์ พร้อมกิจกรรมรับของรางวัล','excerpt'=>'เรียนรู้โอกาสธุรกิจออนไลน์ และศึกษาการเข้าร่วมกิจกรรมรับของรางวัลตามเงื่อนไข','cover'=>'skynext-opp-weekly-rewards.png','when'=>'ทุกวันพุธ · 18.30 น.'],
        'yacht-pattaya-skynext'=>['view'=>'activity_yacht_pattaya','title'=>'ทริปล่องเรือยอร์ชพัทยา รุ่นที่ 3','excerpt'=>'พูลวิลล่า–พัทยาปาร์ค 3 วัน 2 คืน พร้อมกำหนดการและข้อมูลเตรียมตัวก่อนเดินทาง','cover'=>'skynext-yacht-generation-3-20261023.png','when'=>'23–25 ตุลาคม 2569'],
        'opp-online-skynext'=>['view'=>'activity_opp_online','title'=>'OPP ออนไลน์ สกายเน็กซ์ — อัปเดตธุรกิจ เสริมพลังทีม','excerpt'=>'เรียนรู้สินค้า แนวทางธุรกิจ และแลกเปลี่ยนประสบการณ์กับเครือข่ายสกายเน็กซ์','cover'=>'skynext-opp-online-20261021.png','when'=>'โปสเตอร์รอบ 21 ตุลาคม 2569'],
    ];
    public function __construct(private Store $s) {}
    public function config(): array
    {
        $saved=json_decode($this->s->setting('home_activity_highlights','{}'),true);
        $mode=is_array($saved)&&in_array($saved['mode']??'', ['latest','random','manual'],true)?$saved['mode']:'latest';
        $ids=is_array($saved['ids']??null)?array_values(array_intersect(array_filter($saved['ids'],'is_string'),array_keys(self::CATALOG))):[];
        return ['mode'=>$mode,'ids'=>$ids];
    }
    public function selected(): array
    {
        $config=$this->config();$ids=array_keys(self::CATALOG);
        if($config['mode']==='manual' && count(array_unique($config['ids']))===2){$ids=$config['ids'];}
        elseif($config['mode']==='random'){shuffle($ids);}
        $result=[];foreach(array_slice($ids,0,2) as $id){$result[$id]=self::CATALOG[$id];}return $result;
    }
    public function save(array $actor,string $mode,array $ids): void
    {
        if(!Access::isAdmin($actor)){throw new RuntimeException('เฉพาะ Admin เท่านั้นที่เลือกกิจกรรมหน้าแรกได้');}
        if(!in_array($mode,['latest','random','manual'],true)){throw new RuntimeException('เลือกรูปแบบการแสดงกิจกรรมให้ถูกต้อง');}
        if($mode==='manual' && (count($ids)!==2 || count(array_unique($ids,SORT_REGULAR))!==2 || array_filter($ids,fn($id)=>!is_string($id)||!isset(self::CATALOG[$id])))){throw new RuntimeException('เลือกกิจกรรม 2 รายการที่แตกต่างกัน');}
        $this->s->transaction(function()use($actor,$mode,$ids){$this->s->set('home_activity_highlights',json_encode(['mode'=>$mode,'ids'=>$mode==='manual'?array_values($ids):[]],JSON_UNESCAPED_UNICODE));$this->s->audit($actor['id'],'homepage_activities_updated','settings',0,$mode);});
    }
}
