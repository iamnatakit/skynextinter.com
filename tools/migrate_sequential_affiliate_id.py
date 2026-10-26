import sqlite3
import sys

sys.stdout.reconfigure(encoding='utf-8')

db_path = 'writable/platform.sqlite'
conn = sqlite3.connect(db_path)
cur = conn.cursor()

print("Current users before migration:")
cur.execute("SELECT id, name, email, role, referral_code FROM users ORDER BY id ASC")
users = cur.fetchall()
for u in users:
    print(f"ID {u[0]}: {u[1]} ({u[2]}) - {u[3]} - Code: {u[4]}")

# Update existing users to sequential 4-digit zero-padded numbers starting at 0001
code_counter = 1
for u in users:
    user_id = u[0]
    existing_code = str(u[4]).strip()
    new_code = str(code_counter).zfill(4)
    cur.execute("UPDATE users SET referral_code=? WHERE id=?", (new_code, user_id))
    print(f"Updated user #{user_id} ({u[1]}): {existing_code} -> {new_code}")
    code_counter += 1

conn.commit()

print("\nUsers after migration:")
cur.execute("SELECT id, name, email, role, referral_code FROM users ORDER BY id ASC")
for u in cur.fetchall():
    print(f"ID {u[0]}: {u[1]} ({u[2]}) - {u[3]} - Code: {u[4]}")

conn.close()
print("\nMigration to 0001 sequential Affiliate IDs completed successfully!")
