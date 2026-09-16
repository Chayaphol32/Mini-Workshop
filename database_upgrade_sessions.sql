USE coffee_rewards;

CREATE TABLE IF NOT EXISTS app_sessions (
  id VARCHAR(128) NOT NULL,
  payload MEDIUMBLOB NOT NULL,
  last_activity INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_app_sessions_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
