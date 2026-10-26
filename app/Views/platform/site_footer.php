<footer id="sn-site-footer" aria-label="ส่วนท้ายเว็บไซต์ SKYNEXT INTER">
  <div class="sn-footer-inner">
    <div class="sn-footer-top">
      <div><span class="sn-footer-kicker">เติบโตไปด้วยกันกับ SKYNEXT INTER</span><h2>เคียงคู่เกษตรกรไทย<br>พร้อมเติบโตไปด้วยกัน</h2></div>
      <a class="sn-footer-cta" href="<?= site_url('register') ?>">สมัครเป็นตัวแทน <span aria-hidden="true">↗</span></a>
    </div>
    <div class="sn-footer-grid">
      <div class="sn-footer-brand">
        <a class="sn-footer-logo" href="<?= site_url() ?>" aria-label="SKYNEXT INTER หน้าแรก"><img src="<?= base_url('platform/assets/skynext-logo.png') ?>" alt="โลโก้ SKYNEXT INTER" width="76" height="76" loading="lazy"><span>SKYNEXT INTER<small>สกายเน็กซ์ | เคียงคู่เกษตรกรไทย</small></span></a>
        <p>สินค้าเกษตรและเครื่องมือออนไลน์<br>เชื่อมต่อสินค้า ลูกค้า และโอกาสในการเติบโต<br>ไว้ในที่เดียว</p>
      </div>
      <nav aria-label="รู้จักสกายเน็กซ์"><h3>รู้จักสกายเน็กซ์</h3><ul>
        <li><a href="<?= site_url() ?>">หน้าแรก</a></li>
        <li><a href="<?= site_url('about/company') ?>">เกี่ยวกับบริษัท</a></li>
        <li><a href="<?= site_url('activities') ?>">กิจกรรม</a></li>
        <li><a href="<?= site_url('blog') ?>">บทความและความรู้</a></li>
      </ul></nav>
      <nav aria-label="สินค้าและสมาชิก"><h3>สินค้าและสมาชิก</h3><ul>
        <li><a href="<?= site_url('shop') ?>">เลือกซื้อสินค้า</a></li>
        <li><a href="<?= site_url('cart') ?>">ตะกร้าสินค้า</a></li>
        <li><a href="<?= site_url('account') ?>">บัญชีของฉัน</a></li>
        <li><a href="<?= site_url('workspace') ?>">Workspace ของฉัน</a></li>
        <li><a href="<?= site_url('affiliate') ?>">ลิงก์แนะนำและทีมของฉัน</a></li>
      </ul></nav>
      <div class="sn-footer-contact"><h3>พูดคุยกับเรา</h3><p>สนใจสินค้า หรือการเป็นตัวแทน?<br>ติดต่อผู้แนะนำของคุณเพื่อรับข้อมูลเพิ่มเติม</p>
        <a class="sn-footer-line" href="<?= site_url('contact-us') ?>"><span aria-hidden="true">↗</span> ติดต่อผ่าน LINE</a>
        <a class="sn-footer-company" href="<?= site_url('contact-company') ?>">ช่องทางติดต่อส่วนกลาง →</a>
      </div>
    </div>
    <?= view('platform/footer_referrer') ?>
    <?= view('platform/footer_visitor_stats') ?>
    <div class="sn-footer-bottom"><p>© <?= date('Y') ?> SKYNEXT INTER. สงวนลิขสิทธิ์</p><span>เติบโตอย่างมั่นใจ ไปด้วยกันอย่างยั่งยืน</span></div>
  </div>
</footer>
