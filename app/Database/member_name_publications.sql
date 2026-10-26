CREATE TABLE IF NOT EXISTS member_name_publications (
 user_id INTEGER PRIMARY KEY REFERENCES users(id),
 full_name TEXT NOT NULL, consent_at TEXT NOT NULL
);
