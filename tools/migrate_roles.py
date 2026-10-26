import sqlite3
import json

db_path = 'writable/platform.sqlite'
conn = sqlite3.connect(db_path)
cur = conn.cursor()

# 1. Enable foreign keys off during table schema rewrite
cur.execute("PRAGMA foreign_keys=OFF")

# Check current columns of users
cur.execute("PRAGMA table_info(users)")
cols = [row[1] for row in cur.fetchall()]
print("Current users columns:", cols)

# Read all existing users data
cur.execute("SELECT * FROM users")
existing_rows = cur.fetchall()
print(f"Existing users count: {len(existing_rows)}")

# Get current table sql
cur.execute("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'")
sql = cur.fetchone()[0]
print("Original SQL:\n", sql)

# Create new table with updated check constraint including 'affiliate' and default 'affiliate'
new_table_sql = """
CREATE TABLE users_new (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 name TEXT NOT NULL,
 email TEXT NOT NULL COLLATE NOCASE UNIQUE,
 password_hash TEXT NOT NULL,
 role TEXT NOT NULL DEFAULT 'affiliate' CHECK(role IN ('admin','manager','sales','affiliate','member')),
 active INTEGER NOT NULL DEFAULT 1,
 phone TEXT NOT NULL DEFAULT '',
 referral_code TEXT NOT NULL UNIQUE,
 sponsor_id INTEGER REFERENCES users(id),
 created_at TEXT NOT NULL,
 auth_version INTEGER NOT NULL DEFAULT 0,
 line_url TEXT NOT NULL DEFAULT '',
 public_name TEXT NOT NULL DEFAULT '',
 affiliate_headline TEXT NOT NULL DEFAULT '',
 affiliate_bio TEXT NOT NULL DEFAULT '',
 affiliate_public INTEGER NOT NULL DEFAULT 0,
 bank_name TEXT NOT NULL DEFAULT '',
 bank_account TEXT NOT NULL DEFAULT '',
 bank_account_name TEXT NOT NULL DEFAULT ''
)
"""

cur.execute(new_table_sql)

# Copy data from users to users_new
col_names_str = ", ".join(cols)
placeholders = ", ".join(["?"] * len(cols))
for row in existing_rows:
    cur.execute(f"INSERT INTO users_new ({col_names_str}) VALUES ({placeholders})", row)

cur.execute("DROP TABLE users")
cur.execute("ALTER TABLE users_new RENAME TO users")

# Recreate index
cur.execute("CREATE UNIQUE INDEX IF NOT EXISTS users_email_unique ON users(email COLLATE NOCASE)")
cur.execute("CREATE UNIQUE INDEX IF NOT EXISTS users_referral_unique ON users(referral_code)")

# 2. Add default role_menus setting in settings table if not set
cur.execute("SELECT value FROM settings WHERE key='role_menus'")
existing_setting = cur.fetchone()
if not existing_setting:
    default_role_menus = {
        "affiliate": ["affiliates", "affiliate_profile", "dashboard"],
        "sales": ["dashboard", "customers", "leads", "tasks", "conversations"],
        "manager": ["dashboard", "blog", "products", "orders", "payments", "promotions", "customers", "leads", "tasks", "conversations", "affiliates", "analytics"],
        "member": []
    }
    cur.execute("INSERT OR REPLACE INTO settings (key, value) VALUES ('role_menus', ?)", (json.dumps(default_role_menus, ensure_ascii=False),))
    print("Default role_menus setting initialized.")
else:
    print("Existing role_menus found:", existing_setting[0])

conn.commit()
cur.execute("PRAGMA foreign_keys=ON")

# Verify new schema
cur.execute("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'")
print("New users SQL:\n", cur.fetchone()[0])

# Verify user count
cur.execute("SELECT id, name, email, role FROM users")
print("Users:", cur.fetchall())

conn.close()
print("Migration completed successfully!")
