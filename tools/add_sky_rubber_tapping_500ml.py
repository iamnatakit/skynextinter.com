"""Import the supplied blue-cap rubber-tapping product with user-approved copy."""
from pathlib import Path
import datetime
import hashlib
import json
import sqlite3

root = Path(__file__).resolve().parents[1]
data = Path('C:/Users/phon/AppData/Local/Temp/codex-clipboard-eada4556-f270-4bf1-bd4b-e53852d78ab4.png').read_bytes()
image = 'platform/uploads/' + hashlib.md5(data).hexdigest() + '.png'
(root / 'public' / image).write_bytes(data)
sku = 'SKN-SKY-POWER-GREEN-RUBBER-500ML'
name = 'SKN สกาย พาวเวอร์กรีน — สารเสริมประสิทธิภาพสำหรับทาหน้ายางพารา 500 มล. ฝาสีน้ำเงิน'
short = 'SKN สกาย พาวเวอร์กรีน สารเสริมประสิทธิภาพสำหรับทาหน้ายางพารา ขนาด 500 มล. ขวดฝาสีน้ำเงิน สำหรับใช้ตามคำแนะนำของผู้ผลิต'
description = '''สารเสริมประสิทธิภาพสำหรับทาหน้ายางพารา ขนาด 500 มล.

ผลิตภัณฑ์สำหรับทาหน้ายางพารา ภายใต้แบรนด์ SKN — Sky Power Green บรรจุขวดสีขาว ฝาสีน้ำเงิน สำหรับใช้ตามคำแนะนำของผู้ผลิตในการดูแลหน้ายางพารา

ข้อมูลสินค้า
แบรนด์: SKN
ชื่อผลิตภัณฑ์: สกาย พาวเวอร์กรีน
ประเภทตามภาพ: สารเสริมประสิทธิภาพสำหรับทาหน้ายางพารา
ปริมาตรสุทธิ: 500 มิลลิลิตร
บรรจุภัณฑ์: ขวดสีขาว ฝาเกลียวสีน้ำเงิน
การใช้งาน: สำหรับทาหน้ายางพารา
จำนวนต้นตามข้อความในภาพ: ใช้ได้ 100 ต้น โดยยังไม่ระบุปริมาณต่อครั้งและจำนวนครั้ง
ราคา: รอยืนยัน

จุดเด่นที่ระบุในสื่อผลิตภัณฑ์
• ช่วยเพิ่มปริมาณน้ำยาง
• น้ำยางไหลดี
• ยางแห้งเร็ว คุณภาพดี
• ลดการสูญเสียน้ำยาง
• ใช้งานง่าย

ข้อความข้างต้นเป็นคุณสมบัติที่ระบุในภาพโฆษณา ยังไม่ใช่ผลทดสอบที่ได้รับการตรวจสอบ ผลการใช้งานอาจแตกต่างตามสภาพต้นยางและวิธีใช้

เหมาะสำหรับ
เกษตรกรและผู้ดูแลสวนยางพาราที่ต้องการผลิตภัณฑ์สำหรับทาหน้ายาง โดยต้องตรวจสอบความเหมาะสมกับอายุต้นยาง สภาพหน้ายาง และระบบกรีดจากคำแนะนำของผู้ผลิตก่อนใช้

วิธีใช้
ใช้ทาหน้ายางพาราตามฉลากจริง โดยตรวจสอบข้อมูลต่อไปนี้ก่อนใช้งาน:

• ต้องผสมน้ำหรือใช้โดยตรง
• ปริมาณที่ใช้ต่อต้น
• ตำแหน่งและช่วงเวลาที่ทา
• ความถี่และระยะเว้นการใช้
• ข้อจำกัดสำหรับต้นยางหรือหน้ายางที่มีปัญหา

ภาพล่าสุดยังไม่ระบุรายละเอียดเหล่านี้ จึงไม่ควรกำหนดอัตราใช้เองจากข้อความ “ใช้ได้ 100 ต้น”

การเก็บรักษาและข้อควรระวัง
ปิดฝาให้สนิท เก็บในภาชนะเดิมพร้อมฉลาก หลีกเลี่ยงแสงแดดและความร้อน เก็บให้พ้นมือเด็ก แยกจากอาหารและอาหารสัตว์ หลีกเลี่ยงการสัมผัสดวงตาและปฏิบัติตามข้อควรระวังบนฉลาก'''
now = datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S')
with sqlite3.connect(root / 'writable/platform.sqlite') as db:
    db.row_factory = sqlite3.Row
    existing = db.execute('SELECT id FROM products WHERE sku=?', (sku,)).fetchone()
    if existing:
        product_id = existing['id']
    else:
        product_id = db.execute('''INSERT INTO products
            (sku,name,description,category,image,price_satang,stock,active,created_at,inquiry_only,short_description,inquiry_label)
            VALUES(?,?,?,?,?,0,0,1,?,1,?,?)''',
            (sku, name, description, 'ผลิตภัณฑ์สำหรับสวนยางพารา', image, now, short, 'สอบถามราคาและวิธีใช้')).lastrowid
        db.execute('INSERT INTO audit(actor_id,action,entity,entity_id,detail,created_at) VALUES(NULL,?,?,?,?,?)',
            ('product_created', 'products', product_id, 'User-supplied blue-cap 500 ml rubber-tapping product image/copy; advertising caveat and unconfirmed usage retained; inquiry-only pending price and stock.', now))
    result = dict(db.execute('SELECT * FROM products WHERE id=?', (product_id,)).fetchone())
(root / 'build/added-sky-rubber-tapping-500ml.json').write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps({'id': product_id, 'sku': sku, 'image': image, 'inquiry_only': result['inquiry_only']}))
