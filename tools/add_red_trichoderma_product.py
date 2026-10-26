"""Add the supplied red-bag product using the user's approved copy."""
from pathlib import Path
import datetime
import hashlib
import json
import sqlite3

root = Path(__file__).resolve().parents[1]
source = Path('C:/Users/phon/AppData/Local/Temp/codex-clipboard-21ebf264-01f9-4882-883d-b37e76ffe972.png')
data = source.read_bytes()
image = 'platform/uploads/' + hashlib.md5(data).hexdigest() + '.png'
(root / 'public' / image).write_bytes(data)
name = 'สูตรถุงแดง — ไตรโคเดอร์มา + ฮิวมิค แอซิด ชนิดเม็ด 50 กิโลกรัม'
short = 'สูตรถุงแดง ไตรโคเดอร์มา + ฮิวมิค แอซิด ชนิดเม็ด สำหรับดูแลดินและระบบราก ขนาด 50 กิโลกรัม'
description = '''ผลิตภัณฑ์ชนิดเม็ดสำหรับดูแลดินและระบบราก ขนาด 50 กิโลกรัม

ดูแลพื้นฐานการปลูกตั้งแต่ดิน ด้วยสูตรถุงแดงที่สื่อผลิตภัณฑ์ระบุส่วนประกอบกลุ่มไตรโคเดอร์มาและฮิวมิค แอซิด สำหรับนำไปใช้ร่วมกับแผนการบำรุงดินและพืชอย่างเหมาะสม

รายละเอียดสินค้า
สูตรถุงแดงเป็นผลิตภัณฑ์ชนิดเม็ด มุ่งเน้นการดูแลดินและบริเวณราก เหมาะสำหรับผู้ปลูกที่ต้องการจัดการสภาพดินควบคู่กับการให้น้ำและธาตุอาหาร โดยควรเลือกใช้ตามชนิดพืช สภาพพื้นที่ และคำแนะนำบนฉลาก

จุดเด่นของสูตร
• ไตรโคเดอร์มา: เป็นองค์ประกอบที่สื่อผลิตภัณฑ์ระบุไว้สำหรับการดูแลบริเวณรากและดิน
• ฮิวมิค แอซิด: เป็นองค์ประกอบของสูตรสำหรับการปรับปรุงดิน
• รูปแบบชนิดเม็ด: สำหรับใช้ทางดินตามวิธีที่ผู้ผลิตกำหนด
• บรรจุกระสอบ 50 กิโลกรัม: เหมาะสำหรับวางแผนใช้งานในพื้นที่เพาะปลูก

เหมาะกับใคร
ผู้ปลูกพืชไร่ พืชผัก และไม้ผลที่ต้องการดูแลดินและระบบราก ทั้งนี้ ต้องตรวจสอบชนิดพืชที่ใช้ได้จากฉลากก่อนใช้งาน

วิธีใช้
ใช้ทางดินตามอัตราที่ระบุบนฉลาก โดยพิจารณาชนิดพืช อายุพืช และสภาพดินประกอบ ไม่ควรกำหนดอัตราใช้จากสีถุงหรือชื่อสูตรเพียงอย่างเดียว
อัตราการใช้และความถี่: รอยืนยันจากฉลากจริง

ข้อมูลสินค้า
สูตร: ไตรโคเดอร์มา + ฮิวมิค แอซิด ตามข้อความด้านหน้าถุง
ลักษณะ: ชนิดเม็ด
บรรจุภัณฑ์: ถุงแดง
น้ำหนักสุทธิ: 50 กิโลกรัม
ความเข้มข้น / ปริมาณจุลินทรีย์: รอยืนยันจากฉลาก
เลขทะเบียนและผู้ผลิต: รอยืนยันจากฉลาก'''
now = datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S')
with sqlite3.connect(root / 'writable/platform.sqlite') as db:
    db.row_factory = sqlite3.Row
    columns = {r['name'] for r in db.execute('PRAGMA table_info(products)')}
    for column, definition in [('inquiry_only', 'INTEGER NOT NULL DEFAULT 0'), ('short_description', "TEXT NOT NULL DEFAULT ''")]:
        if column not in columns:
            db.execute('ALTER TABLE products ADD COLUMN ' + column + ' ' + definition)
    sku = 'SKN-RED-TRICHODERMA-HUMIC-50KG'
    existing = db.execute('SELECT id FROM products WHERE sku=?', (sku,)).fetchone()
    if existing:
        product_id = existing['id']
    else:
        product_id = db.execute('''INSERT INTO products
            (sku,name,description,category,image,price_satang,stock,active,created_at,inquiry_only,short_description)
            VALUES(?,?,?,?,?,0,0,1,?,1,?)''',
            (sku, name, description, 'วัสดุปรับปรุงดิน', image, now, short)).lastrowid
        db.execute('''INSERT INTO audit(actor_id,action,entity,entity_id,detail,created_at)
            VALUES(NULL,?,?,?,?,?)''', ('product_created', 'products', product_id,
            'Red formula 50 kg; user-supplied copy and image; inquiry only; price and label details unconfirmed.', now))
    result = dict(db.execute('SELECT * FROM products WHERE id=?', (product_id,)).fetchone())
(root / 'build/added-red-trichoderma-product.json').write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps({'id': product_id, 'sku': sku, 'image': image, 'inquiry_only': result['inquiry_only']}))
