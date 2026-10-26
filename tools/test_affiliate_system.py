import sys
import sqlite3
import json

sys.stdout.reconfigure(encoding='utf-8')

db_path = r'c:\xampp\htdocs\skynextinter.com\writable\platform.sqlite'
conn = sqlite3.connect(db_path)
cursor = conn.cursor()

# 1. Check settings role_menus
cursor.execute("SELECT value FROM settings WHERE key='role_menus'")
row = cursor.fetchone()
print("1. role_menus in DB:")
if row:
    print(json.dumps(json.loads(row[0]), indent=2, ensure_ascii=False))
else:
    print("None")

# 2. Check users columns
cursor.execute("PRAGMA table_info(users)")
cols = [c[1] for c in cursor.fetchall()]
print("\n2. Bank columns present:", [c for c in cols if 'bank' in c])

# 3. Check recent users
cursor.execute("SELECT id, name, email, role, referral_code, bank_name, bank_account FROM users ORDER BY id DESC LIMIT 5")
users = cursor.fetchall()
print("\n3. Recent users:")
for u in users:
    print(f"ID: {u[0]}, Name: {u[1]}, Role: {u[3]}, Ref: {u[4]}, Bank: {u[5]} - {u[6]}")

conn.close()
