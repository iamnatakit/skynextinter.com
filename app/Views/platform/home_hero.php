<?php
$slides=[
 ['image'=>'skynext-hero-join.png','title'=>'เริ่มต้นธุรกิจของคุณ พร้อมเครื่องมือออนไลน์','label'=>'ร่วมเป็นตัวแทน','cta'=>'สมัครเป็นตัวแทน','url'=>site_url('register')],
 ['image'=>'skynext-hero-products.png','title'=>'สินค้าดี ราคาจับต้องได้ เติบโตไปกับ SKYNEXT INTER','label'=>'สินค้าของเรา','cta'=>'เลือกชมสินค้า','url'=>site_url('shop')],
 ['image'=>'skynext-hero-smart.png','title'=>'มีเว็บไซต์อัจฉริยะ ช่วยขายให้คุณตลอด 24 ชั่วโมง','label'=>'เว็บไซต์อัจฉริยะ','cta'=>'รู้จักเว็บไซต์อัจฉริยะ','url'=>'#sn-smart-capabilities'],
 ['corporate'=>true,'title'=>'มุ่งสู่อนาคตธุรกิจที่มั่นคงและยั่งยืน','label'=>'เกี่ยวกับบริษัท','cta'=>'รู้จักบริษัท','url'=>site_url('about/company')],
];
?>
<section id="skynextHeroSlider" class="sn-banner-hero" aria-label="SKYNEXT INTER · เรื่องราวและบริการ" aria-roledescription="ภาพสไลด์" tabindex="0" data-interval="3000">
    <h1 class="sn-banner-sr">SKYNEXT INTER · สินค้าเกษตรและเว็บไซต์อัจฉริยะ</h1>
    <div class="sn-banner-stage">
        <?php foreach($slides as $index=>$slide): ?>
        <div class="sn-banner-slide <?= $index===0?'is-active':'' ?> <?= !empty($slide['corporate'])?'sn-banner-corporate':'' ?>" data-cta="<?= esc($slide['cta'],'attr') ?>" data-cta-url="<?= esc($slide['url'],'attr') ?>" id="sn-banner-slide-<?= $index ?>" role="group" aria-roledescription="สไลด์" aria-label="<?= ($index+1).' / '.count($slides) ?>" <?= $index===0?'':'hidden inert' ?>>
            <?php if(!empty($slide['corporate'])): ?><?= view('platform/company_hero',['companyHeroContext'=>'home']) ?><?php else: ?>
            <a href="<?= esc($slide['url'],'attr') ?>" aria-label="<?= esc($slide['title'].' · '.$slide['cta'],'attr') ?>" <?= $index===0?'':'tabindex="-1"' ?>>
                <img src="<?= base_url('platform/assets/'.$slide['image']) ?>" alt="<?= esc($slide['title'],'attr') ?>" width="1672" height="941" <?= $index===0?'fetchpriority="high"':'loading="eager"' ?> decoding="async">
            </a>
            <?php endif ?>
        </div>
        <?php endforeach ?>
    </div>
    <div class="sn-banner-controls">
        <div class="sn-banner-topics" role="group" aria-label="เลือกภาพสไลด์">
        <?php foreach($slides as $index=>$slide): ?><button type="button" class="sn-banner-dot <?= $index===0?'is-active':'' ?>" data-slide="<?= $index ?>" aria-controls="sn-banner-slide-<?= $index ?>" aria-pressed="<?= $index===0?'true':'false' ?>"><span class="sn-banner-number"><?= sprintf('%02d',$index+1) ?></span><span><?= esc($slide['label']) ?></span><i aria-hidden="true"></i></button><?php endforeach ?>
        </div>
        <div class="sn-banner-navigation"><span class="sn-banner-count" aria-live="off">01 / <?= sprintf('%02d',count($slides)) ?></span><button type="button" data-action="previous" aria-label="สไลด์ก่อนหน้า">←</button><button type="button" data-action="pause" aria-label="หยุดสไลด์อัตโนมัติ" aria-pressed="false">Ⅱ</button><button type="button" data-action="next" aria-label="สไลด์ถัดไป">→</button></div>
    </div>
    <div class="sn-banner-mobile-cta"><a href="<?= esc($slides[0]['url'],'attr') ?>"><?= esc($slides[0]['cta']) ?> <span aria-hidden="true">↗</span></a></div>
</section>
