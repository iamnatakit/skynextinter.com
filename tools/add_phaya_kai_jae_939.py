"""Import the supplied blue 9-3-9 fertilizer with the user's approved copy."""
from pathlib import Path
import datetime
import hashlib
import json
import sqlite3

root = Path(__file__).resolve().parents[1]
data = Path('C:/Users/phon/AppData/Local/Temp/codex-clipboard-fd9362c8-0506-44cd-942e-f99ab995a234.png').read_bytes()
image = 'platform/uploads/' + hashlib.md5(data).hexdigest() + '.png'
(root / 'public' / image).write_bytes(data)
sku = 'SKN-PHAYA-KAI-JAE-9-3-9-50KG'
name = 'ปุ๋ยอินทรีย์เคมี พญาไก่แจ้ สูตร 9-3-9 — ถุงน้ำเงิน 50 กิโลกรัม'
short = 'ปุ๋ยอินทรีย์เคมีพญาไก่แจ้ สูตร 9-3-9 ถุงน้ำเงิน มีไนโตรเจนและโพแทชอย่างละ 9% พร้อมฟอสเฟต 3% ขนาด 50 กิโลกรัม'
description = '''ไนโตรเจนและโพแทสเซียมในสัดส่วนเท่ากัน สำหรับวางแผนบำรุงพืช

ปุ๋ยอินทรีย์เคมีตราพญาไก่แจ้ สูตร 9-3-9 บรรจุถุงสีน้ำเงิน มีไนโตรเจนและโพแทชอย่างละ 9% ร่วมกับฟอสเฟต 3% สำหรับเลือกใช้ตามความต้องการธาตุอาหารของพืชและสภาพดินในพื้นที่ปลูก

ข้อมูลสินค้า
ตราสินค้า: พญาไก่แจ้
ประเภท: ปุ๋ยอินทรีย์เคมี
สูตร N–P₂O₅–K₂O: 9-3-9
ไนโตรเจนทั้งหมด (N): 9%
ฟอสเฟตที่เป็นประโยชน์ (P₂O₅): 3%
โพแทชที่ละลายน้ำ (K₂O): 9%
อินทรียวัตถุ: รอยืนยันจากฉลากที่อ่านชัด
น้ำหนักสุทธิ: 50 กิโลกรัม
สีบรรจุภัณฑ์: น้ำเงิน

จุดเด่นของสูตร
• ไนโตรเจนและโพแทชมีสัดส่วนเท่ากัน
• ไนโตรเจนมีบทบาทต่อการสร้างใบและลำต้น
• โพแทสเซียมมีบทบาทต่อการควบคุมน้ำ การทำงานของเอนไซม์ และการเคลื่อนย้ายอาหารในพืช
• มีฟอสฟอรัสร่วมเป็นธาตุอาหารหลักในสูตร

แนวทางเลือกใช้
พิจารณาเมื่อแผนบำรุงพืชต้องการทั้งไนโตรเจนและโพแทสเซียม โดยประเมินร่วมกับธาตุอาหารที่มีอยู่ในดินและระยะการเจริญเติบโต ไม่กำหนดว่าเป็นสูตรเร่งดอกหรือเร่งผลจากตัวเลขสูตรเพียงอย่างเดียว

วิธีใช้
ใช้ทางดินตามอัตราที่ผู้ผลิตกำหนดสำหรับพืชแต่ละชนิด ตรวจสอบฉลากก่อนใช้ร่วมกับปุ๋ยหรือผลิตภัณฑ์อื่น'''
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
            (sku, name, description, 'ปุ๋ยอินทรีย์เคมี', image, now, short, 'สอบถามรายละเอียดสินค้า')).lastrowid
        db.execute('INSERT INTO audit(actor_id,action,entity,entity_id,detail,created_at) VALUES(NULL,?,?,?,?,?)',
            ('product_created', 'products', product_id, 'User-supplied blue 9-3-9 fertilizer image/copy, 50 kg; organic matter kept unconfirmed as requested; inquiry-only pending price and stock.', now))
    result = dict(db.execute('SELECT * FROM products WHERE id=?', (product_id,)).fetchone())
(root / 'build/added-phaya-kai-jae-939.json').write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps({'id': product_id, 'sku': sku, 'image': image, 'inquiry_only': result['inquiry_only']}))
