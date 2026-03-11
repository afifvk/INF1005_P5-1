-- Run this on an existing users table that already has first_name/last_name/address/role/email/password.
-- If your current users table still uses the old username-only structure, use schema.sql instead.

ALTER TABLE users
    ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER role,
    ADD COLUMN verification_token_hash CHAR(64) NULL AFTER is_verified,
    ADD COLUMN verification_expires_at DATETIME NULL AFTER verification_token_hash,
    ADD COLUMN verification_last_sent_at DATETIME NULL AFTER verification_expires_at,
    ADD COLUMN verified_at DATETIME NULL AFTER verification_last_sent_at,
    ADD INDEX idx_verification_token_hash (verification_token_hash);

-- Mark existing accounts as verified if you do not want to force old users through the new flow.
    ADD COLUMN password_reset_token_hash CHAR(64) NULL AFTER verified_at,
    ADD COLUMN password_reset_expires_at DATETIME NULL AFTER password_reset_token_hash,
    ADD COLUMN password_reset_requested_at DATETIME NULL AFTER password_reset_expires_at,
    ADD COLUMN password_reset_at DATETIME NULL AFTER password_reset_requested_at,
    ADD INDEX idx_verification_token_hash (verification_token_hash),
    ADD INDEX idx_password_reset_token_hash (password_reset_token_hash);

UPDATE users
SET is_verified = 1,
    verified_at = COALESCE(verified_at, NOW())
WHERE is_verified = 0;
