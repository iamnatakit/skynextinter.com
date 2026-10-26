<?php
$officialDocuments = [
    ['หนังสือรับรองบริษัทฉบับล่าสุด', 'ข้อมูลนิติบุคคล กรรมการ อำนาจลงนาม และที่ตั้งตามรายการปัจจุบัน'],
    ['หนังสือบริคณห์สนธิ (บอจ.2)', 'รายละเอียดหนังสือบริคณห์สนธิที่ยื่นจดทะเบียน'],
    ['รายการจดทะเบียน (บอจ.3 / บอจ.4)', 'รายการจัดตั้งและรายการแก้ไขเพิ่มเติมตามที่มีการยื่นไว้'],
    ['บัญชีรายชื่อผู้ถือหุ้น (บอจ.5)', 'สำเนาบัญชีรายชื่อผู้ถือหุ้นที่นำส่งกรมพัฒนาธุรกิจการค้า'],
    ['งบการเงินล่าสุด', 'งบการเงินที่บริษัทนำส่งในรอบบัญชีที่ต้องการ'],
    ['เอกสารเปลี่ยนชื่อและวัตถุประสงค์', 'รายการจดทะเบียนแก้ไขชื่อบริษัทและวัตถุประสงค์ที่เกี่ยวข้อง'],
];
?>
<link rel="stylesheet" href="<?= base_url('platform/company-documents.css?v='.filemtime(FCPATH.'platform/company-documents.css')) ?>">
<div class="sn-page-content sn-company-documents">
    <header class="sn-docs-hero">
        <p>COMPANY INFORMATION &amp; DOCUMENTS</p>
        <h1>เอกสารบริษัท<br><span>SKYNEXT INTER CO., LTD.</span></h1>
        <p class="sn-docs-lead">ข้อมูลบริษัท แหล่งอ้างอิง และช่องทางขอเอกสารราชการ<br>เพื่อให้คุณศึกษารายละเอียดก่อนติดต่อธุรกิจ</p>
        <a href="#sn-company-registry">ดูข้อมูลจดทะเบียน <span aria-hidden="true">↓</span></a>
    </header>
    <section class="sn-docs-panel" id="sn-company-registry" aria-labelledby="sn-registry-title">
        <p class="sn-docs-eyebrow">01 · CORPORATE REGISTRATION</p>
        <h2 id="sn-registry-title">ข้อมูลจดทะเบียนบริษัท</h2>
        <p class="sn-docs-company">บริษัท สกายเน็กซ์ อินเตอร์ จำกัด <span>SKYNEXT INTER CO., LTD.</span></p>
        <span class="sn-docs-status">สถานะตาม DataForThai: ยังดำเนินกิจการอยู่</span>
        <dl class="sn-docs-facts">
            <div><dt>เลขทะเบียนนิติบุคคล</dt><dd>0605557001314</dd></div>
            <div><dt>วันที่จดทะเบียนเดิม</dt><dd>1 ธันวาคม 2557</dd></div>
            <div><dt>ทุนจดทะเบียน</dt><dd>1,000,000 บาท</dd></div>
            <div><dt>วันที่เปลี่ยนเป็นชื่อปัจจุบัน</dt><dd>11 กุมภาพันธ์ 2569</dd></div>
            <div><dt>ลักษณะธุรกิจ</dt><dd>จำหน่ายสินค้าอุปโภคและสินค้าบริโภคผ่านช่องทางเครือข่ายคอมพิวเตอร์</dd></div>
            <div><dt>ที่ตั้งสำนักงาน</dt><dd>801/180-181 หมู่ที่ 8 ตำบลคูคต อำเภอลำลูกกา จังหวัดปทุมธานี 12130</dd></div>
        </dl>
        <p class="sn-docs-source">อ้างอิง: <a href="https://www.dataforthai.com/company/0605557001314/" target="_blank" rel="noopener noreferrer">DataForThai · ข้อมูลบริษัทเลขทะเบียน 0605557001314 ↗</a><br>ตรวจสอบแหล่งข้อมูลเมื่อ 26 ตุลาคม 2569 ข้อมูลนี้เป็นข้อมูลประกอบการศึกษา ควรตรวจสอบกับหนังสือรับรอง DBD ฉบับล่าสุดก่อนใช้ติดต่อราชการหรือทำธุรกรรม</p>
        <a class="sn-docs-button" href="https://datawarehouse.dbd.go.th/" target="_blank" rel="noopener noreferrer">ตรวจสอบข้อมูลล่าสุดกับ DBD ↗</a>
    </section>
    <section class="sn-docs-panel" aria-labelledby="sn-official-docs-title">
        <p class="sn-docs-eyebrow">02 · OFFICIAL DOCUMENTS</p>
        <h2 id="sn-official-docs-title">เอกสารราชการที่สามารถขอคัดสำเนาได้</h2>
        <p class="sn-docs-description">ขอหนังสือรับรองและสำเนาเอกสารผ่านระบบ DBD e-Service โดยใช้เลขทะเบียนนิติบุคคล <strong>0605557001314</strong> เลือกรายการตามเอกสารที่มีในระบบและเงื่อนไขของกรมพัฒนาธุรกิจการค้า</p>
        <div class="sn-docs-grid">
        <?php foreach ($officialDocuments as $i => [$name, $detail]): ?>
            <article><span class="sn-docs-number"><?= sprintf('%02d', $i + 1) ?></span><h3><?= esc($name) ?></h3><p><?= esc($detail) ?></p><span class="sn-docs-pending">ยังไม่มีไฟล์ฉบับทางการเผยแพร่บนเว็บไซต์</span></article>
        <?php endforeach ?>
        </div>
        <div class="sn-docs-actions"><a class="sn-docs-button" href="https://ebiz.dbd.go.th/eservice-web/index.xhtml" target="_blank" rel="noopener noreferrer">ไปยัง DBD e-Service เพื่อขอเอกสาร ↗</a><a href="https://ecert.dbd.go.th/eservice-inquiry/index.xhtml" target="_blank" rel="noopener noreferrer">ตรวจสอบความถูกต้องของเอกสาร DBD ↗</a></div>
        <p class="sn-docs-source">ลิงก์ข้างต้นเปิดบริการของ DBD ในแท็บใหม่ การขอเอกสารและค่าบริการเป็นไปตามเงื่อนไขของหน่วยงาน</p>
    </section>
    <section class="sn-docs-profile" aria-labelledby="sn-profile-title">
        <div><p class="sn-docs-eyebrow">03 · COMPANY PROFILE 2026</p><h2 id="sn-profile-title">เชื่อมต่อโอกาสทางธุรกิจ<br><span>เติบโตไปด้วยกัน</span></h2><p>รู้จักบริษัท วิสัยทัศน์ พันธกิจ ค่านิยม กลุ่มผลิตภัณฑ์ และแนวทางสนับสนุนสมาชิกผ่านเครื่องมือดิจิทัล</p><p>แพลตฟอร์ม AI และบริการที่เกี่ยวข้องอยู่ระหว่างการพัฒนาและเปิดให้ทดลองใช้ตามแผน ขอบเขตการใช้งานขึ้นอยู่กับฟีเจอร์ที่เปิดให้บริการ</p><a class="sn-docs-button" href="<?= site_url('about/company') ?>">อ่านข้อมูลแนะนำบริษัท →</a></div>
        <aside><span>สถานะเอกสารแนะนำบริษัท</span><h3>ฉบับรอผู้บริหารตรวจทาน</h3><p>ยังไม่มีไฟล์ Word หรือ PDF แนบสำหรับดาวน์โหลด ต้องตรวจทานข้อมูลบริษัท ภาพบุคคล และข้อมูลติดต่อก่อนเผยแพร่ฉบับสมบูรณ์</p></aside>
    </section>
    <section class="sn-docs-panel sn-docs-support"><p class="sn-docs-eyebrow">DOCUMENT REQUEST &amp; SUPPORT</p><h2>ต้องการเอกสารเพิ่มเติม?</h2><p>สอบถามหนังสือรับรองบริษัท เอกสารประกอบธุรกิจ หรือข้อมูลผลิตภัณฑ์ พร้อมระบุประเภทเอกสารและวัตถุประสงค์ที่ต้องการใช้งาน</p><a class="sn-docs-button" href="<?= site_url('contact-us') ?>">ติดต่อสอบถามเอกสาร →</a><a class="sn-docs-ethics" href="<?= site_url('about/business-ethics') ?>">อ่านจรรยาบรรณของนักธุรกิจ</a></section>
</div>
