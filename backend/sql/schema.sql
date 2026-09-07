-- Schéma Super Genies - Suivi paiements & inscriptions
-- Exécuter via phpMyAdmin sur InfinityFree

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricule VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    genre ENUM('Masculin', 'Féminin', 'Autre') DEFAULT 'Autre',
    date_naissance DATE NULL,
    classe VARCHAR(120) NOT NULL,
    section VARCHAR(80) DEFAULT NULL,
    telephone VARCHAR(30) DEFAULT NULL,
    annee_scolaire VARCHAR(30) NOT NULL DEFAULT '2026-2027',
    statut_inscription ENUM('Actif', 'Brouillon', 'Inactif') DEFAULT 'Actif',
    date_inscription DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_classe (classe),
    INDEX idx_section (section),
    INDEX idx_annee (annee_scolaire)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fee_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    label VARCHAR(150) NOT NULL,
    montant DECIMAL(10,2) NOT NULL DEFAULT 0,
    categorie ENUM('inscription', 'scolaire', 'transport', 'equipement', 'autre') DEFAULT 'autre',
    mois_applicable TINYINT NULL COMMENT '1-12 pour frais mensuels',
    annee_scolaire VARCHAR(30) NOT NULL DEFAULT '2026-2027',
    actif TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_type_id INT NULL,
    label VARCHAR(150) NOT NULL,
    montant_du DECIMAL(10,2) NOT NULL DEFAULT 0,
    montant_paye DECIMAL(10,2) NOT NULL DEFAULT 0,
    statut ENUM('paye', 'partiel', 'impaye', 'exempt') DEFAULT 'impaye',
    mois TINYINT NULL,
    annee_scolaire VARCHAR(30) NOT NULL DEFAULT '2026-2027',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_type_id) REFERENCES fee_types(id) ON DELETE SET NULL,
    INDEX idx_student (student_id),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS import_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_import ENUM('inscriptions', 'paiements') NOT NULL,
    fichier VARCHAR(255) NOT NULL,
    classe_detectee VARCHAR(120) NULL,
    section_detectee VARCHAR(80) NULL,
    lignes_traitees INT DEFAULT 0,
    lignes_erreur INT DEFAULT 0,
    details JSON NULL,
    imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Types de frais par défaut (Primaire & Secondaire)
INSERT IGNORE INTO fee_types (code, label, montant, categorie, annee_scolaire) VALUES
('FRAIS_CONNEXE_PRIMAIRE', 'Frais connexe (Primaire)', 30.00, 'inscription', '2026-2027'),
('FRAIS_CONNEXE_SEC_7_3', 'Frais connexe (7e-3e)', 30.00, 'inscription', '2026-2027'),
('FRAIS_CONNEXE_SEC_4_6', 'Frais connexe (4e-6e)', 50.00, 'inscription', '2026-2027'),
('SCOLAIRE_PRIMAIRE', 'Frais scolaires mensuels (Primaire)', 65.00, 'scolaire', '2026-2027'),
('SCOLAIRE_7_8_EB', 'Frais scolaires mensuels (7e-8e EB)', 65.00, 'scolaire', '2026-2027'),
('SCOLAIRE_1_GEN', 'Frais scolaires mensuels (1re Option Générale)', 70.00, 'scolaire', '2026-2027'),
('SCOLAIRE_2_3_GEN', 'Frais scolaires mensuels (2e-3e Générale)', 70.00, 'scolaire', '2026-2027'),
('SCOLAIRE_1_TECH', 'Frais scolaires mensuels (1re Technique)', 75.00, 'scolaire', '2026-2027'),
('SCOLAIRE_2_3_TECH', 'Frais scolaires mensuels (2e-3e Technique)', 75.00, 'scolaire', '2026-2027'),
('SCOLAIRE_4_GEN', 'Frais scolaires mensuels (4e Générale)', 115.00, 'scolaire', '2026-2027'),
('SCOLAIRE_4_TECH', 'Frais scolaires mensuels (4e Technique/Pétrochimie)', 120.00, 'scolaire', '2026-2027'),
('TRANSPORT_PROCHE', 'Transport (zones proches)', 20.00, 'transport', '2026-2027'),
('TRANSPORT_MATER', 'Transport (Mater Dei / Maternelle)', 25.00, 'transport', '2026-2027'),
('TRANSPORT_LOIN', 'Transport (Plateau, Lido, etc.)', 30.00, 'transport', '2026-2027'),
('KIT_COMPLET', 'Kit complet (tenue)', 35.00, 'equipement', '2026-2027'),
('KIT_CAGOULE', 'Kit complet avec cagoule', 40.00, 'equipement', '2026-2027'),
('SAC_SCOLAIRE', 'Sac scolaire', 6.00, 'equipement', '2026-2027'),
('PULLOVER', 'Pull-over cagoule', 20.00, 'equipement', '2026-2027'),
('COMBINAISON', 'Combinaison', 25.00, 'equipement', '2026-2027'),
('TENUE_GYM', 'Tenue de gymnastique', 15.00, 'equipement', '2026-2027');
