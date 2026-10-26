<?php if(!empty($registration) && $registration['kind']==='existing'): $renewal=$registration['renewal']; ?>
<section class="panel"><h2>แผนสมาชิก SKYNEXT ของคุณ</h2>
<p>รหัสสมาชิก SKYNEXT เดิม: <strong><?= esc($registration['skynext_member_code']) ?></strong></p>
<p>ทดลองใช้งานระบบ 30 วัน จากนั้นใช้ <strong>Packet 990 บาท/ปี</strong></p>
<?php if($renewal): ?><p>ครบกำหนดทดลองใช้: <strong><?= esc(p_date($renewal['due_at'])) ?></strong></p>
<p>สถานะรายการ: <?= esc(['scheduled'=>'กำหนดไว้หลังทดลองใช้ครบ 30 วัน','due'=>'ถึงกำหนดชำระ','awaiting_payment'=>'รอชำระและตรวจสอบสลิป','paid'=>'ชำระแล้ว'][$renewal['status']]??$renewal['status']) ?></p><?php endif ?>
<p class="muted">บันทึกความประสงค์เมื่อ <?= esc(p_date($registration['accepted_at'])) ?> · ไม่มีการหักเงินอัตโนมัติ</p>
<a class="button secondary" href="<?= site_url('platform/billing') ?>">ดูแผนและรายการชำระเงิน</a></section>
<?php endif ?>
