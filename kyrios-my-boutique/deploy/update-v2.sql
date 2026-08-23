-- Migration v2 pour site DÉJÀ installé
-- Exécuter dans phpMyAdmin si vous avez déjà la base initiale

ALTER TABLE orders ADD COLUMN phone_number VARCHAR(30) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN payment_method ENUM('mobile_money','cash','stripe') DEFAULT 'cash';
ALTER TABLE orders ADD COLUMN payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending';
ALTER TABLE orders ADD COLUMN payment_reference VARCHAR(100) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    method ENUM('mobile_money','cash','stripe') NOT NULL,
    status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    reference VARCHAR(100) DEFAULT NULL,
    phone_number VARCHAR(30) DEFAULT NULL,
    operator VARCHAR(50) DEFAULT NULL,
    stripe_session_id VARCHAR(200) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
