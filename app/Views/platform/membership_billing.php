<?php use App\Libraries\Platform\Membership; $annualPlan=!empty($registration['annual_opt_in']);$scheduledTrial=$annualPlan && $membership['state']==='trial'; ?>
<div class="sn-membership">
<?= view('platform/registration_plan',['registration'=>$registration??null]) ?>
<section class="panel">
    <span class="badge"><?= esc(Membership::label($membership['state'])) ?></span>
    <h2>สิทธิ์ใช้งาน Platform ของคุณ</h2>
    <p>Role ปัจจุบัน: <strong><?= esc(\App\Libraries\Platform\Access::ROLES[$user['role']]??$user['role']) ?></strong></p>
    <?php if(in_array($membership['state'],['admin','free'],true)): ?>
        <p>บัญชีนี้ใช้งานได้โดยไม่เสียค่าบริการรายปี</p>
    <?php elseif($membership['state']==='locked'): ?>
        <p>ผู้ดูแลระงับสิทธิ์ใช้งาน กรุณาติดต่อผู้ดูแลเพื่อคืนสิทธิ์</p>
    <?php else: ?>
        <p>ทดลองใช้ 30 วัน ถึง <?= esc(p_date($membership['trial_ends_at'])) ?></p>
        <?php if($membership['paid_until']): ?><p>สิทธิ์รายปีถึง <?= esc(p_date($membership['paid_until'])) ?></p><?php endif ?>
        <?php if(!$membership['allowed']): ?><p class="notice error">ระบบจัดการถูกล็อก ข้อมูลของคุณยังอยู่ ต่ออายุเพื่อกลับมาใช้งาน</p><?php endif ?>
        <?php if(!$annualPlan): ?><div class="sn-membership-options">
            <?php if($membership['type']==='downline'): ?><div class="panel"><h3>สมาชิกใหม่ 100 PV · ใต้สายงาน 0005</h3><p>ซื้อสินค้ารวมอย่างน้อย 100 PV ต่อคำสั่งซื้อ และชำระสำเร็จ ระบบเปลี่ยนเป็นทีมงานใต้สายงาน ใช้งานส่วนธุรกิจได้ครบโดยไม่เสียค่าบริการ Platform รายปี</p><a class="button" href="<?= site_url('shop') ?>">เลือกซื้อสินค้าให้ครบ 100 PV</a><p class="muted">รายการที่เปิดให้สอบถามราคา ให้ติดต่อผู้แนะนำเพื่อจัดคำสั่งซื้อและยืนยันการชำระเงิน</p></div>
            <?php endif ?><div class="panel"><h3>ทีมงานต่างสายงาน · <?= number_format($annualOffer['amount_satang']/100,2) ?> บาท/ปี</h3><p>ชำระค่า Platform และผ่านการตรวจสลิป ระบบเปลี่ยนเป็นทีมงานต่างสายงาน ใช้ส่วนธุรกิจได้ครบ 1 ปี ยกเว้นสิทธิ์ Admin</p></div>
        </div><?php endif ?>
        <h3>ต่ออายุ 1 ปี · <?= number_format($annualOffer['amount_satang']/100) ?> บาท</h3>
        <p>สแกน QR ด้วยแอปธนาคาร แล้วแนบสลิป ระบบจะเปิดสิทธิ์เมื่อยืนยันยอด ผู้รับ และธุรกรรมสำเร็จ</p>
        <?php if(!$ready): ?><p class="notice info">กำลังรอผู้ดูแลตั้งค่าบัญชีพร้อมเพย์และบริการตรวจสลิป กรุณาติดต่อผู้ดูแลก่อนชำระเงิน</p><?php endif ?>
        <?php if($scheduledTrial): ?><p class="notice info">รายการ Packet 990 จะเปิดให้ชำระเมื่อทดลองใช้ครบ 30 วัน</p><?php elseif(!$invoice): ?><form method="post" action="<?= site_url('platform/billing/create') ?>"><?= csrf_field() ?><button <?= !$ready?'disabled':'' ?>>สร้าง QR ต่ออายุ <?= number_format($annualOffer['amount_satang']/100) ?> บาท</button></form><?php endif ?>
    <?php endif ?>
    <div class="actions"><a class="button secondary" href="<?= site_url('account') ?>">บัญชีของฉัน</a><?php if($membership['allowed']): ?><a class="button" href="<?= site_url('admin') ?>">เข้าสู่ระบบจัดการ</a><?php endif ?></div>
</section>
<?php if($invoice): ?>
<section class="panel">
    <h2>รายการ <?= esc($invoice['number']) ?></h2>
    <p>ยอดชำระ <strong><?= number_format($invoice['amount_satang']/100,2) ?> บาท</strong> · <?= esc(['pending'=>'รอชำระเงิน','checking'=>'กำลังตรวจสลิป','review'=>'รอผู้ดูแลตรวจสอบ'][$invoice['status']]??$invoice['status']) ?></p>
    <?php if($qr): ?>
        <div class="sn-payment-grid"><div>
            <div class="sn-payment-qr" data-payment-qr="<?= esc($qr,'attr') ?>" aria-label="QR พร้อมเพย์ตามยอดรายการชำระ"></div>
            <?php if($payee): ?><p>ผู้รับ: <?= esc($payee) ?></p><?php endif ?>
            <p>ตรวจชื่อผู้รับในแอปธนาคารให้ตรงก่อนยืนยันโอน</p>
            <p class="muted">QR ใช้สำหรับรายการนี้ ถึง <?= esc(p_date($invoice['expires_at'])) ?></p>
        </div><form method="post" enctype="multipart/form-data" class="stack" action="<?= site_url('platform/billing/slip/'.$invoice['id']) ?>">
            <?= csrf_field() ?><h3>แนบสลิปเพื่อเปิดสิทธิ์</h3>
            <label>ภาพสลิปจากธนาคาร<input type="file" name="slip" accept="image/jpeg,image/png,image/webp" required></label>
            <p>ระบบส่งสลิปไปตรวจสอบกับ <?= esc($provider==='slipok'?'SlipOK':'EasySlip') ?> รองรับ JPG, PNG และ WebP ไม่เกิน 4 MB</p>
            <button>ส่งสลิปและตรวจสอบอัตโนมัติ</button>
        </form></div>
        <script src="<?= base_url('platform/vendor/qrcodegen.js') ?>" defer></script><script src="<?= base_url('platform/platform-billing.js') ?>" defer></script>
    <?php elseif($slip): ?><p class="notice info"><?= esc($slip['note']?:'รับสลิปแล้ว อยู่ระหว่างตรวจสอบ กรุณารีเฟรชหน้านี้เพื่อติดตามผล') ?></p><?php endif ?>
</section>
<?php endif ?>
<section class="panel"><h2>ประวัติค่า Platform</h2>
<?php if(!$history): ?><p>ยังไม่มีรายการชำระ</p><?php else: ?><div class="table-scroll"><table><thead><tr><th>เลขรายการ</th><th>ยอด</th><th>สถานะ</th><th>ชำระสำเร็จ</th></tr></thead><tbody>
<?php foreach($history as $row): ?><tr><td><?= esc($row['number']) ?></td><td><?= number_format($row['amount_satang']/100,2) ?> บาท</td><td><?= esc(['paid'=>'ชำระแล้ว','pending'=>'รอชำระ','checking'=>'ตรวจสลิป','review'=>'รอตรวจสอบ','expired'=>'รายการหมดอายุ'][$row['status']]??$row['status']) ?></td><td><?= esc(p_date($row['paid_at'])) ?></td></tr><?php endforeach ?>
</tbody></table></div><?php endif ?></section>
</div>
