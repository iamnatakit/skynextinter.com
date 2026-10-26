<?php $annualWebsite=\App\Libraries\Platform\AnnualWebsitePackage::matches($product); ?>
<?php if(!empty($user) && \App\Libraries\Platform\Access::isAdmin($user)): ?>
<div class="panel actions" aria-label="เครื่องมือ Admin"><strong>จัดการสินค้านี้</strong><a class="button" href="<?= site_url('admin/products/edit/'.$product['id']) ?>">แก้ไขสินค้า</a><a href="<?= site_url('admin/products') ?>">จัดการสินค้าทั้งหมด</a></div>
<?php endif ?>
<article class="sn-product-detail">
    <?php if($annualWebsite): ?><?= view('platform/annual_website_offer',['annualProduct'=>$product]) ?><?php endif ?>
    <div class="sn-product-detail-hero">
        <div class="sn-product-media">
            <?php if ($product['image']): ?><img src="<?= base_url($product['image']) ?>" alt="<?= esc($product['name'], 'attr') ?>" width="600" height="700"><?php else: ?><span>ไม่มีภาพสินค้า</span><?php endif ?>
        </div>
        <div class="sn-product-detail-summary">
            <span class="sn-product-category"><?= esc(p_category($product['category'])) ?></span>
            <h2><?= esc($product['name']) ?></h2>
            <p class="sn-product-sku">SKU <?= esc($product['sku']) ?></p>
            <?= view('platform/product_price', ['product' => $product, 'pricingMember'=>\App\Libraries\Platform\ProductPricing::eligible($user??null)]) ?>
<?= view('platform/product_pv', ['product' => $product]) ?>
            <?php if (!empty($product['short_description'])): ?><p class="sn-product-detail-excerpt"><?= esc($product['short_description']) ?></p><?php endif ?>
            <?php if ($annualWebsite): ?>
                <p class="sn-product-status">บริการรายปี · 1 ปีต่อรอบบริการ · ไม่มีค่าจัดส่ง</p>
                <div class="actions"><a class="sn-product-button" href="<?= site_url('website-package/apply') ?>">สมัครใช้บริการเว็บไซต์รายปี</a><a class="sn-product-button sn-product-button-secondary" href="<?= p_affiliate_contact_url() ?>">สอบถามรายละเอียดแพ็กเกจ</a></div>
                <p class="muted">เข้าสู่ระบบหรือสมัครบัญชี เพื่อตรวจสอบสิทธิ์และชำระผ่านระบบต่ออายุรายปี</p>
            <?php elseif (!empty($product['inquiry_only'])): ?>
                <p class="sn-product-status">ติดต่อเพื่อสอบถามข้อมูลสินค้า</p>
                <a class="sn-product-button" href="<?= p_affiliate_contact_url() ?>"><?= esc(($product['inquiry_label'] ?? '') ?: 'สอบถามสูตรและการใช้งาน') ?></a>
            <?php else: ?>
                <p class="sn-product-status"><?= $product['stock'] > 0 ? 'พร้อมส่ง ' . $product['stock'] . ' ชิ้น' : 'รอยืนยันสต็อก' ?></p>
                <form class="inline-form" method="post" action="<?= site_url('cart') ?>">
                    <?= csrf_field() ?><input type="hidden" name="product_id" value="<?= $product['id'] ?>"><input type="hidden" name="mode" value="add">
                    <label>จำนวน<input type="number" min="1" max="<?= max(1, min(100, $product['stock'])) ?>" name="quantity" value="1" required></label>
                    <button class="sn-product-button" <?= !$product['stock'] ? 'disabled' : '' ?>>เพิ่มลงตะกร้า</button>
                </form>
                <?php if(!empty($product['inquiry_label'])): ?><a class="sn-product-button sn-product-button-secondary" href="<?= p_affiliate_contact_url() ?>"><?= esc($product['inquiry_label']) ?></a><?php endif ?>
            <?php endif ?>
        </div>
    </div>
    <?= view('platform/product_inquiry_details', ['product' => $product,'pricingMember'=>\App\Libraries\Platform\ProductPricing::eligible($user??null)]) ?>
</article>
<?php if (!empty($images)): ?>
<section class="sn-product-detail sn-product-gallery" aria-label="ภาพประกอบสินค้าเพิ่มเติม">
    <h2>ภาพประกอบสินค้าเพิ่มเติม</h2>
    <?php foreach ($images as $image): ?>
    <figure>
        <a href="<?= base_url($image['image']) ?>" aria-label="<?= esc('เปิดภาพขนาดเต็ม: ' . $image['alt'], 'attr') ?>"><img src="<?= base_url($image['image']) ?>" alt="<?= esc($image['alt'], 'attr') ?>" loading="lazy"></a>
        <figcaption><?= esc($image['alt']) ?></figcaption>
    </figure>
    <?php endforeach ?>
</section>
<?php endif ?>
