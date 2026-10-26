"""Import supplied orange 12-4-5 fertilizer using the user's product copy."""
from pathlib import Path
import datetime
import hashlib
import json
import sqlite3

root = Path(__file__).resolve().parents[1]
data = Path('C:/Users/phon/AppData/Local/Temp/codex-clipboard-dad48bb4-2b29-4fce-8452-81370eac9006.png').read_bytes()
image = 'platform/uploads/' + hashlib.md5(data).hexdigest() + '.png'
(root / 'public' / image).write_bytes(data)
sku = 'SKN-PHAYA-KAI-JAE-12-4-5-50KG'
name = 'ปุ๋ยอินทรีย์เคมี พญาไก่แจ้ สูตร 12-4-5 — ถุงส้ม 50 กิโลกรัม'
short = 'ปุ๋ยอินทรีย์เคมีพญาไก่แจ้ สูตร 12-4-5 ถุงส้ม มีไนโตรเจน 12% ฟอสเฟต 4% และโพแทช 5% สำหรับประกอบแผนบำรุงพืช ขนาด 50 กิโลกรัม'
description = '''สูตรไนโตรเจนเด่น สำหรับประกอบแผนบำรุงใบและลำต้น

ปุ๋ยอินทรีย์เคมีตราพญาไก่แจ้ สูตร 12-4-5 บรรจุถุงสีส้ม มีไนโตรเจน 12% ร่วมกับฟอสเฟต 4% และโพแทช 5% เป็นทางเลือกสำหรับแผนการให้ธาตุอาหารที่ต้องการไนโตรเจนในสัดส่วนสูงกว่าธาตุหลักอีกสองตัว

ข้อมูลสินค้า
ตราสินค้า: พญาไก่แจ้
ประเภท: ปุ๋ยอินทรีย์เคมี
สูตร N–P₂O₅–K₂O: 12-4-5
ไนโตรเจนทั้งหมด (N): 12%
ฟอสเฟตที่เป็นประโยชน์ (P₂O₅): 4%
โพแทชที่ละลายน้ำ (K₂O): 5%
อินทรียวัตถุ: รอยืนยันจากฉลากที่อ่านชัด
น้ำหนักสุทธิ: 50 กิโลกรัม
สีบรรจุภัณฑ์: ส้ม

จุดเด่นของสูตร
• มีไนโตรเจน 12% ซึ่งสูงที่สุดในสินค้าสามสูตรชุดนี้
• เหมาะสำหรับพิจารณาในแผนบำรุงที่ต้องการส่งเสริมการเจริญเติบโตทางใบและลำต้น
• มีฟอสฟอรัสและโพแทสเซียมร่วมในสูตร
• สามารถวางแผนใช้ตามความต้องการธาตุอาหารของพืชและผลวิเคราะห์ดิน

แนวทางเลือกใช้
พิจารณาในระยะที่พืชต้องการไนโตรเจนเพื่อการเจริญเติบโต โดยใช้อัตราที่เหมาะสมกับชนิดและอายุพืช ปริมาณไนโตรเจนที่มากกว่าไม่ได้หมายความว่าเหมาะกับพืชทุกชนิดหรือทุกระยะ

วิธีใช้
ใช้ทางดินตามฉลากจริง กำหนดปริมาณและช่วงเวลาให้สัมพันธ์กับความต้องการของพืช และคำนึงถึงปุ๋ยชนิดอื่นที่ใช้ร่วมในแปลง'''
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
            ('product_created', 'products', product_id, 'User-supplied orange 12-4-5 fertilizer image/copy, 50 kg; organic matter retained as unconfirmed; inquiry-only pending price and stock.', now))
    result = dict(db.execute('SELECT * FROM products WHERE id=?', (product_id,)).fetchone())
(root / 'build/added-phaya-kai-jae-1245.json').write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps({'id': product_id, 'sku': sku, 'image': image, 'inquiry_only': result['inquiry_only']}))
