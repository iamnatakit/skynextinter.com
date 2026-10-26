CREATE TABLE IF NOT EXISTS member_photos (
 user_id INTEGER PRIMARY KEY REFERENCES users(id),
 image BLOB NOT NULL, version TEXT NOT NULL, updated_at TEXT NOT NULL
);
