<?php
$slides = [
    [9, 'ผลิตภัณฑ์เสริมอาหารสำหรับผู้หญิง ELA'],
    [6, 'กองทัพสินค้าคุณภาพสูง SkyNext'],
    [8, 'เครื่องดื่มสมุนไพร DOKULA'],
    [11, 'รายการสินค้าคุณภาพสูง สกายเน็กซ์ อินเตอร์'],
    [10, 'สตาร์ ไทเทเนียม สำหรับเครื่องยนต์'],
];
?>
<link rel="stylesheet" href="<?= base_url('platform/blog-slideshow.css?v=' . filemtime(FCPATH . 'platform/blog-slideshow.css')) ?>">
<section class="sn-blog-slideshow" aria-label="สไลด์แนะนำสินค้า SKYNEXT INTER" aria-roledescription="สไลด์โชว์" tabindex="0" data-blog-slideshow>
    <div class="sn-blog-slideshow-stage">
        <?php foreach ($slides as $index => [$number, $label]): ?>
        <div class="sn-blog-slide" role="group" aria-roledescription="สไลด์" aria-label="<?= ($index + 1) . ' จาก ' . count($slides) ?>" <?= $index ? 'hidden' : '' ?>>
            <img src="<?= base_url('platform/assets/blog-slides/slide-' . $number . '.png') ?>" alt="<?= esc($label, 'attr') ?>" width="1280" height="720" <?= $index ? 'loading="lazy"' : 'fetchpriority="high"' ?> draggable="false">
        </div>
        <?php endforeach ?>
        <button type="button" class="sn-blog-slide-arrow sn-blog-slide-prev" aria-label="สไลด์ก่อนหน้า" data-slide-prev hidden>‹</button>
        <button type="button" class="sn-blog-slide-arrow sn-blog-slide-next" aria-label="สไลด์ถัดไป" data-slide-next hidden>›</button>
    </div>
    <div class="sn-blog-slideshow-controls" data-slide-controls hidden>
        <div class="sn-blog-slide-dots" aria-label="เลือกสไลด์">
            <?php foreach ($slides as $index => [$number, $label]): ?>
            <button type="button" class="sn-blog-slide-dot" data-slide-to="<?= $index ?>" aria-label="แสดงสไลด์ <?= ($index + 1) . ': ' . esc($label, 'attr') ?>" <?= !$index ? 'aria-current="true"' : '' ?>></button>
            <?php endforeach ?>
        </div>
        <span class="sn-blog-slide-count" data-slide-count>1 / <?= count($slides) ?></span>
        <button type="button" class="sn-blog-slide-toggle" data-slide-toggle aria-label="หยุดสไลด์อัตโนมัติ">หยุดอัตโนมัติ</button>
    </div>
    <span class="sn-blog-slide-status" aria-live="polite" aria-atomic="true" data-slide-status></span>
</section>
<script src="<?= base_url('platform/blog-slideshow.js?v=' . filemtime(FCPATH . 'platform/blog-slideshow.js')) ?>" defer></script>
