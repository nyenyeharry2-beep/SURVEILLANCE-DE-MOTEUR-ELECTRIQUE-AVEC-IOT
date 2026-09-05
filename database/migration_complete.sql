-- ============================================================
-- NOUVELLE EVE — Migration complète (base EXISTANTE)
-- À exécuter si vous avez déjà importé une ancienne version
-- Conserve vos données existantes
-- ============================================================
-- Exécutez dans phpMyAdmin sur : if0_42810781_mapharmacieEve
-- Si une colonne/table existe déjà, ignorez l'erreur correspondante
-- ============================================================

SET NAMES utf8mb4;

-- 1. Devise CDF / USD sur les ventes
ALTER TABLE ventes
    ADD COLUMN devise ENUM('CDF', 'USD') NOT NULL DEFAULT 'CDF' AFTER montant_total;

-- 2. Dates fabrication
ALTER TABLE medicaments
    ADD COLUMN date_fabrication DATE NULL AFTER seuil_alerte;

ALTER TABLE achat_lignes
    ADD COLUMN date_fabrication DATE NULL AFTER prix_unitaire;

-- 3. Journal quotidien
CREATE TABLE IF NOT EXISTS journaux_quotidiens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_jour DATE NOT NULL UNIQUE,
    stock_initial_cdf DECIMAL(14, 2) NOT NULL DEFAULT 0,
    stock_initial_usd DECIMAL(14, 2) NOT NULL DEFAULT 0,
    entrees_cdf DECIMAL(14, 2) NOT NULL DEFAULT 0,
    entrees_usd DECIMAL(14, 2) NOT NULL DEFAULT 0,
    sorties_cdf DECIMAL(14, 2) NOT NULL DEFAULT 0,
    sorties_usd DECIMAL(14, 2) NOT NULL DEFAULT 0,
    stock_final_cdf DECIMAL(14, 2) NOT NULL DEFAULT 0,
    stock_final_usd DECIMAL(14, 2) NOT NULL DEFAULT 0,
    cloture TINYINT(1) NOT NULL DEFAULT 0,
    utilisateur_id INT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cloture_at TIMESTAMP NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS journal_produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    journal_id INT NOT NULL,
    medicament_id INT NOT NULL,
    stock_initial INT NOT NULL DEFAULT 0,
    entrees INT NOT NULL DEFAULT 0,
    sorties INT NOT NULL DEFAULT 0,
    stock_final INT NOT NULL DEFAULT 0,
    stock_final_manuel INT NULL,
    valeur_initial_cdf DECIMAL(14, 2) NOT NULL DEFAULT 0,
    valeur_entrees_cdf DECIMAL(14, 2) NOT NULL DEFAULT 0,
    valeur_sorties_cdf DECIMAL(14, 2) NOT NULL DEFAULT 0,
    valeur_final_cdf DECIMAL(14, 2) NOT NULL DEFAULT 0,
    UNIQUE KEY uk_journal_med (journal_id, medicament_id),
    FOREIGN KEY (journal_id) REFERENCES journaux_quotidiens(id) ON DELETE CASCADE,
    FOREIGN KEY (medicament_id) REFERENCES medicaments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API mobile (tokens)
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
