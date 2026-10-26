<?php $annualPricing=\App\Libraries\Platform\ProductPricing::apply($annualProduct,false); ?>
<section class="notice info sn-annual-promotion" aria-label="ค่าบริการสำหรับสมาชิกต่างสายงาน">
    <p>ค่าบริการสำหรับสมาชิก SKYNEXT INTER ที่อยู่ต่างสายงาน</p>
    <?php if($annualPricing['public_discount_satang']>0): ?><del><?= number_format($annualPricing['original_price_satang']/100,2) ?> บาท</del><?php endif ?>
    <strong><?= number_format($annualPricing['price_satang']/100, ($annualPricing['price_satang']%100)?2:0) ?> บาท / รอบปี<?= $annualPricing['public_discount_satang']>0?' · ลด '.esc(\App\Libraries\Platform\ProductPricing::discountLabel($annualProduct)):'' ?></strong>
    <p>ระยะเวลาใช้บริการ 1 ปีต่อรอบบริการ ต่ออายุโดยชำระค่าบริการสำหรับรอบปีถัดไป</p>
</section>
