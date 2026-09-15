CREATE DATABASE IF NOT EXISTS coffee_rewards
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE coffee_rewards;

DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS members;

CREATE TABLE members (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_no VARCHAR(20) NOT NULL,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  joined_at DATE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_members_member_no (member_no),
  KEY idx_members_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchases (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id INT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  points INT UNSIGNED NOT NULL,
  status ENUM('active', 'cancelled') NOT NULL DEFAULT 'active',
  purchased_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cancelled_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_purchases_member_status (member_id, status),
  CONSTRAINT fk_purchases_member
    FOREIGN KEY (member_id) REFERENCES members(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT chk_purchases_amount_positive CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO members (member_no, name, phone, joined_at) VALUES
  ('M0001', 'สมชาย ใจดี', '0812345678', '2026-09-01'),
  ('M0002', 'สุดา ใจดี', '0898765432', '2026-09-02'),
  ('M0003', 'สมชาย ใจดี', '0861112222', '2026-09-03');

INSERT INTO purchases (member_id, amount, points, status, purchased_at) VALUES
  (1, 125.00, 12, 'active', '2026-09-10 09:30:00'),
  (1, 85.00, 8, 'active', '2026-09-11 14:15:00'),
  (2, 50.00, 5, 'cancelled', '2026-09-11 10:00:00');
