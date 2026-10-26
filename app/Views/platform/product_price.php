<?php $publicPricing=\App\Libraries\Platform\ProductPricing::apply($product,false); $hasSale=$publicPricing['public_discount_satang']>0; ?>
<p class="sn-product-price">
<?php if($hasSale): ?><del><?= number_format($publicPricing['original_price_satang']/100,2) ?> บาท</del><strong><?= number_format($publicPricing['public_price_satang']/100,2) ?> บาท</strong><span class="sn-product-sale-badge">ลด <?= esc(\App\Libraries\Platform\ProductPricing::discountLabel($product)) ?></span>
<?php else: ?><?= !empty($product['inquiry_only']) && (int)$product['price_satang'] === 0 ? 'สอบถามราคา' : number_format($product['price_satang'] / 100, 2) . ' บาท' ?><?php endif ?>
<?= \App\Libraries\Platform\AnnualWebsitePackage::matches($product)?' / ปี':'' ?></p>
<?php if(!empty($pricingMember) && isset($product['member_price_satang']) && !\App\Libraries\Platform\AnnualWebsitePackage::matches($product)): ?>
<p class="sn-member-price">ราคาสมาชิก <strong><?= number_format($product['member_price_satang']/100,2) ?> บาท</strong></p>
<p class="sn-member-price-note">ใช้ราคาสมาชิกในตะกร้าและคำสั่งซื้อ ส่วนลดรายสินค้าใช้กับราคาปกติเท่านั้น</p>
<?php elseif(!empty($product['member_price_satang']) && !\App\Libraries\Platform\AnnualWebsitePackage::matches($product)): ?>
<p class="sn-member-price-note">เข้าสู่ระบบสมาชิกเพื่อซื้อในราคาสมาชิก</p>
<?php endif ?>
