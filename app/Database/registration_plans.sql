CREATE TABLE IF NOT EXISTS member_enrollments (
 user_id INTEGER PRIMARY KEY REFERENCES users(id), kind TEXT NOT NULL CHECK(kind IN ('new','existing')),
 skynext_member_code TEXT COLLATE NOCASE UNIQUE, annual_opt_in INTEGER NOT NULL DEFAULT 0,
 accepted_at TEXT, created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS platform_renewal_orders (
 id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL REFERENCES users(id),
 package_code TEXT NOT NULL DEFAULT 'PACKET-990-ANNUAL', amount_satang INTEGER NOT NULL CHECK(amount_satang>0),
 due_at TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'scheduled' CHECK(status IN ('scheduled','due','awaiting_payment','paid')),
 invoice_id INTEGER REFERENCES platform_invoices(id), created_at TEXT NOT NULL, paid_at TEXT,
 UNIQUE(user_id,due_at)
);
