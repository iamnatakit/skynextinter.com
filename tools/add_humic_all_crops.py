"""Import the supplied all-crops product poster without changing existing products."""
from pathlib import Path
import datetime
import hashlib
import json
import sqlite3

root = Path(__file__).resolve().parents[1]
source = Path('D:/#SKYNEX INTER/ปุ๋ย/126942_0.jpg')
data = source.read_bytes()
image = 'platform/uploads/' + hashlib.md5(data).hexdigest() + '.jpg'
(root / 'public' / image).write_bytes(data)
sku = 'SKN-HUMIC-AMINO-SILICON-50KG'
description = '''วัสดุปรับปรุงดิน ตราเพื่อนโชค ชนิดเม็ด
สูตรเข้มข้นสำหรับบำรุงพืชทุกระยะ
ขนาดบรรจุ: 50 กิโลกรัม / กระสอบ
ราคาปกติ: 990 บาท
ราคาสมาชิกตามภาพสินค้า: 790 บาท (50 PV) กรุณาติดต่อผู้แนะนำเพื่อยืนยันสิทธิ์และเงื่อนไขก่อนสั่งซื้อ

ส่วนประกอบสำคัญตามภาพสินค้า: ฮิวมิค + อะมิโน + ซิลิคอน
ข้อมูลคุณสมบัติตามภาพสินค้า:
• ฮิวมิค: ช่วยฟื้นฟูดินเสื่อมและดินแน่น เพิ่มการอุ้มน้ำและระบายอากาศ ทำให้ดินร่วนซุยและรากเดินดี
• อะมิโน: ช่วยลดความเครียดจากสภาพอากาศร้อน แล้ง และหนาว บำรุงการเจริญเติบโตและการแตกยอด
• ซิลิคอน: ช่วยเสริมความแข็งแรงของผนังเซลล์พืช ลำต้นและใบ

การบำรุงพืชตามระยะที่แสดงในภาพ:
• ระยะต้นกล้า: บำรุงราก
• ระยะเจริญเติบโต: บำรุงใบและลำต้น
• ระยะออกดอก: บำรุงดอก
• ระยะติดผล: บำรุงขนาดและน้ำหนักผล
เหมาะสำหรับ: ข้าว พืชไร่ ผัก ไม้ผล และพืชสวน

ภาพนี้ไม่ได้ระบุอัตราใช้ กรุณาปฏิบัติตามฉลากหรือติดต่อผู้แนะนำก่อนใช้งาน
ยังไม่ระบุจำนวนสต็อก กรุณาติดต่อเพื่อยืนยันสินค้าก่อนสั่งซื้อ'''
now = datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S')
with sqlite3.connect(root / 'writable/platform.sqlite') as db:
    db.row_factory = sqlite3.Row
    existing = db.execute('SELECT * FROM products WHERE sku=?', (sku,)).fetchone()
    if existing is None:
        cur = db.execute('''INSERT INTO products
            (sku,name,description,category,image,price_satang,stock,active,created_at)
            VALUES(?,?,?,?,?,?,0,1,?)''', (
                sku, 'ฮิวมิค + อะมิโน + ซิลิคอน ชนิดเม็ด สูตรบำรุงพืชทุกระยะ ตราเพื่อนโชค 50 กก.',
                description, 'วัสดุปรับปรุงดิน', image, 99000, now))
        product_id = cur.lastrowid
        db.execute('''INSERT INTO audit(actor_id,action,entity,entity_id,detail,created_at)
            VALUES(NULL,?,?,?,?,?)''', ('product_created', 'products', product_id,
            'Supplied poster 126942_0.jpg; retail 990 THB; member 790 THB and 50 PV recorded in description; stock unspecified.', now))
    else:
        product_id = existing['id']
    product = dict(db.execute('SELECT * FROM products WHERE id=?', (product_id,)).fetchone())
(root / 'build/added-humic-all-crops-product.json').write_text(
    json.dumps(product, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps({'id': product_id, 'sku': sku, 'image': image, 'stock': product['stock']}))
