CREATE TABLE IF NOT EXISTS member_profiles (
 user_id INTEGER PRIMARY KEY REFERENCES users(id), private_data TEXT NOT NULL DEFAULT '',
 submitted_at TEXT, updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS member_documents (
 id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL REFERENCES users(id),
 kind TEXT NOT NULL CHECK(kind IN ('identity','bankbook')), storage_key TEXT NOT NULL,
 mime TEXT NOT NULL, size INTEGER NOT NULL, status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','approved','rejected')),
 review_note TEXT NOT NULL DEFAULT '', reviewed_by INTEGER REFERENCES users(id), reviewed_at TEXT,
 created_at TEXT NOT NULL, UNIQUE(user_id,kind)
);
CREATE TABLE IF NOT EXISTS member_salespages (
 id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL REFERENCES users(id),
 slug TEXT NOT NULL, title TEXT NOT NULL, headline TEXT NOT NULL, intro TEXT NOT NULL DEFAULT '',
 benefits TEXT NOT NULL DEFAULT '', offer TEXT NOT NULL DEFAULT '', cta TEXT NOT NULL DEFAULT 'สอบถามรายละเอียด',
 theme TEXT NOT NULL DEFAULT 'green', cover TEXT NOT NULL DEFAULT 'platform', product_ids TEXT NOT NULL DEFAULT '[]',
 status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft','published','archived')),
 created_at TEXT NOT NULL, updated_at TEXT NOT NULL, UNIQUE(user_id,slug)
);
CREATE INDEX IF NOT EXISTS member_salespages_owner ON member_salespages(user_id,status);
