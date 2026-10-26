CREATE TABLE IF NOT EXISTS pending_referrals (
 user_id INTEGER PRIMARY KEY REFERENCES users(id),
 group_code TEXT NOT NULL DEFAULT '00000' CHECK(group_code='00000'),
 created_at TEXT NOT NULL,
 assigned_to INTEGER REFERENCES users(id),
 assigned_by INTEGER REFERENCES users(id),
 assigned_at TEXT
);
CREATE INDEX IF NOT EXISTS pending_referrals_queue ON pending_referrals(assigned_at,user_id);
