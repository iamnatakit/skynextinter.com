"""Replace the sachet listing with the user's revised box packaging details."""
from pathlib import Path
import datetime
import hashlib
import json
import sqlite3

root = Path(__file__).resolve().parents[1]
data = Path('C:/Users/phon/AppData/Local/Temp/codex-clipboard-266612ea-a785-4df9-9683-6ed54f1aa1ff.png').read_bytes()
image = 'platform/uploads/' + hashlib.md5(data).hexdigest() + '.png'
(root / 'public' / image).write_bytes(data)
name = 'SKN สกาย พาวเวอร์กรีน — แบบกล่องบรรจุซอง'
short = 'SKN สกาย พาวเวอร์กรีน แบบกล่องบรรจุซอง สำหรับเตรียมใช้งานตามคำแนะนำเฉพาะสูตร พร้อมบรรจุภัณฑ์แยกซองที่จัดเก็บสะดวก'
description = '''Sky Power Green | บรรจุภัณฑ์แบบซองภายในกล่อง

ผลิตภัณฑ์สกาย พาวเวอร์กรีนในรูปแบบกล่องบรรจุซองสีเขียว สำหรับผู้ที่ต้องการบรรจุภัณฑ์แยกซอง เพื่อจัดเตรียมการใช้งานและเก็บรักษาตามคำแนะนำของผู้ผลิต

รายละเอียดสินค้า
ภาพต้นฉบับแสดงกล่อง SKN สีเขียว–ทอง ภายในมีซองบรรจุผลิตภัณฑ์ อย่างไรก็ตาม ยังไม่มีข้อมูลชัดเจนว่ามีกี่ซองต่อกล่อง น้ำหนักต่อซองเท่าไร และเป็นสูตรเดียวกับแบบขวดหรือไม่ จึงต้องยืนยันข้อมูลเหล่านี้ก่อนกำหนดหน่วยขาย

จุดเด่น
• บรรจุภัณฑ์แยกซองภายในกล่อง
• สะดวกต่อการจัดเตรียมและแบ่งเก็บเป็นซอง
• สามารถแสดงจำนวนซองและน้ำหนักต่อซองบนหน้าสินค้าได้เมื่อยืนยันข้อมูลแล้ว
• ใช้ตามคำแนะนำเฉพาะของสูตรแบบซอง

ข้อมูลสินค้า
แบรนด์: SKN
ชื่อสินค้า: สกาย พาวเวอร์กรีน — Sky Power Green
รูปแบบบรรจุภัณฑ์: กล่องบรรจุซองสีเขียว
จำนวนซองต่อกล่อง: รอยืนยัน
น้ำหนักต่อซอง / น้ำหนักรวม: รอยืนยัน
ลักษณะผลิตภัณฑ์ภายในซอง: รอยืนยันจากฉลาก
ส่วนประกอบและความเข้มข้น: รอยืนยันจากฉลาก
ราคาต่อซอง / ต่อกล่อง: รอยืนยัน

วิธีใช้
เปิดซองและเตรียมผลิตภัณฑ์ตามคำแนะนำบนฉลาก ใช้อุปกรณ์ตวงที่เหมาะสม และตรวจสอบอัตราผสมเฉพาะสูตร
อัตราผสม วิธีใช้ และความถี่: รอยืนยันจากผู้ผลิต โดยไม่ใช้อัตราของแบบขวดแทนโดยอัตโนมัติ

การเก็บรักษา
เก็บซองในที่แห้ง หลีกเลี่ยงแสงแดด ความร้อน และความชื้น หากเปิดซองแล้ว ให้เก็บตามคำแนะนำบนฉลาก เก็บให้พ้นมือเด็กและแยกจากอาหาร'''
with sqlite3.connect(root / 'writable/platform.sqlite') as db:
    db.row_factory = sqlite3.Row
    old = dict(db.execute('SELECT * FROM products WHERE id=247 AND sku=?', ('SKN-SKY-POWER-GREEN-BOX',)).fetchone())
    backup = root / 'build/sky-box-before-revised-copy.json'
    if not backup.exists():
        backup.write_text(json.dumps(old, ensure_ascii=False, indent=2), encoding='utf-8')
    db.execute('''UPDATE products SET name=?,description=?,short_description=?,image=?,price_satang=0,
        inquiry_only=1,inquiry_label=? WHERE id=247''',
        (name, description, short, image, 'สอบถามรายละเอียดสินค้า'))
    now = datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S')
    db.execute('INSERT INTO audit(actor_id,action,entity,entity_id,detail,created_at) VALUES(NULL,?,?,?,?,?)',
        ('product_updated', 'products', 247, 'Applied latest user box/sachet copy and image; superseded 50 g and demo price with pending label/price; inquiry-only, stock and active state preserved.', now))
    result = dict(db.execute('SELECT * FROM products WHERE id=247').fetchone())
    assert result['stock'] == old['stock'] and result['active'] == old['active']
(root / 'build/updated-sky-box-product.json').write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps({'id': 247, 'image': image, 'inquiry_only': result['inquiry_only']}))
