CREATE TABLE IF NOT EXISTS blog (
 id INTEGER PRIMARY KEY AUTOINCREMENT, slug TEXT NOT NULL UNIQUE, title TEXT NOT NULL,
 excerpt TEXT NOT NULL, category TEXT NOT NULL DEFAULT '', body TEXT NOT NULL,
 cover_image TEXT NOT NULL DEFAULT '', status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft','published','archived')),
 author_id INTEGER REFERENCES users(id), published_at TEXT, created_at TEXT NOT NULL, updated_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS blog_publication ON blog(status,published_at);
CREATE TABLE IF NOT EXISTS users (
 id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL COLLATE NOCASE UNIQUE,
 password_hash TEXT NOT NULL, role TEXT NOT NULL DEFAULT 'affiliate' CHECK(role IN ('admin','manager','sales','affiliate','member','downline_team','external_team')),
 active INTEGER NOT NULL DEFAULT 1, auth_version INTEGER NOT NULL DEFAULT 0, phone TEXT NOT NULL DEFAULT '', referral_code TEXT NOT NULL UNIQUE,
 sponsor_id INTEGER REFERENCES users(id), created_at TEXT NOT NULL,
 line_url TEXT NOT NULL DEFAULT '', public_name TEXT NOT NULL DEFAULT '', affiliate_headline TEXT NOT NULL DEFAULT '', affiliate_bio TEXT NOT NULL DEFAULT '', affiliate_public INTEGER NOT NULL DEFAULT 0,
 bank_name TEXT NOT NULL DEFAULT '', bank_account TEXT NOT NULL DEFAULT '', bank_account_name TEXT NOT NULL DEFAULT ''
);
CREATE TABLE IF NOT EXISTS product_images (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 product_id INTEGER NOT NULL REFERENCES products(id),
 image TEXT NOT NULL,
 alt TEXT NOT NULL DEFAULT '',
 sort_order INTEGER NOT NULL DEFAULT 0,
 UNIQUE(product_id,image)
);
CREATE TABLE IF NOT EXISTS products (
 id INTEGER PRIMARY KEY AUTOINCREMENT, sku TEXT NOT NULL UNIQUE, name TEXT NOT NULL, description TEXT NOT NULL DEFAULT '',
 category TEXT NOT NULL DEFAULT '', image TEXT NOT NULL DEFAULT '', source_path TEXT UNIQUE,
 price_satang INTEGER NOT NULL CHECK(price_satang>=0), stock INTEGER NOT NULL DEFAULT 0 CHECK(stock>=0), active INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL,
 inquiry_only INTEGER NOT NULL DEFAULT 0, short_description TEXT NOT NULL DEFAULT '', inquiry_label TEXT NOT NULL DEFAULT '',
 discount_kind TEXT NOT NULL DEFAULT '' CHECK(discount_kind IN ('','fixed','percent')),
 discount_value INTEGER NOT NULL DEFAULT 0 CHECK(discount_value>=0)
);
CREATE TABLE IF NOT EXISTS promotions (
 id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT NOT NULL COLLATE NOCASE UNIQUE, kind TEXT NOT NULL CHECK(kind IN ('fixed','percent')),
 value INTEGER NOT NULL CHECK(value>0), minimum_satang INTEGER NOT NULL DEFAULT 0, starts_at TEXT NOT NULL, ends_at TEXT NOT NULL,
 usage_limit INTEGER NOT NULL DEFAULT 100, used INTEGER NOT NULL DEFAULT 0, active INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS orders (
 id INTEGER PRIMARY KEY AUTOINCREMENT, number TEXT NOT NULL UNIQUE, user_id INTEGER NOT NULL REFERENCES users(id),
 idempotency_key TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'awaiting_payment', subtotal_satang INTEGER NOT NULL,
 discount_satang INTEGER NOT NULL, shipping_satang INTEGER NOT NULL, total_satang INTEGER NOT NULL CHECK(total_satang>=0),
 promo_id INTEGER REFERENCES promotions(id), recipient TEXT NOT NULL, phone TEXT NOT NULL, address TEXT NOT NULL,
 tracking TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL, updated_at TEXT NOT NULL, UNIQUE(user_id,idempotency_key)
);
CREATE TABLE IF NOT EXISTS order_items (
 id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER NOT NULL REFERENCES orders(id), product_id INTEGER NOT NULL REFERENCES products(id),
 name TEXT NOT NULL, price_satang INTEGER NOT NULL, quantity INTEGER NOT NULL CHECK(quantity>0)
);
CREATE TABLE IF NOT EXISTS payments (
 id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER NOT NULL REFERENCES orders(id), amount_satang INTEGER NOT NULL,
 reference TEXT NOT NULL, slip TEXT NOT NULL, slip_hash TEXT NOT NULL UNIQUE, mime TEXT NOT NULL,
 status TEXT NOT NULL DEFAULT 'pending', reviewed_by INTEGER REFERENCES users(id), review_note TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS one_pending_payment ON payments(order_id) WHERE status IN ('pending','approved');
CREATE TABLE IF NOT EXISTS slip_checks (
 payment_id INTEGER PRIMARY KEY REFERENCES payments(id), provider TEXT NOT NULL,
 status TEXT NOT NULL DEFAULT 'queued', attempts INTEGER NOT NULL DEFAULT 0,
 lease TEXT NOT NULL DEFAULT '', lease_until INTEGER NOT NULL DEFAULT 0,
 note TEXT NOT NULL DEFAULT '', updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS verified_transfers (
 transaction_key TEXT PRIMARY KEY, payment_id INTEGER NOT NULL UNIQUE REFERENCES payments(id),
 provider TEXT NOT NULL, verified_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS payment_tokens (
 order_id INTEGER PRIMARY KEY REFERENCES orders(id), payment_id INTEGER NOT NULL UNIQUE REFERENCES payments(id),
 token TEXT NOT NULL UNIQUE, issued_at TEXT NOT NULL, revoked_at TEXT
);
CREATE TABLE IF NOT EXISTS leads (
 id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, contact TEXT NOT NULL, source TEXT NOT NULL,
 stage TEXT NOT NULL DEFAULT 'new', score INTEGER NOT NULL DEFAULT 0, owner_id INTEGER REFERENCES users(id),
 consent_at TEXT, notes TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL, updated_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS lead_contact ON leads(contact);
CREATE TABLE IF NOT EXISTS activities (
 id INTEGER PRIMARY KEY AUTOINCREMENT, lead_id INTEGER NOT NULL REFERENCES leads(id), actor_id INTEGER REFERENCES users(id),
 note TEXT NOT NULL, created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS tasks (
 id INTEGER PRIMARY KEY AUTOINCREMENT, lead_id INTEGER REFERENCES leads(id), owner_id INTEGER REFERENCES users(id),
 title TEXT NOT NULL, due_at TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'open', created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS conversations (
 id INTEGER PRIMARY KEY AUTOINCREMENT, platform TEXT NOT NULL, external_id TEXT NOT NULL, lead_id INTEGER REFERENCES leads(id),
 mode TEXT NOT NULL DEFAULT 'auto', status TEXT NOT NULL DEFAULT 'open', ai_consent INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL, updated_at TEXT NOT NULL, UNIQUE(platform,external_id)
);
CREATE TABLE IF NOT EXISTS messages (
 id INTEGER PRIMARY KEY AUTOINCREMENT, conversation_id INTEGER NOT NULL REFERENCES conversations(id), direction TEXT NOT NULL,
 body TEXT NOT NULL, created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS outbox (
 id INTEGER PRIMARY KEY AUTOINCREMENT, conversation_id INTEGER NOT NULL REFERENCES conversations(id), message_id INTEGER NOT NULL UNIQUE REFERENCES messages(id),
 status TEXT NOT NULL DEFAULT 'queued', attempts INTEGER NOT NULL DEFAULT 0, error TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS webhook_events (event_key TEXT PRIMARY KEY, created_at TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS inbox_jobs (id INTEGER PRIMARY KEY AUTOINCREMENT, conversation_id INTEGER NOT NULL REFERENCES conversations(id), body TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'queued', created_at TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS visits (
 id INTEGER PRIMARY KEY AUTOINCREMENT, visitor TEXT NOT NULL, path TEXT NOT NULL, referrer TEXT NOT NULL DEFAULT '', source TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS visit_created ON visits(created_at);
CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS referral_tokens (token_hash TEXT PRIMARY KEY, sponsor_id INTEGER NOT NULL REFERENCES users(id), expires INTEGER NOT NULL);
CREATE TABLE IF NOT EXISTS affiliate_notes (
 owner_id INTEGER NOT NULL REFERENCES users(id), member_id INTEGER NOT NULL REFERENCES users(id),
 stage TEXT NOT NULL DEFAULT 'new', note TEXT NOT NULL DEFAULT '', updated_at TEXT NOT NULL,
 PRIMARY KEY(owner_id,member_id)
);
CREATE TABLE IF NOT EXISTS affiliate_events (
 id INTEGER PRIMARY KEY AUTOINCREMENT, sponsor_id INTEGER NOT NULL REFERENCES users(id), kind TEXT NOT NULL, created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS affiliate_events_owner ON affiliate_events(sponsor_id,kind,created_at);
CREATE TABLE IF NOT EXISTS referral_clicks (id INTEGER PRIMARY KEY AUTOINCREMENT, sponsor_id INTEGER NOT NULL REFERENCES users(id), created_at TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS gateway_attempts (
 id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER NOT NULL REFERENCES orders(id), provider TEXT NOT NULL,
 external_id TEXT UNIQUE, status TEXT NOT NULL DEFAULT 'starting', url TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS audit (
 id INTEGER PRIMARY KEY AUTOINCREMENT, actor_id INTEGER REFERENCES users(id), action TEXT NOT NULL, entity TEXT NOT NULL, entity_id INTEGER NOT NULL,
 detail TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS rate_limits (key TEXT PRIMARY KEY, hits INTEGER NOT NULL, expires INTEGER NOT NULL);
