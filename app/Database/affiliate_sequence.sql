-- Fixed business invariant: 0001, 0002, ...; issued IDs are immutable and never reused.
CREATE TRIGGER IF NOT EXISTS users_affiliate_sequential_insert
BEFORE INSERT ON users
WHEN NEW.referral_code != printf('%04d', 1 + MAX(
    COALESCE((SELECT CAST(value AS INTEGER) FROM settings WHERE key='affiliate_sequence'), 0),
    COALESCE((SELECT MAX(CAST(referral_code AS INTEGER)) FROM users
      WHERE referral_code NOT GLOB '*[^0-9]*' AND referral_code = printf('%04d', CAST(referral_code AS INTEGER))), 0)
))
BEGIN SELECT RAISE(ABORT, 'Affiliate ID must follow the sequence starting at 0001'); END;

CREATE TRIGGER IF NOT EXISTS users_affiliate_immutable
BEFORE UPDATE OF referral_code ON users WHEN NEW.referral_code != OLD.referral_code
BEGIN SELECT RAISE(ABORT, 'Affiliate ID cannot be changed'); END;

CREATE TRIGGER IF NOT EXISTS users_affiliate_sequence_advance
AFTER INSERT ON users
BEGIN
    INSERT INTO settings(key,value) VALUES('affiliate_sequence', NEW.referral_code)
    ON CONFLICT(key) DO UPDATE SET value=NEW.referral_code;
END;
