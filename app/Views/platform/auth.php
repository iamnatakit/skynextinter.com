<?php if($mode!=='register'): ?><link rel="stylesheet" href="<?= base_url('platform/login.css?v='.filemtime(FCPATH.'platform/login.css')) ?>"><?php endif ?>
<section class="panel auth-card <?= $mode==='register'?'registration-card':'login-card' ?>"><?php if($mode!=='register'): ?><span class="login-brand-label">SKYNEXT INTER</span><?php endif ?><h2><?= $mode==='register'?'สร้างบัญชีใหม่':'ยินดีต้อนรับกลับ' ?></h2><p class="muted"><?= $mode==='register'?'สมัครบัญชีเพื่อสั่งซื้อสินค้าหรือเริ่มต้นเป็น Affiliate':'เข้าสู่ระบบเพื่อจัดการบัญชีของคุณ' ?></p>
<?php if($mode==='register'): ?><div class="notice"><?php if(!empty($registrationReferral)): ?>สมัครผ่านลิงก์แนะนำ: <?= esc($registrationReferral) ?><?php else: ?>กลุ่มผู้แนะนำ: <strong>00000</strong> · รอ Admin กำหนดผู้แนะนำ<?php endif ?></div><?php endif ?>
<form method="post" class="stack"><?= csrf_field() ?>
<?php if($mode==='register'): ?>
<link rel="stylesheet" href="<?= base_url('platform/register.css?v='.filemtime(FCPATH.'platform/register.css')) ?>">
<p class="registration-required-note">กรุณากรอกข้อมูลให้ครบทุกช่อง</p>
<fieldset class="registration-types"><legend>ประเภทสมาชิก</legend>
<label class="check"><input type="radio" name="membership_kind" value="customer" required>Member — สมัครเพื่อสั่งซื้อสินค้า (ราคาปกติ)</label>
<label class="check"><input type="radio" name="membership_kind" value="new" checked required>Affiliate — สมาชิกใหม่</label>
<label class="check"><input type="radio" name="membership_kind" value="existing" required>เป็นสมาชิก SKYNEXT อยู่แล้ว</label>
</fieldset>
<div id="existing-membership" class="registration-existing stack">
<label>รหัสสมาชิก SKYNEXT เดิม *<input name="skynext_member_code" maxlength="40" pattern="[A-Za-z0-9][A-Za-z0-9\/_\-]{0,39}" autocomplete="off" aria-describedby="skynext-code-help"></label>
<small id="skynext-code-help">กรอกรหัสที่ได้รับจาก SKYNEXT รหัสนี้แยกจาก Affiliate ID ของเว็บไซต์</small>
<label class="check"><input type="checkbox" name="annual_opt_in" value="1">มีความประสงค์ทดลองใช้งานระบบ 30 วัน และหลังจากนั้นชำระ Packet 990 บาท/ปี</label>
<p class="muted">ระบบผูกแผนกับโปรไฟล์และกำหนดรายการ Packet 990 เมื่อครบ 30 วัน คุณชำระผ่านหน้าต่ออายุ ไม่มีการหักเงินอัตโนมัติ</p>
</div>
<p id="customer-membership-help" class="notice info" hidden>Member ใช้สั่งซื้อสินค้า ดูและแก้ไขโปรไฟล์ตัวเอง ดูคำสั่งซื้อ และชำระเงินได้ ไม่แสดงราคาสมาชิก และไม่มีเครื่องมือ Affiliate หรือค่าบริการเว็บไซต์รายปี</p>
<p id="new-membership-help" class="notice info">ทดลองใช้ 30 วัน สมาชิกใหม่ภายใต้สายงาน 0005 ซื้อสินค้าอย่างน้อย 100 PV ต่อคำสั่งซื้อและยืนยันชำระเงิน เพื่อรับสิทธิ์สมาชิกใต้สายงาน ส่วนสมาชิกต่างสายงานหรือสมาชิก SKYNEXT เดิมใช้แผน 990 บาท/ปี</p>
<noscript><p>หากเลือกเป็นสมาชิก SKYNEXT อยู่แล้ว ต้องกรอกรหัสสมาชิกและยืนยันแผนรายปีให้ครบ</p></noscript>
<script src="<?= base_url('platform/register.js?v='.filemtime(FCPATH.'platform/register.js')) ?>" defer></script>
<div class="registration-fields"><label><span>ชื่อ–นามสกุล <b class="registration-required">*</b></span><input name="name" required minlength="2" maxlength="120" autocomplete="name" placeholder="กรอกชื่อและนามสกุล"></label><label><span>โทรศัพท์ <b class="registration-required">*</b></span><input name="phone" type="tel" required minlength="9" maxlength="24" autocomplete="tel" inputmode="tel" placeholder="เช่น 0812345678"></label>
<label><span>จังหวัด <b class="registration-required">*</b></span><select name="province" required autocomplete="address-level1"><option value="" selected disabled>เลือกจังหวัดของคุณ</option><?php foreach(\App\Libraries\Platform\ThaiProvinces::ALL as $province): ?><option value="<?= esc($province,'attr') ?>"><?= esc($province) ?></option><?php endforeach ?></select><small>บันทึกในโปรไฟล์ เพื่อแสดงจังหวัดเมื่อเปิดเผยแพร่โปรไฟล์</small></label></div>
<?php endif ?>
<label><span>อีเมล <?php if($mode==='register'): ?><b class="registration-required">*</b><?php endif ?></span><input type="email" name="email" required maxlength="200" autocomplete="email" <?= $mode!=='register'?'placeholder="name@example.com"':'' ?>></label><label><span>รหัสผ่าน <?php if($mode==='register'): ?><b class="registration-required">*</b><?php endif ?></span><input type="password" name="password" required <?= $mode==='register'?'minlength="12" placeholder="อย่างน้อย 12 ตัวอักษร"':'placeholder="กรอกรหัสผ่านของคุณ"' ?> maxlength="200" autocomplete="<?= $mode==='register'?'new-password':'current-password' ?>"></label><?php if($mode==='register'): ?><label class="check"><input type="checkbox" name="consent" value="1" required>ยินยอมให้ใช้ข้อมูลเพื่อจัดการบัญชี คำสั่งซื้อ และบันทึกผู้แนะนำ</label><?php endif ?><button><?= $mode==='register'?'สมัครสมาชิก':'เข้าสู่ระบบ' ?></button></form><p class="muted" style="margin-top:20px"><?= $mode==='register'?'มีบัญชีแล้ว?':'ยังไม่มีบัญชี?' ?> <a href="<?= site_url($mode==='register'?'login':'register') ?>"><?= $mode==='register'?'เข้าสู่ระบบ':'สมัครสมาชิก' ?></a></p></section>
