<?php $value=static fn($key,$default='')=>old($key,$product[$key]??$default,false); ?>
<link rel="stylesheet" href="<?= base_url('platform/product-editor.css?v='.filemtime(FCPATH.'platform/product-editor.css')) ?>">
<form class="pe-form" method="post" enctype="multipart/form-data" action="<?= site_url('admin/products/edit/'.$product['id']) ?>">
<?= csrf_field() ?>
<div class="pe-toolbar panel"><div><strong>สินค้า #<?= $product['id'] ?></strong><p>แก้ไขข้อมูล ราคา สต็อก และภาพสินค้าในหน้าเดียว</p></div><div class="actions"><a class="button secondary" href="<?= site_url('shop/product/'.$product['id']) ?>">ดูหน้าสินค้า</a><a href="<?= site_url('admin/products') ?>">รายการสินค้าทั้งหมด</a></div></div>
<div class="pe-columns"><div>
<section class="panel"><h2>ข้อมูลสินค้า</h2><div class="pe-fields">
<label class="pe-full">ชื่อสินค้า *<input name="name" required maxlength="200" value="<?= esc($value('name'),'attr') ?>"></label>
<label>รหัส SKU *<input name="sku" required maxlength="200" value="<?= esc($value('sku'),'attr') ?>"></label>
<label>หมวดหมู่<input name="category" maxlength="200" value="<?= esc($value('category'),'attr') ?>"></label>
<label class="pe-full">คำอธิบายสั้น<textarea name="short_description" rows="4" maxlength="16000"><?= esc($value('short_description')) ?></textarea></label>
<label class="pe-full">รายละเอียดสินค้า<textarea name="description" rows="18" maxlength="16000"><?= esc($value('description')) ?></textarea><small>แยกหัวข้อและย่อหน้าด้วยบรรทัดว่าง เพื่อให้อ่านง่ายบนหน้าสินค้า</small></label>
</div></section>
<section class="panel"><h2>ราคา ส่วนลด และสต็อก</h2><div class="pe-fields">
<label>ราคาปกติ (บาท) *<input name="price" type="number" min="0" step="0.01" required value="<?= esc(old('price',number_format($product['price_satang']/100,2,'.',''),false),'attr') ?>"></label>
<label>ราคาสมาชิก (บาท)<input name="member_price" type="number" min="0" step="0.01" value="<?= esc(old('member_price',isset($product['member_price_satang'])?number_format($product['member_price_satang']/100,2,'.',''):'',false),'attr') ?>"><small>เว้นว่างหากใช้ราคาเดียวกับราคาปกติ</small></label>
<label>ประเภทส่วนลดสินค้า<select name="discount_kind"><option value="" <?= old('discount_kind',$product['discount_kind']??'',false)===''?'selected':'' ?>>ไม่มีส่วนลด</option><option value="percent" <?= old('discount_kind',$product['discount_kind']??'',false)==='percent'?'selected':'' ?>>เปอร์เซ็นต์ (%)</option><option value="fixed" <?= old('discount_kind',$product['discount_kind']??'',false)==='fixed'?'selected':'' ?>>จำนวนเงินบาท</option></select></label>
<label>ส่วนลด (% หรือ บาท)<input name="discount_value" type="number" min="0" step="0.01" value="<?= esc(old('discount_value',($product['discount_kind']??'')==='percent'?number_format(($product['discount_value']??0)/100,2,'.',''):(($product['discount_kind']??'')==='fixed'?number_format(($product['discount_value']??0)/100,2,'.',''):''),false),'attr') ?>"><small>ใช้กับราคาปกติเท่านั้น ไม่หักซ้ำจากราคาสมาชิก</small></label>
<label>คะแนน PV ต่อหน่วย<input name="pv" type="number" min="0" max="1000000" step="1" required value="<?= esc($value('pv',0),'attr') ?>"></label>
<label>สต็อกคงเหลือ<input name="stock" type="number" min="0" max="10000000" step="1" required value="<?= esc($value('stock',0),'attr') ?>"></label>
</div></section></div>
<aside>
<section class="panel"><h2>ภาพสินค้า</h2><div class="pe-image"><?php if($product['image']): ?><img src="<?= base_url($product['image']) ?>" alt="ภาพสินค้าปัจจุบัน"><?php else: ?><span>ยังไม่มีภาพสินค้า</span><?php endif ?></div>
<label>เปลี่ยนภาพสินค้า<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label><p class="muted">JPG, PNG หรือ WebP ไม่เกิน 5 MB หากไม่เลือกไฟล์จะใช้ภาพเดิม</p>
<details><summary>ตำแหน่งไฟล์ภาพในระบบ</summary><label>ไฟล์ภาพ<input name="image" maxlength="200" value="<?= esc($value('image'),'attr') ?>"></label></details></section>
<section class="panel"><h2>การแสดงผลและการขาย</h2>
<label>สถานะสินค้า<select name="active"><option value="1" <?= (string)$value('active')==='1'?'selected':'' ?>>เปิดขาย / แสดงหน้าร้าน</option><option value="0" <?= (string)$value('active')==='0'?'selected':'' ?>>ซ่อนสินค้า</option></select></label>
<label>รูปแบบการขาย<select name="inquiry_only"><option value="0" <?= (string)$value('inquiry_only')==='0'?'selected':'' ?>>สั่งซื้อผ่านตะกร้า</option><option value="1" <?= (string)$value('inquiry_only')==='1'?'selected':'' ?>>สอบถาม / บริการรายปี</option></select></label>
<label>ข้อความปุ่มสอบถาม<input name="inquiry_label" maxlength="200" value="<?= esc($value('inquiry_label'),'attr') ?>"></label><p class="muted">ปุ่มสอบถามเชื่อมกับเจ้าของโปรไฟล์ตามลิงก์แนะนำ</p>
</section></aside></div>
<div class="panel pe-save"><button type="submit" name="after" value="edit">บันทึกการแก้ไข</button><button type="submit" name="after" value="view" class="secondary">บันทึกแล้วดูหน้าสินค้า</button><a href="<?= site_url('shop/product/'.$product['id']) ?>">ยกเลิก</a></div>
</form>
