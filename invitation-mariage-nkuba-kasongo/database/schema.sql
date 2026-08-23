-- invitation-mariage-nkuba-kasongo
-- Importer dans phpMyAdmin (base: if0_42732689_mariage1)

CREATE TABLE IF NOT EXISTS guests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(255) NOT NULL,
  whatsapp VARCHAR(32) NOT NULL DEFAULT '',
  table_zone VARCHAR(128) DEFAULT '',
  seats INT UNSIGNED NOT NULL DEFAULT 1,
  style_id VARCHAR(64) NOT NULL DEFAULT 'mariage-civil',
  sent TINYINT(1) NOT NULL DEFAULT 0,
  device_id BIGINT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_whatsapp (whatsapp),
  INDEX idx_table (table_zone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_config (
  config_key VARCHAR(64) NOT NULL PRIMARY KEY,
  config_value TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO event_config (config_key, config_value) VALUES
  ('event_date', 'Vendredi, le 11 Septembre 2026'),
  ('event_time', '11h00'),
  ('event_venue', 'Commune de Kipushi, Ville de KIPUSHI'),
  ('whatsapp_message', 'Bonjour {NAME}, nous avons l''honneur de vous inviter au mariage civil de nos enfants, Moïse NKUBA & Sarah KASONGO, le {DATE} à {VENUE}. Votre présence fera notre immense joie.')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);
