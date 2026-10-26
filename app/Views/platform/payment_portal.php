<section class="panel"><h2>แจ้งโอนเงินและตรวจสอบสลิป</h2>
<p>เลือกคำสั่งซื้อเพื่อดูยอดชำระ บัญชีรับเงิน และอัปโหลดสลิป</p>
<?php if(\App\Libraries\Platform\SlipPayments::enabled() && \App\Libraries\Platform\SlipVerifier::ready((string)env('SLIP_PROVIDER'))): ?>
<div class="notice info">ตรวจสลิปอัตโนมัติ เมื่อยืนยันยอดเงินและบัญชีรับเงินสำเร็จ ระบบจะสร้าง Payment token ในหน้าคำสั่งซื้อ หากตรวจไม่ได้จะส่งให้เจ้าหน้าที่ตรวจต่อ</div>
<?php else: ?><div class="notice info">ขณะนี้ตรวจสลิปโดยเจ้าหน้าที่ ระบบตรวจอัตโนมัติยังรอตั้งค่าบัญชีบริการ</div><?php endif ?>
<?php if(!$user): ?><p>เข้าสู่ระบบเพื่อแจ้งชำระเงินและดูสถานะคำสั่งซื้อของคุณ</p><a class="button" href="<?= site_url('login') ?>">เข้าสู่ระบบ</a>
<?php elseif(!$orders): ?><div class="empty">ยังไม่มีคำสั่งซื้อ</div><a class="button" href="<?= site_url('shop') ?>">เลือกซื้อสินค้า</a>
<?php else: ?><div class="table-scroll"><table><thead><tr><th>คำสั่งซื้อ</th><th>ยอดชำระ</th><th>สถานะ</th><th></th></tr></thead><tbody>
<?php foreach($orders as $o): ?><tr><td><?= esc($o['number']) ?></td><td><?= p_money($o['total_satang']) ?></td><td><?= esc(p_label($o['status'])) ?></td><td><a class="button secondary small" href="<?= site_url('orders/'.$o['id']) ?>">ดู / แจ้งชำระเงิน</a></td></tr><?php endforeach ?>
</tbody></table></div><?php endif ?></section>
