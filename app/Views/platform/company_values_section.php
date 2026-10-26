<?php
$missions=['พัฒนาผลิตภัณฑ์คุณภาพ ให้ความสำคัญกับข้อมูลและความต้องการของผู้บริโภค','สร้างระบบการเรียนรู้ เพื่อพัฒนาทักษะและศักยภาพของสมาชิกอย่างต่อเนื่อง','ส่งเสริมผู้นำที่มีจริยธรรม โปร่งใส และรับผิดชอบต่อผู้ร่วมงานและสังคม','นำเทคโนโลยี AI และเครื่องมือดิจิทัลมาช่วยขับเคลื่อนธุรกิจตามขอบเขตบริการ'];
$values=[
 ['title'=>'ความซื่อสัตย์','en'=>'Integrity','icon'=>'M6 12l4 4 8-8','text'=>'ดำเนินธุรกิจอย่างโปร่งใส เป็นธรรม และยึดมั่นในจรรยาบรรณ'],
 ['title'=>'คุณภาพ','en'=>'Quality','icon'=>'M12 3l8 4v6c0 5-8 8-8 8s-8-3-8-8V7l8-4z','text'=>'ใส่ใจการคัดสรรผลิตภัณฑ์และบริการ พร้อมข้อมูลที่ชัดเจนสำหรับลูกค้า'],
 ['title'=>'นวัตกรรม','en'=>'Innovation','icon'=>'M9 18h6m-5 3h4M8 14a6 6 0 1 1 8 0l-1 3H9l-1-3z','text'=>'นำเทคโนโลยี AI และแนวคิดใหม่มาพัฒนาวิธีทำงานอย่างต่อเนื่อง'],
 ['title'=>'การทำงานร่วมกัน','en'=>'Teamwork','icon'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8m8 0a4 4 0 0 1 0 8','text'=>'แบ่งปันความรู้ ช่วยเหลือกัน และสร้างความสัมพันธ์ที่ดีในการทำงาน'],
 ['title'=>'การเติบโตอย่างยั่งยืน','en'=>'Sustainability','icon'=>'M3 17l6-6 4 4 8-10m-6 0h6v6M3 21h18','text'=>'คำนึงถึงคุณค่าระยะยาวของสมาชิก ลูกค้า และสังคม เพื่อพัฒนาไปด้วยกัน'],
];
?>
<link rel="stylesheet" href="<?= base_url('platform/company-values.css?v='.filemtime(FCPATH.'platform/company-values.css')) ?>">
<section class="sn-company-values" id="sn-company-values" aria-labelledby="sn-company-values-title">
    <header class="sn-values-header"><div class="sn-values-brand"><img src="<?= base_url('platform/assets/skynext-logo.png') ?>" alt="SkyNext Inter" width="72" height="64"><span>SKYNEXT INTER<small>OUR PURPOSE &amp; PRINCIPLES</small></span></div><p>VISION · MISSION · CORE VALUES</p><h2 id="sn-company-values-title">วิสัยทัศน์ พันธกิจ<br><span>และค่านิยมองค์กร</span></h2><div class="sn-values-divider" aria-hidden="true"></div></header>
    <div class="sn-values-main">
        <figure class="sn-values-portrait"><div role="img" aria-label="ภาพประกอบแนวคิดแบรนด์ SkyNext"></div><figcaption>มองไปข้างหน้า<br><strong>เติบโตไปด้วยกัน</strong><small>ภาพประกอบแนวคิดแบรนด์</small></figcaption></figure>
        <article class="sn-values-vision"><span class="sn-values-badge" aria-hidden="true">◎</span><p class="sn-values-english">VISION</p><h3>วิสัยทัศน์</h3><p class="sn-values-vision-copy">มุ่งเป็นองค์กรเครือข่ายชั้นนำ สร้างโอกาสทางธุรกิจอย่างยั่งยืน พัฒนาศักยภาพผู้คน และยกระดับคุณภาพชีวิต</p><div class="sn-values-vision-footer">โอกาส · ศักยภาพ · คุณภาพชีวิต</div></article>
        <article class="sn-values-mission"><div class="sn-values-mission-heading"><span class="sn-values-badge" aria-hidden="true">↗</span><div><p class="sn-values-english">MISSION</p><h3>พันธกิจ</h3></div></div><ol><?php foreach($missions as $index=>$mission): ?><li><span aria-hidden="true"><?= sprintf('%02d',$index+1) ?></span><p><?= esc($mission) ?></p></li><?php endforeach ?></ol></article>
    </div>
    <div class="sn-values-core-heading"><span>THE VALUES WE SHARE</span><h3>ค่านิยมที่ยึดมั่นร่วมกัน</h3><p>หลักในการทำงาน ดูแลลูกค้า และพัฒนาองค์กร</p></div>
    <div class="sn-values-core-grid"><?php foreach($values as $value): ?><article><span class="sn-values-core-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $value['icon'] ?>"/></svg></span><h4><?= esc($value['title']) ?></h4><span class="sn-values-core-en"><?= esc($value['en']) ?></span><p><?= esc($value['text']) ?></p></article><?php endforeach ?></div>
</section>
