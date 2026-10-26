<div class="affiliate-nav"><a href="<?= site_url('affiliate') ?>">← ศูนย์ Affiliate</a><a href="<?= site_url('affiliate/network') ?>">จัดการสายงาน</a></div>
<section class="panel affiliate-profile"><h2>โปรไฟล์สำหรับหน้าลิงก์ของคุณ</h2><p class="muted">เมื่อเปิดเผยแพร่ ชื่อที่แสดง ข้อความแนะนำ และ LINE ด้านล่างจะปรากฏต่อผู้ที่เข้าผ่านลิงก์ของคุณ เมนูติดต่อเราจะใช้ LINE เดียวกันนี้</p>
<form method="post" class="stack"><?= csrf_field() ?>
<label>รหัส Affiliate ID (ระบบกำหนดอัตโนมัติ)
<input readonly aria-readonly="true" value="<?= esc($user['referral_code'],'attr') ?>">" pattern="[A-Za-z0-9_-]{3,30}" placeholder="ระบบออกเลขให้อัตโนมัติ เริ่มต้น 0001">
<small class="muted">เริ่มต้น 0001 และเรียงต่อเนื่อง ใช้เป็นรหัสแนะนำและลิงก์ส่วนตัว ไม่สามารถเปลี่ยนเองได้</small>
</label>
<label>ชื่อที่แสดงต่อสาธารณะ<input name="public_name" maxlength="100" value="<?= esc($user['public_name'],'attr') ?>" placeholder="ชื่อร้าน หรือชื่อที่ต้องการให้ลูกค้าเห็น"></label>
<label>ลิงก์ LINE สำหรับติดต่อ<input name="line_url" maxlength="250" value="<?= esc($user['line_url'],'attr') ?>" placeholder="https://line.me/ti/p/... หรือ https://lin.ee/..."></label>
<p class="muted">บัญชีส่วนตัวให้คัดลอกลิงก์เพิ่มเพื่อนจาก LINE; บัญชี OA ใช้ลิงก์เพิ่มเพื่อนหรือ @LINE_ID ได้ การบันทึกลิงก์ไม่ได้เชื่อมบัญชีหรือให้สิทธิ์อ่านข้อความใน LINE</p>
<label>ลิงก์ Facebook สำหรับติดต่อ<input name="facebook_url" maxlength="250" value="<?= esc($user['facebook_url']??'','attr') ?>" placeholder="https://www.facebook.com/ชื่อโปรไฟล์"></label>
<label>หัวข้อหน้าโปรโมท<input name="affiliate_headline" maxlength="180" value="<?= esc($user['affiliate_headline'],'attr') ?>" placeholder="รู้จักสินค้าเกษตรและร่วมเครือข่ายกับเรา"></label>
<label>ข้อความแนะนำตัว / จุดเด่นของคุณ<textarea name="affiliate_bio" maxlength="2000" rows="5"><?= esc($user['affiliate_bio']) ?></textarea></label>
<div style="background:#f8faf8; padding:16px; border-radius:8px; border:1px solid #e0ebe0; margin:10px 0;">
  <h3 style="margin-top:0; color:#1b5e20; font-size:16px;">ข้อมูลบัญชีธนาคารสำหรับรับเงิน / คอมมิชชั่น</h3>
  <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px;">
    <label>ธนาคาร<input name="bank_name" maxlength="100" value="<?= esc($user['bank_name']??'','attr') ?>" placeholder="เช่น กสิกรไทย, ไทยพาณิชย์"></label>
    <label>เลขที่บัญชี<input name="bank_account" maxlength="50" value="<?= esc($user['bank_account']??'','attr') ?>" placeholder="xxx-x-xxxxx-x"></label>
    <label>ชื่อบัญชี<input name="bank_account_name" maxlength="120" value="<?= esc($user['bank_account_name']??'','attr') ?>" placeholder="ชื่อ-นามสกุลเจ้าของบัญชี"></label>
  </div>
</div>
<label>การเผยแพร่โปรไฟล์<select name="affiliate_public"><option value="0" <?= !$user['affiliate_public']?'selected':'' ?>>ยังไม่เผยแพร่ชื่อและ LINE</option><option value="1" <?= $user['affiliate_public']?'selected':'' ?>>เผยแพร่ชื่อ ข้อความ และ LINE บนลิงก์ของฉัน</option></select></label>
<div class="actions"><button>บันทึกโปรไฟล์ Affiliate</button><a class="button secondary" href="<?= site_url('p/'.$user['referral_code']) ?>" target="_blank" rel="noopener">เปิดหน้าโปรโมท ↗</a></div></form></section>
