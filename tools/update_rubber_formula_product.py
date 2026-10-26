"""Apply the user's revised rubber-plantation copy and archive conflicting posters."""
from pathlib import Path
import datetime
import hashlib
import json
import sqlite3

root = Path(__file__).resolve().parents[1]
data = Path('C:/Users/phon/AppData/Local/Temp/codex-clipboard-c39d37de-fae9-47d5-a5a0-1c6ff41ee050.png').read_bytes()
image = 'platform/uploads/' + hashlib.md5(data).hexdigest() + '.png'
(root / 'public' / image).write_bytes(data)
name = 'สูตรสวนยางพารา — วัสดุปรับปรุงดินชนิดเม็ด 50 กิโลกรัม'
short = 'วัสดุปรับปรุงดินชนิดเม็ด สูตรสวนยางพารา สำหรับใช้ร่วมกับแผนดูแลดินและบำรุงสวน ขนาด 50 กิโลกรัม'
description = '''สูตรถุงเหลือง–แดง สำหรับการดูแลดินในสวนยางพารา ขนาด 50 กิโลกรัม

ดูแลสวนยางเริ่มต้นที่พื้นฐานของดิน ด้วยวัสดุปรับปรุงดินชนิดเม็ดสูตรสวนยางพารา สำหรับใช้ร่วมกับแผนการจัดการธาตุอาหาร น้ำ และพื้นที่สวนอย่างเหมาะสม

รายละเอียดสินค้า
ผลิตภัณฑ์สูตรสวนยางพาราเป็นวัสดุปรับปรุงดินชนิดเม็ด บรรจุถุงสีเหลือง–แดง สื่อประกอบระบุ Humic, Silicon และ Silica ส่วนข้อความด้านหน้าถุงในภาพรวมอ่านได้ว่า “ฮิวมิค + ซิลิคอน + อะมิโน” จึงต้องตรวจสอบฉลากจริงเพื่อยืนยันส่วนประกอบให้ตรงกันก่อนเผยแพร่รายละเอียดสูตร

จุดเด่นของสูตร
• ระบุการใช้งานสำหรับสวนยางพารา: แยกจากสูตรทั่วไปอย่างชัดเจน
• ใช้ทางดิน: สำหรับประกอบแผนการดูแลดินในสวน
• รูปแบบชนิดเม็ด: ใช้ตามตำแหน่งและวิธีที่ผู้ผลิตแนะนำ
• ขนาด 50 กิโลกรัม: วางแผนปริมาณใช้ตามจำนวนต้น อายุยาง และคำแนะนำบนฉลาก

เหมาะกับใคร
เจ้าของสวนยางพาราที่ต้องการดูแลสภาพดินร่วมกับแผนบำรุงสวน โดยควรเลือกอัตราใช้ให้ตรงกับอายุต้นยางและสภาพพื้นที่

วิธีใช้
ใช้ทางดินตามฉลาก ไม่ใช้ทาหน้ายาง อัตราและตำแหน่งใส่ต้องพิจารณาตามอายุต้นยางและคำแนะนำของผู้ผลิต
อัตราต่อต้น ช่วงอายุยาง และความถี่ในการใช้: รอยืนยันจากฉลากจริง

ข้อมูลสินค้า
สูตร: สำหรับสวนยางพารา
ประเภทตามข้อความบนถุง: วัสดุปรับปรุงดิน
ลักษณะ: ชนิดเม็ด
บรรจุภัณฑ์: ถุงเหลือง–แดง
น้ำหนักสุทธิ: 50 กิโลกรัม
ส่วนประกอบและสัดส่วน: รอยืนยัน เนื่องจากข้อความในสื่อแต่ละภาพไม่ตรงกัน
เลขทะเบียนและผู้ผลิต: รอยืนยันจากฉลาก'''
with sqlite3.connect(root / 'writable/platform.sqlite') as db:
    db.row_factory = sqlite3.Row
    old = dict(db.execute('SELECT * FROM products WHERE id=244 AND sku=?', ('SKN-RUBBER-HUMIC-50KG',)).fetchone())
    gallery = [dict(r) for r in db.execute('SELECT * FROM product_images WHERE product_id=244')]
    backup = root / 'build/rubber-formula-before-revised-copy.json'
    if not backup.exists():
        backup.write_text(json.dumps({'product': old, 'gallery': gallery}, ensure_ascii=False, indent=2), encoding='utf-8')
    db.execute('''UPDATE products SET name=?,description=?,short_description=?,image=?,
        inquiry_only=1,inquiry_label=?,active=1 WHERE id=244''',
        (name, description, short, image, 'สอบถามการใช้งานสำหรับสวนยาง'))
    # Old posters have unconfirmed ingredients/rates; retained on disk and in the backup above.
    db.execute('DELETE FROM product_images WHERE product_id=244')
    now = datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S')
    db.execute('INSERT INTO audit(actor_id,action,entity,entity_id,detail,created_at) VALUES(NULL,?,?,?,?,?)',
        ('product_updated', 'products', 244, 'Applied user revised rubber-plantation copy and image; archived old gallery metadata with conflicting claims; price and stock preserved; inquiry CTA enabled.', now))
    result = dict(db.execute('SELECT * FROM products WHERE id=244').fetchone())
    assert result['price_satang'] == old['price_satang'] and result['stock'] == old['stock']
(root / 'build/updated-rubber-formula-product.json').write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps({'id': 244, 'image': image, 'price_satang': result['price_satang'], 'inquiry_only': result['inquiry_only']}))
