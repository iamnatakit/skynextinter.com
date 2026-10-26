<?php use App\Libraries\Platform\Membership;
$post=site_url('admin/user_management/action/');
$value=static fn($key,$default='')=>esc($settings[$key]?:$default,'attr');
?>
<div class="sn-membership">
<div class="stats">
    <?php foreach(['downline'=>'ผู้ใช้ใต้สายงาน','external'=>'ผู้ใช้ต่างสายงาน','expired'=>'ล็อก / หมดอายุ','paid'=>'ชำระรายปีแล้ว'] as $key=>$label): ?><div class="stat"><div class="stat-label"><?= $label ?></div><div class="stat-value"><?= $counts[$key] ?></div></div><?php endforeach ?>
</div>
<section class="panel">
    <h2>ประเภทสิทธิ์ใช้งาน Platform</h2>
    <p>ทดลอง 30 วัน · เฉพาะสมาชิกใหม่ใต้สายงาน 0005 ซื้อสินค้ารวมอย่างน้อย 100 PV ต่อคำสั่งซื้อ และชำระสำเร็จ → ทีมงานใต้สายงาน ใช้ฟรี · จ่ายค่า Platform 990 บาท/ปี → ทีมงานต่างสายงาน</p>
    <p>ทีมงานทั้งสองประเภทใช้เมนูธุรกิจได้ครบ ยกเว้นเมนูเฉพาะ Admin การชำระเงินไม่ให้สิทธิ์ผู้ดูแลระบบ</p>
    <div class="actions"><a class="button secondary" href="<?= site_url('admin/user_management') ?>">ทั้งหมด / ตั้งค่า</a><a class="button secondary" href="<?= site_url('admin/downline_users') ?>">ผู้ใช้ใต้สายงาน</a><a class="button secondary" href="<?= site_url('admin/external_users') ?>">ผู้ใช้ต่างสายงาน</a><a class="button secondary" href="<?= site_url('admin/users') ?>">จัดการผู้ใช้และ Role</a></div>
</section>
<?php if($group==='all'): ?>
<section class="panel">
    <h2>ตั้งค่าต้นสาย การชำระเงิน และอีเมล</h2>
    <p class="notice <?= $paymentReady?'':'info' ?>">QR และตรวจสลิป: <?= $paymentReady?'ตั้งค่าครบ':'ยังตั้งค่าไม่ครบ' ?> · อีเมลหมดอายุ: <?= $mailReady?'ตั้งค่า SMTP แล้ว':'ยังไม่ได้ตั้งค่า SMTP' ?></p>
    <form method="post" action="<?= $post ?>0" class="stack" autocomplete="off">
    <?= csrf_field() ?><input type="hidden" name="action" value="config">
    <p class="notice info">สมาชิกใต้สายงาน: ต้องเลือกสมัครเป็นสมาชิกใหม่ และอยู่ใต้ Affiliate 0005 (รวมสายงานต่อเนื่อง ไม่รวมตัวต้นสาย) ซื้อครบ 100 PV ต่อคำสั่งซื้อและยืนยันชำระเงิน จึงได้รับสิทธิ์ใช้ฟรี สมาชิก SKYNEXT เดิมใช้แผนรายปี 990 บาท</p>
    <div class="config-grid"><div>
        <h3>รับชำระรายปี 990 บาท</h3>
        <label>ชื่อผู้รับเงิน<input name="platform_payee_name" value="<?= $value('platform_payee_name') ?>" maxlength="200"></label>
        <label>พร้อมเพย์ที่ผูกกับบัญชีผู้รับ (โทรศัพท์ 10 หลัก / เลขประจำตัว 13 หลัก)<input name="platform_promptpay" inputmode="numeric" value="<?= $value('platform_promptpay') ?>" maxlength="13"></label>
        <label>บริการตรวจสลิป<select name="platform_slip_provider"><option value="easyslip" <?= ($settings['platform_slip_provider']?:'easyslip')==='easyslip'?'selected':'' ?>>EasySlip</option><option value="slipok" <?= $settings['platform_slip_provider']==='slipok'?'selected':'' ?>>SlipOK</option></select></label>
        <label>รหัสธนาคารผู้รับ 3 หลัก<input name="slip_receiver_bank" value="<?= $value('slip_receiver_bank') ?>" maxlength="3" inputmode="numeric" placeholder="เช่น 004"></label>
        <label>เลขบัญชีธนาคารผู้รับเต็ม (เฉพาะตัวเลข)<input name="slip_receiver_account" value="<?= $value('slip_receiver_account') ?>" maxlength="20" inputmode="numeric"></label>
        <label>EasySlip API Key (เว้นว่างเพื่อใช้ค่าเดิม)<input type="password" name="easyslip_api_key" autocomplete="new-password" maxlength="500"></label>
        <label>SlipOK API Key (เว้นว่างเพื่อใช้ค่าเดิม)<input type="password" name="slipok_api_key" autocomplete="new-password" maxlength="500"></label>
        <label>SlipOK Branch ID<input name="slipok_branch_id" value="<?= $value('slipok_branch_id') ?>" inputmode="numeric" maxlength="20"></label>
        <p>ตรวจยอด 990 บาท บัญชีผู้รับ เวลาธุรกรรม และสลิปซ้ำก่อนเปิดสิทธิ์ รูปภาพหรือข้อความที่ AI อ่านได้เพียงอย่างเดียวไม่ใช้อนุมัติการชำระเงิน</p>
    </div><div>
        <h3>อีเมลแจ้งหมดอายุ</h3>
        <label>SMTP Host<input name="platform_smtp_host" value="<?= $value('platform_smtp_host') ?>" maxlength="200"></label>
        <label>SMTP Port<input type="number" min="1" max="65535" name="platform_smtp_port" value="<?= $value('platform_smtp_port','587') ?>"></label>
        <label>การเข้ารหัส<select name="platform_smtp_crypto"><option value="tls" <?= ($settings['platform_smtp_crypto']?:'tls')==='tls'?'selected':'' ?>>TLS / STARTTLS</option><option value="ssl" <?= $settings['platform_smtp_crypto']==='ssl'?'selected':'' ?>>SSL</option></select></label>
        <label>SMTP Username<input name="platform_smtp_user" value="<?= $value('platform_smtp_user') ?>" maxlength="200"></label>
        <label>SMTP Password (เว้นว่างเพื่อใช้ค่าเดิม)<input type="password" name="platform_smtp_pass" autocomplete="new-password" maxlength="500"></label>
        <label>อีเมลผู้ส่ง<input type="email" name="platform_smtp_from" value="<?= $value('platform_smtp_from') ?>" maxlength="200"></label>
        <p>ระบบล็อกทันทีตามวันหมดอายุ และเก็บอีเมลเข้าคิวส่งอัตโนมัติ ข้อมูลเดิมของผู้ใช้ยังอยู่</p>
    </div></div>
    <button>บันทึกต้นสายและการตั้งค่า Platform</button>
    </form>
</section>
<?php endif ?>
<section class="panel">
    <div class="table-toolbar"><h2><?= $group==='downline'?'ผู้ใช้ใต้สายงาน':($group==='external'?'ผู้ใช้ต่างสายงาน':'ผู้ใช้และสิทธิ์ Platform') ?></h2>
    <form method="get"><input name="q" value="<?= esc($query,'attr') ?>" placeholder="ค้นหาชื่อ อีเมล รหัสแนะนำ"><button>ค้นหา</button></form></div>
    <div class="table-scroll"><table><thead><tr><th>ผู้ใช้ / Role</th><th>สายงาน</th><th>สถานะ</th><th>ทดลองถึง</th><th>รายปีถึง</th><th>จัดการ</th></tr></thead><tbody>
    <?php foreach($users as $u): $m=$u['membership']; ?>
    <tr><td><strong><?= esc($u['name']) ?></strong><br><?= esc($u['email']) ?><br><span class="muted"><?= esc((\App\Libraries\Platform\Access::ROLES[$u['role']]??$u['role']).' · '.$u['referral_code']) ?></span></td>
    <td><?= $m['type']==='downline'?'ใต้สายงาน':'ต่างสายงาน' ?></td><td><span class="status-pill"><?= esc(Membership::label($m['state'])) ?></span></td><td><?= esc(p_date($m['trial_ends_at'])) ?></td><td><?= esc(p_date($m['paid_until'])) ?></td>
    <td><a href="<?= site_url('admin/users?edit='.$u['id']) ?>">แก้ไขบัญชี</a>
    <?php if(in_array($m['state'],['trial','expired'],true) && empty($m['paid_until']) && empty($m['manual_lock'])): ?>
    <form method="post" class="sn-trial-extension" action="<?= $post.$u['id'] ?>"><?= csrf_field() ?><input type="hidden" name="action" value="extend_trial"><label>ขยายทดลอง (วัน)<input type="number" name="trial_days" min="1" max="3650" value="30" required></label><button class="small secondary">ขยายเวลา</button></form>
    <?php endif ?>
    <form method="post" class="inline-form" action="<?= $post.$u['id'] ?>"><?= csrf_field() ?><input type="hidden" name="action" value="<?= $m['manual_lock']?'unlock':'lock' ?>"><button class="small secondary"><?= $m['manual_lock']?'คืนสิทธิ์ตามแพ็กเกจ':'ระงับ Platform' ?></button></form></td></tr>
    <?php endforeach ?>
    <?php if(!$users): ?><tr><td colspan="6">ไม่พบผู้ใช้ในกลุ่มนี้</td></tr><?php endif ?>
    </tbody></table></div>
</section>
<?php if($group==='all'): ?>
<section class="panel"><h2>รายการต่ออายุ Platform</h2><div class="table-scroll"><table><thead><tr><th>เลขรายการ / ผู้ใช้</th><th>ยอด</th><th>สถานะ</th><th>ผลตรวจ</th><th>สลิป / ตรวจซ้ำ</th></tr></thead><tbody>
<?php foreach($invoices as $i): ?><tr><td><?= esc($i['number']) ?><br><?= esc($i['email']) ?></td><td>990 บาท</td><td><?= esc(['pending'=>'รอชำระ','checking'=>'กำลังตรวจ','review'=>'รอตรวจสอบ','paid'=>'ชำระแล้ว','expired'=>'รายการหมดอายุ'][$i['status']]??$i['status']) ?></td><td><?= esc($i['note']??'') ?></td><td>
<?php if($i['slip_id']): ?><a href="<?= site_url('admin/user_management/slip/'.$i['slip_id']) ?>">เปิดสลิป</a><?php if($i['slip_status']==='review'): ?><form method="post" action="<?= $post.$i['slip_id'] ?>"><?= csrf_field() ?><input type="hidden" name="action" value="retry_slip"><button class="small secondary">ตรวจซ้ำกับผู้ให้บริการ</button></form><?php endif ?><?php endif ?></td></tr><?php endforeach ?>
<?php if(!$invoices): ?><tr><td colspan="5">ยังไม่มีรายการต่ออายุ</td></tr><?php endif ?></tbody></table></div></section>
<section class="panel"><h2>อีเมลแจ้งหมดอายุ</h2><form method="post" action="<?= $post ?>0"><?= csrf_field() ?><input type="hidden" name="action" value="queue_notices"><button class="secondary">ตรวจวันหมดอายุและจัดคิวแจ้งเตือน</button></form>
<div class="table-scroll"><table><thead><tr><th>ผู้รับ</th><th>สถานะ</th><th>ส่งเมื่อ</th><th>รายละเอียด</th><th>จัดการ</th></tr></thead><tbody>
<?php foreach($emails as $e): ?><tr><td><?= esc($e['recipient']) ?></td><td><?= esc(['queued'=>'รอส่ง','sending'=>'กำลังส่ง','sent'=>'ส่งแล้ว','review'=>'รอตรวจสอบ'][$e['status']]) ?></td><td><?= esc(p_date($e['sent_at'])) ?></td><td><?= esc($e['error']) ?></td><td><?php if($e['status']==='review'): ?><form method="post" action="<?= $post.$e['id'] ?>"><?= csrf_field() ?><input type="hidden" name="action" value="retry_email"><button class="small secondary">จัดคิวส่งใหม่</button></form><?php endif ?></td></tr><?php endforeach ?>
<?php if(!$emails): ?><tr><td colspan="5">ยังไม่มีอีเมลแจ้งหมดอายุ</td></tr><?php endif ?></tbody></table></div></section>
<?php endif ?>
</div>
