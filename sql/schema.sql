-- =============================================================================
-- NetworkMemories — MGO2 Nomad — Database Schema
-- =============================================================================

CREATE TABLE IF NOT EXISTS accounts (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(32) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    banned        TINYINT(1) NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login    DATETIME NULL,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sessions (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id INT UNSIGNED NOT NULL,
    token      VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS players (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id INT UNSIGNED NOT NULL UNIQUE,
    `rank`     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    kills      INT UNSIGNED NOT NULL DEFAULT 0,
    deaths     INT UNSIGNED NOT NULL DEFAULT 0,
    wins       INT UNSIGNED NOT NULL DEFAULT 0,
    losses     INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rooms (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(64) NOT NULL,
    host_id    INT UNSIGNED NOT NULL,
    map        VARCHAR(32) NULL,
    mode       VARCHAR(32) NULL,
    max_players TINYINT UNSIGNED NOT NULL DEFAULT 8,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at  DATETIME NULL,
    FOREIGN KEY (host_id) REFERENCES accounts(id) ON DELETE CASCADE,
    INDEX idx_open (closed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
