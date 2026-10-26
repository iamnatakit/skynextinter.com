"""Save supplied Sky Power Green poster as a draft pending sale details."""
from pathlib import Path
import datetime
import hashlib
import json
import sqlite3

root = Path(__file__).resolve().parents[1]
data = Path('D:/#SKYNEX INTER/ปุ๋ย/126912_0.jpg').read_bytes()
image = 'platform/uploads/' + hashlib.md5(data).hexdigest() + '.jpg'
(root / 'public' / image).write_bytes(data)
sku = 'SKN-SKY-POWER-GREEN-BOTTLE'
description = '''สกาย พาวเวอร์กรีน (Sky Power Green) ตรา SKN
ผลิตภัณฑ์สำหรับการเกษตร

ข้อมูลจากภาพประชาสัมพันธ์:
• นำเสนอผลิตภัณฑ์ในบรรจุภัณฑ์แบบขวดและกล่องซอง
• ใช้ภาพประกอบพืชผัก ข้าว และพืชไร่
• ภาพประชาสัมพันธ์ระบุข้อความ Organic 100% และเป็นมิตรต่อสิ่งแวดล้อม

รอยืนยันรูปแบบสินค้า ขนาดบรรจุ ราคาปกติ และราคาสมาชิก
ยังไม่ระบุจำนวนสต็อก
ภาพนี้ไม่ได้ระบุอัตราใช้ กรุณาตรวจสอบฉลากหรือติดต่อผู้แนะนำก่อนใช้งาน'''
now = datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S')
with sqlite3.connect(root / 'writable/platform.sqlite') as db:
    db.row_factory = sqlite3.Row
    row = db.execute('SELECT * FROM products WHERE sku=?', (sku,)).fetchone()
    if row is None:
        cur = db.execute('''INSERT INTO products
            (sku,name,description,category,image,price_satang,stock,active,created_at)
            VALUES(?,?,?,?,?,0,0,0,?)''', (sku, 'สกาย พาวเวอร์กรีน (Sky Power Green) ตรา SKN แบบขวด',
            description, 'ผลิตภัณฑ์เพื่อการเกษตร', image, now))
        product_id = cur.lastrowid
        db.execute('''INSERT INTO audit(actor_id,action,entity,entity_id,detail,created_at)
            VALUES(NULL,?,?,?,?,?)''', ('product_created', 'products', product_id,
            'Draft from supplied 126912_0.jpg; package and price pending; zero internal price placeholder; inactive and stock unspecified.', now))
    else:
        product_id = row['id']
    product = dict(db.execute('SELECT * FROM products WHERE id=?', (product_id,)).fetchone())
(root / 'build/added-sky-power-green-draft.json').write_text(
    json.dumps(product, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps({'id': product_id, 'sku': sku, 'active': product['active'], 'image': image}))
