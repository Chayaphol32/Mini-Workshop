USE coffee_rewards;

CREATE TABLE IF NOT EXISTS menu_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255) NOT NULL DEFAULT '',
  price DECIMAL(10,2) NOT NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_menu_status_name (status, name),
  CONSTRAINT chk_menu_price_positive CHECK (price > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  member_id INT UNSIGNED NOT NULL,
  status ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  total_amount DECIMAL(10,2) NOT NULL,
  purchase_id INT UNSIGNED NULL,
  completed_at DATETIME NULL,
  cancelled_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_purchase (purchase_id),
  KEY idx_orders_user_created (user_id, created_at),
  KEY idx_orders_member_status (member_id, status),
  KEY idx_orders_status_created (status, created_at),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_orders_member FOREIGN KEY (member_id) REFERENCES members(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_orders_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_orders_total_positive CHECK (total_amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  menu_item_id INT UNSIGNED NOT NULL,
  item_name VARCHAR(120) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  quantity TINYINT UNSIGNED NOT NULL,
  line_total DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_order_items_order (order_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_order_items_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_order_items_quantity CHECK (quantity BETWEEN 1 AND 99)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO menu_items (name, description, price, status)
SELECT seed.name, seed.description, seed.price, 'active'
FROM (
  SELECT 'อเมริกาโน่' AS name, 'กาแฟดำหอมเข้ม' AS description, 55.00 AS price
  UNION ALL SELECT 'ลาเต้', 'กาแฟนมเนื้อนุ่ม', 65.00
  UNION ALL SELECT 'คาปูชิโน่', 'กาแฟนมพร้อมฟองนม', 70.00
  UNION ALL SELECT 'ชาไทย', 'ชาไทยหอมหวาน', 50.00
  UNION ALL SELECT 'ครัวซองต์เนยสด', 'อบใหม่กรอบนอกนุ่มใน', 45.00
) seed
WHERE NOT EXISTS (SELECT 1 FROM menu_items LIMIT 1);
