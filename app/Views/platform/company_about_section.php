<?php
$features=[
 ['number'=>'01','icon'=>'◇','title'=>'ผลิตภัณฑ์คุณภาพ','text'=>'ให้ความสำคัญกับการคัดสรรผลิตภัณฑ์และข้อมูลสินค้าที่ชัดเจน เพื่อให้ผู้บริโภคเลือกได้ตามความต้องการ'],
 ['number'=>'02','icon'=>'AI','title'=>'เทคโนโลยี AI','text'=>'นำเครื่องมือดิจิทัลและ AI มาช่วยนำเสนอข้อมูลและสนับสนุนการทำงาน ตามฟีเจอร์และช่องทางที่เปิดใช้งาน'],
 ['number'=>'03','icon'=>'◎','title'=>'ระบบสนับสนุนสมาชิก','text'=>'ส่งเสริมการเรียนรู้ พร้อมพื้นที่จัดการโปรไฟล์ ข้อมูลสินค้า และเครื่องมือออนไลน์ตามสิทธิ์ของสมาชิก'],
 ['number'=>'04','icon'=>'↗','title'=>'โอกาสทางธุรกิจอย่างยั่งยืน','text'=>'มุ่งสร้างโอกาสอย่างเป็นธรรม สนับสนุนการพัฒนาทักษะและความสัมพันธ์กับลูกค้า โดยยึดข้อมูลและเงื่อนไขที่ชัดเจน'],
];
?>
<link rel="stylesheet" href="<?= base_url('platform/company-about.css?v='.filemtime(FCPATH.'platform/company-about.css')) ?>">
<section class="sn-company-about" id="sn-company-story" aria-labelledby="sn-company-about-title">
    <div class="sn-company-about-intro">
        <figure class="sn-company-about-visual"><div class="sn-company-about-art" role="img" aria-label="ภาพประกอบแนวคิดแบรนด์ SKYNEXT INTER"></div><figcaption>ภาพบุคคลและอาคารใช้ประกอบแนวคิดแบรนด์<br>ไม่ใช้ยืนยันประวัติบุคคลหรือสถานที่ตั้งบริษัท</figcaption></figure>
        <div class="sn-company-about-copy"><span class="sn-company-about-eyebrow">ABOUT US · SKYNEXT INTER</span><h2 id="sn-company-about-title">รู้จักสกายเน็กซ์<br><span>เติบโตบนคุณค่าที่เชื่อมั่น</span></h2><p class="sn-company-about-name">บริษัท สกายเน็กซ์ อินเตอร์ จำกัด</p><p class="sn-company-about-lead">SKYNEXT INTER มุ่งสร้างโอกาสทางธุรกิจอย่างเป็นธรรม ผ่านผลิตภัณฑ์คุณภาพ เทคโนโลยี AI และระบบสนับสนุนสมาชิก เพื่อส่งเสริมการเรียนรู้ การเติบโต และการพัฒนาคุณภาพชีวิตอย่างยั่งยืน</p><p class="sn-company-about-principle">ใส่ใจคุณภาพ · บริการด้วยความจริงใจ · พัฒนาไปด้วยกัน</p><a class="sn-company-about-cta" href="#sn-company-values">รู้จักบริษัทเพิ่มเติม <span aria-hidden="true">→</span></a></div>
    </div>
    <div class="sn-company-about-features"><?php foreach($features as $feature): ?><article><div class="sn-company-about-card-top"><span class="sn-company-about-icon" aria-hidden="true"><?= esc($feature['icon']) ?></span><span><?= esc($feature['number']) ?></span></div><h3><?= esc($feature['title']) ?></h3><p><?= esc($feature['text']) ?></p><i aria-hidden="true"></i></article><?php endforeach ?></div>
    <div class="sn-company-about-info" aria-label="ข้อมูลและแนวทางของบริษัท"><div><span>COMPANY</span><strong>SKYNEXT INTER</strong><p>บริษัท สกายเน็กซ์ อินเตอร์ จำกัด</p></div><div><span>OUR APPROACH</span><strong>เรียนรู้และพัฒนาอย่างต่อเนื่อง</strong><p>ต่อยอดทักษะและประสบการณ์ผ่านการทำงานร่วมกัน</p></div><div><span>OUR COMMITMENT</span><strong>คุณภาพ · เทคโนโลยี · สมาชิก</strong><p>สร้างความสัมพันธ์และเติบโตไปด้วยกันอย่างรับผิดชอบ</p></div></div>
</section>
