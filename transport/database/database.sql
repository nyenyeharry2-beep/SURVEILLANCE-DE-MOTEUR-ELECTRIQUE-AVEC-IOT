-- =====================================================
-- Transport Scolaire - C.S LES SUPER GENIES
-- Base de données MySQL / MariaDB (InfinityFree)
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Années scolaires
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `academic_years` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `label` VARCHAR(20) NOT NULL COMMENT 'Ex: 2026-2027',
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_label` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Classes
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `classes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `section` VARCHAR(10) DEFAULT NULL,
  `ordre` INT NOT NULL DEFAULT 0,
  `statut` ENUM('actif','inactif') NOT NULL DEFAULT 'actif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Arrêts de bus
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `bus_stops` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(150) NOT NULL,
  `adresse` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `statut` ENUM('actif','inactif') NOT NULL DEFAULT 'actif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tarifs
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tariffs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL COMMENT 'Ex: Zone 1, Standard',
  `montant` DECIMAL(10,2) NOT NULL DEFAULT 50.00,
  `devise` VARCHAR(10) NOT NULL DEFAULT 'USD',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `statut` ENUM('actif','inactif') NOT NULL DEFAULT 'actif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Élèves
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `numero_dossier` VARCHAR(30) NOT NULL,
  `nom_complet` VARCHAR(200) NOT NULL,
  `classe_id` INT UNSIGNED DEFAULT NULL,
  `section` VARCHAR(10) DEFAULT NULL,
  `parent_nom` VARCHAR(200) DEFAULT NULL,
  `telephone_parent` VARCHAR(30) DEFAULT NULL,
  `telephone_parent2` VARCHAR(30) DEFAULT NULL,
  `adresse` TEXT NOT NULL,
  `arret_id` INT UNSIGNED DEFAULT NULL,
  `arret_precision` VARCHAR(255) DEFAULT NULL,
  `tariff_id` INT UNSIGNED DEFAULT NULL,
  `academic_year_id` INT UNSIGNED NOT NULL,
  `date_inscription` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `statut` ENUM('actif','inactif') NOT NULL DEFAULT 'actif',
  `verified` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Vérifié par admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_numero_dossier` (`numero_dossier`),
  KEY `idx_nom` (`nom_complet`),
  KEY `idx_classe` (`classe_id`),
  KEY `idx_arret` (`arret_id`),
  KEY `idx_year` (`academic_year_id`),
  CONSTRAINT `fk_students_classe` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_students_arret` FOREIGN KEY (`arret_id`) REFERENCES `bus_stops` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_students_tariff` FOREIGN KEY (`tariff_id`) REFERENCES `tariffs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_students_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Paiements
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `academic_year_id` INT UNSIGNED NOT NULL,
  `mois` TINYINT NOT NULL COMMENT '9=Sept, 10=Oct, 11=Nov, 12=Dec, 1=Jan, 2=Fev, 3=Mars, 4=Avril, 5=Mai, 6=Juin',
  `montant_du` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `montant_paye` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `reste` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `statut` ENUM('paye','partiel','impaye') NOT NULL DEFAULT 'impaye',
  `numero_recu` VARCHAR(30) DEFAULT NULL,
  `date_paiement` DATE DEFAULT NULL,
  `mode_paiement` VARCHAR(50) DEFAULT NULL,
  `observation` TEXT DEFAULT NULL,
  `verified_ok` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Marqué OK par admin',
  `source` ENUM('formulaire','admin') NOT NULL DEFAULT 'formulaire',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_student_month_year` (`student_id`, `mois`, `academic_year_id`),
  KEY `idx_mois` (`mois`),
  KEY `idx_statut` (`statut`),
  KEY `idx_year` (`academic_year_id`),
  CONSTRAINT `fk_payments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Utilisateurs admin
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(150) NOT NULL,
  `username` VARCHAR(80) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','superadmin') NOT NULL DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Paramètres
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Historique / Journal d'activité
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) DEFAULT NULL,
  `entity_id` INT UNSIGNED DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_action` (`action`),
  KEY `idx_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Compteur pour numéros de dossier / reçus
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `counters` (
  `name` VARCHAR(50) NOT NULL,
  `year` INT NOT NULL,
  `value` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`name`, `year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- DONNÉES INITIALES
-- =====================================================

INSERT INTO `academic_years` (`label`, `start_date`, `end_date`, `is_active`) VALUES
('2025-2026', '2025-09-01', '2026-06-30', 0),
('2026-2027', '2026-09-01', '2027-06-30', 1);

INSERT INTO `classes` (`nom`, `section`, `ordre`) VALUES
('1ère année', 'A', 1),
('1ère année', 'B', 2),
('2ème année', 'A', 3),
('2ème année', 'B', 4),
('3ème année', 'A', 5),
('3ème année', 'B', 6),
('4ème année', 'A', 7),
('4ème année', 'B', 8),
('5ème année', 'A', 9),
('5ème année', 'B', 10),
('6ème année', 'A', 11),
('6ème année', 'B', 12);

INSERT INTO `bus_stops` (`nom`, `adresse`, `description`) VALUES
('Golf Maisha', 'Q/ Golf Maisha', 'Zone principale'),
('Kenya', 'Quartier Kenya', NULL),
('Bel-Air', 'Quartier Bel-Air', NULL),
('Katuba', 'Quartier Katuba', NULL),
('Annexe', 'Commune Annexe', NULL);

INSERT INTO `tariffs` (`nom`, `montant`, `devise`, `is_default`) VALUES
('Standard', 50.00, 'USD', 1),
('Zone 1', 40.00, 'USD', 0),
('Zone 2', 50.00, 'USD', 0),
('Zone 3', 60.00, 'USD', 0);

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('school_name', 'C.S LES SUPER GENIES'),
('school_foundation', 'FONDATION EBEN EZER – ORA S.A.R.I'),
('school_project', 'PROJET EDUCATIF'),
('school_address', '26, AV. BIN MALISAWA'),
('school_quarter', 'Q/ Golf Maisha, Commune Annexe'),
('school_city', 'VILLE DE LUBUMBASHI'),
('school_email', 'cslessupergenies@gmail.com'),
('school_phone', '+243815454401 / +243858357777'),
('default_tariff', '50'),
('default_currency', 'USD'),
('active_academic_year', '2026-2027'),
('receipt_prefix', 'BUS'),
('dossier_prefix', 'BUS');

-- Mot de passe par défaut: admin123 (À CHANGER IMMÉDIATEMENT après installation)
INSERT INTO `users` (`nom`, `username`, `password`, `role`) VALUES
('Administrateur', 'admin', '$2b$12$osSzJCmpWXc1BWcZVHklAum47.JlBPHiPjOgl.q7yrdF5Ii1f7X2.', 'superadmin');

INSERT INTO `counters` (`name`, `year`, `value`) VALUES
('dossier', 2026, 0),
('receipt', 2026, 0);
