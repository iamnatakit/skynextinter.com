"""Apply the user's green-bag copy and image to the existing matching formula."""
from pathlib import Path
import datetime
import hashlib
import json
import sqlite3

root = Path(__file__).resolve().parents[1]
data = Path('C:/Users/phon/AppData/Local/Temp/codex-clipboard-e1fa72ec-e766-4abe-9632-f4ac3a77d81c.png').read_bytes()
image = 'platform/uploads/' + hashlib.md5(data).hexdigest() + '.png'
(root / 'public' / image).write_bytes(data)
name = 'สูตรถุงเขียว — ฮิวมิค + อะมิโน + ซิลิคอน ชนิดเม็ด 50 กิโลกรัม'
short = 'สูตรถุงเขียว ฮิวมิค + อะมิโน + ซิลิคอน วัสดุปรับปรุงดินชนิดเม็ด สำหรับใช้ร่วมกับแผนดูแลดินและพืช ขนาด 50 กิโลกรัม'
description = '''วัสดุปรับปรุงดินชนิดเม็ด สำหรับการดูแลดินและพืช ขนาด 50 กิโลกรัม

วางพื้นฐานการบำรุงพืชด้วยการดูแลดิน สูตรถุงเขียวระบุส่วนประกอบฮิวมิค อะมิโน และซิลิคอน เหมาะสำหรับนำไปประกอบแผนการดูแลแปลงร่วมกับการจัดการน้ำและธาตุอาหารตามความต้องการของพืช

รายละเอียดสินค้า
สูตรถุงเขียวเป็นวัสดุปรับปรุงดินชนิดเม็ด ออกแบบสำหรับใช้ทางดิน สื่อผลิตภัณฑ์นำเสนอการใช้งานกับพืชหลายกลุ่ม เช่น ข้าว พืชไร่ พืชผัก และไม้ผล โดยการเลือกใช้ต้องยึดคำแนะนำของผู้ผลิตและสภาพพื้นที่จริง

จุดเด่นของสูตร
• ฮิวมิค: เป็นส่วนประกอบที่ระบุในสูตรสำหรับดูแลสภาพดิน
• อะมิโน: เป็นส่วนประกอบที่สื่อผลิตภัณฑ์นำเสนอสำหรับการบำรุงพืช
• ซิลิคอน: เป็นส่วนประกอบอีกกลุ่มที่ระบุบนบรรจุภัณฑ์
• รูปแบบชนิดเม็ด: ใช้ทางดินตามคำแนะนำบนฉลาก
• ขนาด 50 กิโลกรัม: สำหรับวางแผนดูแลพื้นที่เพาะปลูกตามอัตราใช้ที่เหมาะสม

เหมาะกับใคร
เกษตรกรที่ต้องการวัสดุปรับปรุงดินสำหรับใช้ร่วมกับแผนบำรุงพืช โดยควรตรวจสอบความเหมาะสมกับชนิดพืชและผลวิเคราะห์ดินก่อนเลือกใช้

วิธีใช้
ใช้ทางดินตามคำแนะนำบนฉลาก เลือกช่วงเวลาและตำแหน่งใส่ให้เหมาะกับชนิดพืชและระยะการเจริญเติบโต
อัตราการใช้ ความถี่ และการใช้ร่วมกับผลิตภัณฑ์อื่น: รอยืนยันจากฉลากจริง

ข้อมูลสินค้า
สูตร: ฮิวมิค + อะมิโน + ซิลิคอน
ประเภทตามข้อความบนถุง: วัสดุปรับปรุงดิน
ลักษณะ: ชนิดเม็ด
บรรจุภัณฑ์: ถุงเขียว
น้ำหนักสุทธิ: 50 กิโลกรัม
สัดส่วนส่วนประกอบ: รอยืนยันจากฉลาก
เลขทะเบียนและผู้ผลิต: รอยืนยันจากฉลาก'''
with sqlite3.connect(root / 'writable/platform.sqlite') as db:
    db.row_factory = sqlite3.Row
    columns = {r['name'] for r in db.execute('PRAGMA table_info(products)')}
    if 'inquiry_label' not in columns:
        db.execute("ALTER TABLE products ADD COLUMN inquiry_label TEXT NOT NULL DEFAULT ''")
    old = dict(db.execute('SELECT * FROM products WHERE id=245 AND sku=?', ('SKN-HUMIC-AMINO-SILICON-50KG',)).fetchone())
    backup = root / 'build/green-formula-before-update.json'
    if not backup.exists():
        backup.write_text(json.dumps(old, ensure_ascii=False, indent=2), encoding='utf-8')
    if old['image'] != image:
        db.execute('INSERT OR IGNORE INTO product_images(product_id,image,alt,sort_order) VALUES(?,?,?,?)',
            (245, old['image'], 'ภาพประชาสัมพันธ์สูตรฮิวมิค + อะมิโน + ซิลิคอน 50 กก.', 1))
    db.execute('''UPDATE products SET name=?,description=?,short_description=?,image=?,inquiry_only=1,
        inquiry_label=?,active=1 WHERE id=245''',
        (name, description, short, image, 'สอบถามสูตรสำหรับพืชของคุณ'))
    now = datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S')
    db.execute('INSERT INTO audit(actor_id,action,entity,entity_id,detail,created_at) VALUES(NULL,?,?,?,?,?)',
        ('product_updated', 'products', 245, 'Applied user green-bag copy, image, card summary and inquiry label; existing price and stock preserved; original poster retained in gallery.', now))
    result = dict(db.execute('SELECT * FROM products WHERE id=245').fetchone())
    assert result['price_satang'] == old['price_satang'] and result['stock'] == old['stock']
(root / 'build/updated-green-formula-product.json').write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps({'id': 245, 'image': image, 'price_satang': result['price_satang'], 'inquiry_only': result['inquiry_only']}))
