CREATE TABLE IF NOT EXISTS member_promotion_preferences (
 user_id INTEGER PRIMARY KEY REFERENCES users(id),
 target TEXT NOT NULL CHECK(target IN ('home','salespage')),
 salespage_id INTEGER REFERENCES member_salespages(id) ON DELETE SET NULL,
 updated_at TEXT NOT NULL
);
