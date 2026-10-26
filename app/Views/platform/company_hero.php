<?php $companyHref=($companyHeroContext??'about')==='home'?site_url('about/company').'#sn-company-story':'#sn-company-story'; ?>
<div class="sn-corporate-hero" role="region" aria-label="SKYNEXT INTER · วิสัยทัศน์ธุรกิจ">
    <div class="sn-corporate-art" aria-hidden="true"><img src="<?= base_url('platform/assets/skynext-corporate-vision.png') ?>" alt="" width="1672" height="941" decoding="async"></div>
    <div class="sn-corporate-shade" aria-hidden="true"></div>
    <div class="sn-corporate-copy">
        <span class="sn-corporate-eyebrow">SKYNEXT INTER · THE NEXT CHAPTER</span>
        <p class="sn-corporate-brand">SkyNext <span>Inter Co., Ltd.</span></p>
        <h2>มุ่งสู่อนาคตธุรกิจ<br>ที่มั่นคง<span>และยั่งยืน</span></h2>
        <p class="sn-corporate-description">ด้วยผลิตภัณฑ์คุณภาพ เครื่องมือ AI<br class="sn-corporate-break"> และโอกาสทางธุรกิจระดับสากล</p>
        <div class="sn-corporate-actions">
            <a class="sn-corporate-primary" href="<?= site_url('register') ?>">สมัครสมาชิก <span aria-hidden="true">↗</span></a>
            <a href="<?= $companyHref ?>">รู้จักบริษัท <span aria-hidden="true">→</span></a>
            <a href="<?= site_url().'#sn-smart-capabilities' ?>">สำรวจเว็บไซต์อัจฉริยะ <span aria-hidden="true">→</span></a>
        </div>
        <div class="sn-corporate-pillars" aria-label="แนวทางของเรา"><span>ผลิตภัณฑ์คุณภาพ</span><span>เครื่องมือ AI</span><span>เติบโตไปด้วยกัน</span></div>
    </div>
    <span class="sn-corporate-caption">ภาพประกอบการนำเสนอแบรนด์</span>
</div>
