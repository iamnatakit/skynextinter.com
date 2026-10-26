CREATE TABLE IF NOT EXISTS platform_subscriptions (
 user_id INTEGER PRIMARY KEY REFERENCES users(id), trial_started_at TEXT NOT NULL, trial_ends_at TEXT NOT NULL,
 paid_until TEXT, manual_lock INTEGER NOT NULL DEFAULT 0 CHECK(manual_lock IN (0,1)), created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS platform_invoices (
 id INTEGER PRIMARY KEY AUTOINCREMENT, number TEXT NOT NULL UNIQUE, user_id INTEGER NOT NULL REFERENCES users(id),
 amount_satang INTEGER NOT NULL CHECK(amount_satang>0), status TEXT NOT NULL DEFAULT 'pending'
 CHECK(status IN ('pending','checking','review','paid','expired')), created_at TEXT NOT NULL, expires_at TEXT NOT NULL, paid_at TEXT
);
CREATE UNIQUE INDEX IF NOT EXISTS platform_open_invoice ON platform_invoices(user_id) WHERE status IN ('pending','checking','review');
CREATE TABLE IF NOT EXISTS platform_slips (
 id INTEGER PRIMARY KEY AUTOINCREMENT, invoice_id INTEGER NOT NULL REFERENCES platform_invoices(id),
 slip TEXT NOT NULL, mime TEXT NOT NULL, slip_hash TEXT NOT NULL UNIQUE, provider TEXT NOT NULL,
 status TEXT NOT NULL DEFAULT 'queued' CHECK(status IN ('queued','checking','verified','review')),
 lease TEXT NOT NULL DEFAULT '', lease_until INTEGER NOT NULL DEFAULT 0,
 note TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL, updated_at TEXT NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS platform_one_slip ON platform_slips(invoice_id);
CREATE TABLE IF NOT EXISTS platform_transfers (
 transaction_key TEXT PRIMARY KEY, invoice_id INTEGER NOT NULL UNIQUE REFERENCES platform_invoices(id), verified_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS platform_emails (
 id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL REFERENCES users(id), dedupe TEXT NOT NULL UNIQUE,
 recipient TEXT NOT NULL, subject TEXT NOT NULL, body TEXT NOT NULL,
 status TEXT NOT NULL DEFAULT 'queued' CHECK(status IN ('queued','sending','sent','review')),
 attempts INTEGER NOT NULL DEFAULT 0, next_attempt_at INTEGER NOT NULL DEFAULT 0,
 created_at TEXT NOT NULL, sent_at TEXT, error TEXT NOT NULL DEFAULT ''
);
