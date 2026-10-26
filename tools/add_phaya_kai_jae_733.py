"""Import supplied pink 7-3-3 fertilizer using the user's product copy."""
from pathlib import Path
import datetime
import hashlib
import json
import sqlite3

root = Path(__file__).resolve().parents[1]
data = Path('C:/Users/phon/AppData/Local/Temp/codex-clipboard-352662a8-c476-4c18-986e-e7eaa1b2b674.png').read_bytes()
image = 'platform/uploads/' + hashlib.md5(data).hexdigest() + '.png'
(root / 'public' / image).write_bytes(data)
sku = 'SKN-PHAYA-KAI-JAE-7-3-3-50KG'
name = 'ปุ๋ยอินทรีย์เคมี พญาไก่แจ้ สูตร 7-3-3 — ถุงชมพู 50 กิโลกรัม'
short = 'ปุ๋ยอินทรีย์เคมีพญาไก่แจ้ สูตร 7-3-3 ถุงชมพู มีไนโตรเจน 7% ฟอสเฟต 3% และโพแทช 3% พร้อมอินทรียวัตถุ 20% ขนาด 50 กิโลกรัม'
description = '''ธาตุอาหารสำหรับดูแลการเจริญเติบโตของพืช พร้อมอินทรียวัตถุ

ปุ๋ยอินทรีย์เคมีตราพญาไก่แจ้ สูตร 7-3-3 บรรจุถุงสีชมพู ผสานธาตุอาหารหลักไนโตรเจน ฟอสฟอรัส และโพแทสเซียม พร้อมอินทรียวัตถุที่ภาพต้นฉบับระบุ 20% สำหรับประกอบแผนการบำรุงพืชและดูแลดินอย่างเหมาะสม

ข้อมูลสินค้า
ตราสินค้า: พญาไก่แจ้
ประเภท: ปุ๋ยอินทรีย์เคมี
สูตร N–P₂O₅–K₂O: 7-3-3
ไนโตรเจนทั้งหมด (N): 7%
ฟอสเฟตที่เป็นประโยชน์ (P₂O₅): 3%
โพแทชที่ละลายน้ำ (K₂O): 3%
อินทรียวัตถุตามภาพต้นฉบับ: 20%
น้ำหนักสุทธิ: 50 กิโลกรัม
สีบรรจุภัณฑ์: ชมพู

จุดเด่นของสูตร
• มีไนโตรเจนสูงกว่าฟอสเฟตและโพแทชในสูตรเดียวกัน
• ไนโตรเจนมีบทบาทต่อการสร้างใบและการเจริญเติบโตทางลำต้น
• มีฟอสฟอรัสและโพแทสเซียมร่วมเป็นธาตุอาหารหลัก
• มีอินทรียวัตถุประกอบในสูตร

แนวทางเลือกใช้
พิจารณาเมื่อแผนบำรุงพืชต้องการไนโตรเจนเด่นกว่าฟอสฟอรัสและโพแทสเซียม โดยเลือกให้สอดคล้องกับชนิดพืช ระยะการเจริญเติบโต และผลวิเคราะห์ดิน

วิธีใช้
ใช้ทางดินตามฉลากจริง อัตราต่อไร่หรือต่อต้นและความถี่ต้องอ้างอิงคำแนะนำของผู้ผลิตสำหรับพืชแต่ละชนิด'''
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
            ('product_created', 'products', product_id, 'User-supplied pink 7-3-3 fertilizer image/copy, 50 kg; inquiry-only pending price and stock.', now))
    result = dict(db.execute('SELECT * FROM products WHERE id=?', (product_id,)).fetchone())
(root / 'build/added-phaya-kai-jae-733.json').write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps({'id': product_id, 'sku': sku, 'image': image, 'inquiry_only': result['inquiry_only']}))
