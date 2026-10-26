"""Apply latest user copy, keeping historical size and price explicitly unconfirmed."""
from pathlib import Path
import datetime
import json
import sqlite3

root = Path(__file__).resolve().parents[1]
name = 'SKN สกาย พาวเวอร์กรีน — แบบขวดฝาเขียว'
short = 'SKN สกาย พาวเวอร์กรีน แบบขวดฝาเขียว ผลิตภัณฑ์บำรุงพืชสูตรเข้มข้น สำหรับใช้ร่วมกับแผนดูแลพืชตามคำแนะนำบนฉลาก'
description = '''Sky Power Green | ผลิตภัณฑ์บำรุงพืชสูตรเข้มข้น

ผลิตภัณฑ์สำหรับนำไปใช้ร่วมกับแผนการดูแลพืช ตั้งแต่การบำรุงราก ลำต้น และใบ ไปจนถึงช่วงการเจริญเติบโต บรรจุในขวดฝาเกลียวสีเขียว ปิดเก็บได้สะดวกหลังใช้งาน

รายละเอียดสินค้า
สกาย พาวเวอร์กรีน นำเสนอส่วนประกอบ 7 กลุ่มในสื่อผลิตภัณฑ์ ได้แก่ ไคโตซาน แกนในปลาหมึก สาหร่ายทะเล ฮิวมิค กรดอะมิโน กรดแอซิติก และส่วนประกอบที่ภาพระบุว่า “โมโนโซเดียม” โดยต้องตรวจสอบชื่อสารและความเข้มข้นจากฉลากจริงอีกครั้ง

จุดเด่น
• บรรจุภัณฑ์แบบขวดฝาเกลียว เปิดใช้และปิดเก็บสะดวก
• สำหรับประกอบแผนบำรุงพืชตามคำแนะนำของผู้ผลิต
• สื่อผลิตภัณฑ์นำเสนอการใช้งานกับข้าว พืชไร่ พืชผัก และไม้ผล
• ควรเลือกวิธีใช้ให้เหมาะกับชนิดพืชและระยะการเจริญเติบโต

ข้อมูลสินค้า
แบรนด์: SKN
ชื่อสินค้า: สกาย พาวเวอร์กรีน — Sky Power Green
รูปแบบบรรจุภัณฑ์: ขวดฝาเกลียวสีเขียว
ขนาดตามสื่อก่อนหน้า: 500 มิลลิลิตร — รอยืนยันจากฉลาก
ราคาตามสื่อก่อนหน้า: 690 บาท — รอยืนยันราคาปัจจุบัน
ความเข้มข้นและส่วนประกอบรับรอง: รอยืนยันจากฉลาก

วิธีใช้
ใช้ตามวิธีและอัตราที่ระบุบนฉลากจริง ตรวจสอบก่อนว่าสูตรนี้เหมาะสำหรับฉีดพ่น ราดดิน หรือวิธีอื่น และไม่เพิ่มความเข้มข้นเอง
อัตราผสม ปริมาณต่อพื้นที่ และความถี่: รอยืนยันจากผู้ผลิต

การเก็บรักษา
ปิดฝาให้สนิท เก็บในบรรจุภัณฑ์เดิม หลีกเลี่ยงแสงแดดและความร้อน เก็บให้พ้นมือเด็ก แยกจากอาหารและอาหารสัตว์ และปฏิบัติตามข้อกำหนดบนฉลาก'''
with sqlite3.connect(root / 'writable/platform.sqlite') as db:
    db.row_factory = sqlite3.Row
    old = dict(db.execute('SELECT * FROM products WHERE id=246 AND sku=?', ('SKN-SKY-POWER-GREEN-BOTTLE',)).fetchone())
    backup = root / 'build/sky-bottle-before-revised-copy.json'
    if not backup.exists():
        backup.write_text(json.dumps(old, ensure_ascii=False, indent=2), encoding='utf-8')
    db.execute('''UPDATE products SET name=?,description=?,short_description=?,price_satang=0,
        inquiry_only=1,inquiry_label=? WHERE id=246''',
        (name, description, short, 'สอบถามรายละเอียดสินค้า'))
    now = datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S')
    db.execute('INSERT INTO audit(actor_id,action,entity,entity_id,detail,created_at) VALUES(NULL,?,?,?,?,?)',
        ('product_updated', 'products', 246, 'Applied latest user bottle copy: historical 500 ml and 690 THB explicitly pending confirmation; inquiry-only; retained current image, stock and active state.', now))
    result = dict(db.execute('SELECT * FROM products WHERE id=246').fetchone())
    assert all(result[k] == old[k] for k in ['image', 'stock', 'active', 'sku'])
(root / 'build/updated-sky-bottle-copy.json').write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps({'id': 246, 'inquiry_only': result['inquiry_only'], 'image_preserved': True}))
