<?php
$benefits = [
    ['เริ่มต้นพร้อมสินค้า', 'มีสินค้าเกษตรให้เลือกนำเสนอ พร้อมข้อมูลและราคาที่ช่วยให้ลูกค้าศึกษาก่อนตัดสินใจ คุณจึงมีสิ่งที่พร้อมนำไปพูดคุยกับลูกค้า'],
    ['มีพื้นที่ธุรกิจบนโลกออนไลน์', 'ใช้เว็บไซต์อัจฉริยะช่วยแนะนำสินค้าและโอกาสทางธุรกิจ ให้ผู้สนใจเข้ามาดูข้อมูลได้ แม้คุณกำลังทำงานหรือดูแลลูกค้ารายอื่น'],
    ['แชร์ลิงก์เดียว เข้าถึงคุณได้', 'มีลิงก์ Affiliate และหน้าโปรโมทของสมาชิก พร้อมปุ่มติดต่อที่เชื่อมไปยัง LINE ในโปรไฟล์ ให้ผู้สนใจติดต่อเจ้าของลิงก์ได้สะดวก'],
    ['ให้ลูกค้าสั่งซื้อได้ด้วยตัวเอง', 'หน้าร้าน ตะกร้าสินค้า และขั้นตอนสั่งซื้อช่วยให้ลูกค้าเลือกสินค้าและส่งออเดอร์ออนไลน์ ลดงานรับข้อมูลซ้ำในแชต'],
    ['ดูแลผู้สนใจต่อได้เป็นระบบ', 'มีเครื่องมือเก็บรายชื่อ CRM และงานติดตาม ช่วยจัดระเบียบโอกาสที่เข้ามา ให้คุณกลับมาพูดคุยและดูแลต่อได้ง่ายขึ้น'],
    ['มี AI เป็นผู้ช่วยเบื้องต้น', 'ใช้ Chatbot ช่วยตอบคำถามจากข้อมูลที่กำหนด และเชื่อมบทสนทนาผ่านช่องทางที่เปิดใช้งาน ให้คุณมีเวลาสำหรับการดูแลลูกค้ามากขึ้น'],
    ['เติบโตไปพร้อมเครือข่าย', 'ชวนผู้สนใจเข้ามาศึกษาธุรกิจผ่านลิงก์แนะนำ พร้อมระบบบันทึกผู้แนะนำและมุมมองเครือข่าย เพื่อช่วยจัดการสมาชิกที่เชื่อมโยงกับคุณ'],
    ['เห็นข้อมูลเพื่อวางแผนก้าวต่อไป', 'ติดตามข้อมูลที่ระบบเปิดให้ตามสิทธิ์ ทั้งคำสั่งซื้อ ผู้สนใจ และสถิติที่เกี่ยวข้อง ช่วยให้วางแผนดูแลธุรกิจจากข้อมูลได้ชัดเจนขึ้น'],
];
?>
<section id="sn-partner-benefits" aria-labelledby="sn-partner-title">
  <div class="sn-partner-wrap">
    <header class="sn-partner-intro"><p class="sn-partner-eyebrow">GROW TOGETHER WITH SKYNEXT INTER</p><h2 id="sn-partner-title">เริ่มต้นกับสิ่งที่พร้อม<br><span>เติบโตไปด้วยกันกับ SKYNEXT INTER</span></h2><p>มีสินค้า มีพื้นที่ออนไลน์ มีเครื่องมือช่วยทำงาน<br>ให้คุณเริ่มสร้างโอกาสทางธุรกิจ พร้อมดูแลลูกค้าและทีมในแบบของคุณ</p></header>
    <div class="sn-partner-offer">
      <div class="sn-partner-offer-copy"><span class="sn-partner-badge">สิทธิประโยชน์สำหรับผู้เข้าร่วมงาน</span><h3>ใช้ Free Website อัจฉริยะ</h3><p class="sn-partner-value">มูลค่า <strong>150,000</strong> บาท</p><p>เปลี่ยนลิงก์ที่คุณแชร์ ให้เป็นพื้นที่นำเสนอสินค้า<br>รับโอกาสใหม่ และเชื่อมต่อผู้สนใจถึงคุณ</p><a href="<?= site_url('register') ?>" class="sn-partner-primary">สมัครเข้าร่วมงาน <span aria-hidden="true">↗</span></a></div>
      <div class="sn-partner-visual"><img src="<?= base_url('platform/assets/skynext-smart-platform.png') ?>" alt="แนวคิดเว็บไซต์อัจฉริยะ SKYNEXT ช่วยนำเสนอสินค้า รับคำสั่งซื้อ สมัครสมาชิก และติดตามผู้สนใจ" width="1536" height="1024" loading="lazy"><p>เว็บไซต์ช่วยนำเสนอ · คุณสร้างความสัมพันธ์</p></div>
    </div>
    <div class="sn-partner-benefit-heading"><p class="sn-partner-eyebrow">พร้อมมากขึ้น ตั้งแต่ก้าวแรก</p><h3>ข้อดีของการเริ่มต้นกับ SKYNEXT INTER</h3><p>รวมเครื่องมือที่ช่วยให้การขายออนไลน์และการดูแลเครือข่ายเป็นเรื่องที่จัดการได้</p></div>
    <div class="sn-partner-grid"><?php foreach ($benefits as $i => [$heading, $description]): ?><article><span class="sn-partner-number"><?= sprintf('%02d', $i + 1) ?></span><div><h4><?= esc($heading) ?></h4><p><?= esc($description) ?></p></div></article><?php endforeach ?></div>
    <div class="sn-partner-close"><h3>โอกาสเริ่มต้น อยู่ที่ก้าวแรกของคุณ</h3><p>ทำความรู้จักสินค้าและรูปแบบการร่วมงาน แล้วเลือกเริ่มต้นอย่างเข้าใจ<br>ให้ผู้แนะนำช่วยตอบคำถาม พร้อมพาคุณรู้จักเครื่องมือที่ใช่สำหรับธุรกิจ</p><div class="sn-partner-buttons"><a class="sn-partner-primary" href="<?= site_url('register') ?>">สมัครเป็นตัวแทน <span aria-hidden="true">→</span></a><a class="sn-partner-secondary" href="<?= site_url('contact-us') ?>">สอบถามสิทธิ์และรายละเอียด</a></div></div>
    <aside id="sn-partner-terms" class="sn-partner-note" aria-label="รายละเอียดสิทธิ์และเงื่อนไข"><span class="sn-partner-note-heading">รายละเอียดสิทธิ์และเงื่อนไข</span><p>สอบถามเงื่อนไขการรับสิทธิ์ <strong>Free Website อัจฉริยะ มูลค่า 150,000 บาท</strong><br>ขอบเขตบริการที่รวม และค่าใช้จ่ายที่เกี่ยวข้องก่อนสมัคร</p><p><strong>การใช้งาน AI และบริการภายนอก</strong> ขึ้นอยู่กับการเชื่อมต่อ การตั้งค่า และสิทธิ์ที่เปิดใช้งาน</p><p class="sn-partner-note-result">ผลลัพธ์ทางธุรกิจขึ้นอยู่กับการดำเนินงานของแต่ละบุคคล</p></aside>
  </div>
</section>
<style>
.homepage-content-container:has(#sn-partner-benefits)>#contentnewmode6a5d99f1f1c67c0013a0000e{display:none!important}
#sn-partner-benefits{box-sizing:border-box;font-family:'Prompt','Sarabun',sans-serif;color:#284234;background:linear-gradient(180deg,#fcf9f0,#f1f5ec);padding:40px 24px 80px;margin-top:24px;border-top:1px solid #e3e6da}
#sn-partner-benefits *{box-sizing:border-box}
#sn-partner-benefits .sn-partner-wrap{max-width:1240px;margin:0 auto}
#sn-partner-benefits .sn-partner-intro{text-align:center;max-width:1000px;margin:0 auto 36px}
#sn-partner-benefits .sn-partner-eyebrow{font-size:13px;letter-spacing:.08em;font-weight:600;color:#90702c;margin:0 0 14px}
#sn-partner-benefits h2{font-size:clamp(28px,3.2vw,42px);line-height:1.5;font-weight:700;color:#163c27;margin:0 0 20px}
#sn-partner-benefits h2 span{color:#8f6c28}
#sn-partner-benefits p{font-size:16px;line-height:1.9;margin:0}
#sn-partner-benefits .sn-partner-intro>p:last-child{font-size:18px;color:#62705e}
#sn-partner-benefits .sn-partner-offer{display:grid;grid-template-columns:1fr 1fr;align-items:center;gap:32px;padding:40px;background:radial-gradient(ellipse at top right,#385b3c,transparent),#103622;border-radius:24px;color:#d8e5d9;box-shadow:0 16px 40px #173b2117}
#sn-partner-benefits .sn-partner-badge{display:inline-block;border:1px solid #b6a06b;background:#ffffff08;padding:6px 14px;border-radius:30px;font-size:13px;color:#e6ce92;margin-bottom:20px}
#sn-partner-benefits h3{font-size:clamp(24px,2.5vw,32px);font-weight:600;line-height:1.5;margin:0 0 14px;color:#173d26}
#sn-partner-benefits .sn-partner-offer h3{color:#fff}
#sn-partner-benefits .sn-partner-value{display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;color:#f0d18a;font-size:20px;margin:0 0 18px}
#sn-partner-benefits .sn-partner-value strong{font-size:clamp(42px,5vw,68px);line-height:1.3;font-weight:700;letter-spacing:-.03em}
#sn-partner-benefits a{display:inline-flex;justify-content:center;align-items:center;gap:22px;min-height:50px;padding:12px 24px;border-radius:9px;font-size:17px;font-weight:600;text-decoration:none}
#sn-partner-benefits .sn-partner-primary{color:#183921;background:linear-gradient(115deg,#f4d990,#c9a048)}
#sn-partner-benefits .sn-partner-offer .sn-partner-primary{margin-top:24px}
#sn-partner-benefits a:hover{filter:brightness(1.07)}
#sn-partner-benefits a:focus-visible{outline:3px solid #c7a14c;outline-offset:4px}
#sn-partner-benefits .sn-partner-visual{background:#ffffff0a;border:1px solid #ffffff26;border-radius:18px;padding:10px;overflow:hidden}
#sn-partner-benefits .sn-partner-visual img{width:100%;height:auto;display:block;border-radius:12px}
#sn-partner-benefits .sn-partner-visual p{font-size:13px;text-align:center;margin:12px 0 2px;color:#d8e5d9}
#sn-partner-benefits .sn-partner-benefit-heading{text-align:center;margin:52px auto 28px}
#sn-partner-benefits .sn-partner-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
#sn-partner-benefits article{display:flex;align-items:flex-start;gap:18px;padding:26px;background:#fff;border:1px solid #e0e6d9;border-radius:16px}
#sn-partner-benefits .sn-partner-number{display:grid;place-items:center;flex-shrink:0;width:40px;height:40px;border-radius:10px;background:#edf3e8;color:#38623b;font-size:14px;font-weight:600}
#sn-partner-benefits h4{font-size:20px;line-height:1.6;font-weight:600;color:#1b422b;margin:0 0 10px}
#sn-partner-benefits article p{font-size:15px;color:#62705e}
#sn-partner-benefits .sn-partner-close{text-align:center;padding:42px 20px 28px;margin-top:22px}
#sn-partner-benefits .sn-partner-buttons{display:flex;justify-content:center;flex-wrap:wrap;gap:14px;margin-top:24px}
#sn-partner-benefits .sn-partner-secondary{border:1px solid #476c4a;color:#23472b;background:#ffffff80}
#sn-partner-benefits .sn-partner-note{scroll-margin-top:100px;font-size:18px;line-height:1.9;text-align:center;color:#31543e;max-width:1060px;margin:10px auto 0;padding:30px 32px;background:linear-gradient(120deg,#fff9e9,#f4faef);border:1px solid #dfcc95;border-top:4px solid #bf963c;border-radius:18px;box-shadow:0 8px 24px #23452c08}
#sn-partner-benefits .sn-partner-note-heading{display:inline-block;color:#735012;background:#f4e6bd;border:1px solid #e7d39c;border-radius:24px;padding:6px 20px;font-size:18px;font-weight:600;margin-bottom:14px}
#sn-partner-benefits .sn-partner-note p{font-size:18px;line-height:1.9;color:#31543e;margin:0 0 14px}
#sn-partner-benefits .sn-partner-note strong{color:#956514;font-weight:600}
#sn-partner-benefits .sn-partner-note .sn-partner-note-result{color:#245b3a;font-weight:600;border-top:1px solid #d8e3cb;padding-top:16px;margin-bottom:0}
@media(max-width:640px){#sn-partner-benefits .sn-partner-note{padding:24px 18px}#sn-partner-benefits .sn-partner-note p{font-size:17px}#sn-partner-benefits .sn-partner-note-heading{font-size:16px;padding:6px 14px}}
@media(max-width:900px){#sn-partner-benefits .sn-partner-offer{grid-template-columns:1fr;padding:30px}#sn-partner-benefits .sn-partner-visual{max-width:600px;width:100%;margin:auto}}
@media(max-width:640px){#sn-partner-benefits{padding:28px 18px 48px;margin-top:20px}#sn-partner-benefits .sn-partner-grid{grid-template-columns:1fr;gap:16px}#sn-partner-benefits article{padding:22px;gap:14px}#sn-partner-benefits .sn-partner-offer{padding:24px 20px}#sn-partner-benefits .sn-partner-intro>p:last-child{font-size:16px}#sn-partner-benefits .sn-partner-buttons a{width:100%}#sn-partner-benefits .sn-partner-value{gap:8px}#sn-partner-benefits h4{font-size:19px}}
</style>
