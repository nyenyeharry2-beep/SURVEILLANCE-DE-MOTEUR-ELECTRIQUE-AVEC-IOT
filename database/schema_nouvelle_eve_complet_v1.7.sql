-- ============================================================
-- NOUVELLE EVE — BASE DE DONNÉES COMPLÈTE v1.7.2
-- Pharmacie Nouvelle Eve — mapharmaciepk.xo.je
-- ============================================================
-- Importez dans phpMyAdmin sur la base :
--   if0_42810781_mapharmacieEve
--
-- ⚠️ ATTENTION : ce script SUPPRIME toutes les tables existantes
--    puis recrée une base neuve avec données de démo.
--
-- Si vous voulez GARDER vos données actuelles, n'utilisez PAS ce
-- fichier — ouvrez plutôt install_migration.php ou reparer.php.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS mouvements_caisse;
DROP TABLE IF EXISTS journal_produits;
DROP TABLE IF EXISTS journaux_quotidiens;
DROP TABLE IF EXISTS api_tokens;
DROP TABLE IF EXISTS vente_lignes;
DROP TABLE IF EXISTS ventes;
DROP TABLE IF EXISTS achat_lignes;
DROP TABLE IF EXISTS achats;
DROP TABLE IF EXISTS medicaments;
DROP TABLE IF EXISTS fournisseurs;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS utilisateurs;

-- ── Utilisateurs ─────────────────────────────────────────────
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'pharmacien', 'caissier') NOT NULL DEFAULT 'pharmacien',
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Catégories ───────────────────────────────────────────────
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Fournisseurs ─────────────────────────────────────────────
CREATE TABLE fournisseurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    contact VARCHAR(100),
    telephone VARCHAR(30),
    email VARCHAR(150),
    adresse TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Médicaments (comprimé / plaquette / flacon) ──────────────
CREATE TABLE medicaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(200) NOT NULL,
    categorie_id INT,
    fournisseur_id INT,
    prix_achat DECIMAL(12, 2) NOT NULL DEFAULT 0,
    prix_vente DECIMAL(12, 2) NOT NULL DEFAULT 0,
    type_unite ENUM('comprime_plaquette', 'flacon') NOT NULL DEFAULT 'comprime_plaquette',
    prix_comprime DECIMAL(12, 2) NULL,
    prix_plaquette DECIMAL(12, 2) NULL,
    prix_flacon DECIMAL(12, 2) NULL,
    comprimes_par_plaquette INT NOT NULL DEFAULT 10,
    quantite_stock INT NOT NULL DEFAULT 0,
    seuil_alerte INT NOT NULL DEFAULT 10,
    date_fabrication DATE,
    date_expiration DATE,
    description TEXT,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Achats ───────────────────────────────────────────────────
CREATE TABLE achats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fournisseur_id INT,
    utilisateur_id INT NOT NULL,
    date_achat DATE NOT NULL,
    montant_total DECIMAL(12, 2) NOT NULL DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE SET NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE achat_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    achat_id INT NOT NULL,
    medicament_id INT NOT NULL,
    unite_entree ENUM('comprime', 'plaquette', 'flacon', 'unite') NOT NULL DEFAULT 'unite',
    quantite INT NOT NULL,
    stock_ajoute INT NULL,
    prix_unitaire DECIMAL(12, 2) NOT NULL,
    date_fabrication DATE,
    date_expiration DATE,
    FOREIGN KEY (achat_id) REFERENCES achats(id) ON DELETE CASCADE,
    FOREIGN KEY (medicament_id) REFERENCES medicaments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Ventes (multi-produits, annulation, journée 6h-20h) ──────
CREATE TABLE ventes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(30) NOT NULL UNIQUE,
    utilisateur_id INT NOT NULL,
    client_nom VARCHAR(150),
    date_vente DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_jour DATE NULL,
    montant_total DECIMAL(12, 2) NOT NULL DEFAULT 0,
    devise ENUM('CDF', 'USD') NOT NULL DEFAULT 'CDF',
    notes TEXT,
    annulee TINYINT(1) NOT NULL DEFAULT 0,
    annulee_at DATETIME NULL,
    annulee_par INT NULL,
    motif_annulation VARCHAR(255) NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE vente_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vente_id INT NOT NULL,
    medicament_id INT NOT NULL,
    unite_vente ENUM('comprime', 'plaquette', 'flacon', 'unite') NOT NULL DEFAULT 'unite',
    quantite INT NOT NULL,
    stock_deduit INT NULL,
    prix_unitaire DECIMAL(12, 2) NOT NULL,
    sous_total DECIMAL(12, 2) NOT NULL,
    FOREIGN KEY (vente_id) REFERENCES ventes(id) ON DELETE CASCADE,
    FOREIGN KEY (medicament_id) REFERENCES medicaments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Journal quotidien + fond caisse ────────────────────────────
CREATE TABLE journaux_quotidiens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_jour DATE NOT NULL UNIQUE,
    taux_usd_cdf DECIMAL(12, 2) NULL,
    fond_caisse_cdf DECIMAL(14, 2) NOT NULL DEFAULT 0,
    fond_caisse_usd DECIMAL(14, 2) NOT NULL DEFAULT 0,
    caisse_cloture_cdf DECIMAL(14, 2) NULL,
    caisse_cloture_usd DECIMAL(14, 2) NULL,
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

CREATE TABLE journal_produits (
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

-- ── Caisse entrées / sorties ───────────────────────────────────
CREATE TABLE mouvements_caisse (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('entree', 'sortie') NOT NULL,
    montant DECIMAL(12, 2) NOT NULL,
    devise ENUM('CDF', 'USD') NOT NULL DEFAULT 'CDF',
    motif VARCHAR(255) NOT NULL,
    utilisateur_id INT NOT NULL,
    date_mouvement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    INDEX idx_date (date_mouvement),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── API mobile / vendeur PC ────────────────────────────────────
CREATE TABLE api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DONNÉES INITIALES
-- Mot de passe admin : admin123
-- ============================================================

INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES
('Administrateur', 'admin@pharmagest.local', '$2y$10$mc4enZqnMCVwApE7EvTeZ.aRdFDafB3ycsLFAQs5KwJOwl1aMpGdG', 'admin');

INSERT INTO categories (nom, description) VALUES
('Antibiotiques', 'Médicaments antibactériens'),
('Antalgiques', 'Médicaments contre la douleur'),
('Vitamines', 'Compléments vitaminiques'),
('Antipaludéens', 'Traitement du paludisme'),
('Dermatologie', 'Produits pour la peau');

INSERT INTO fournisseurs (nom, contact, telephone, email) VALUES
('Labo Pharma CI', 'M. Koné', '+225 07 00 00 01', 'contact@labopharma.ci'),
('DistribMed Afrique', 'Mme Diallo', '+225 07 00 00 02', 'info@distribmed.ci');

INSERT INTO medicaments (
    code, nom, categorie_id, fournisseur_id,
    prix_achat, prix_vente, type_unite,
    prix_comprime, prix_plaquette, prix_flacon, comprimes_par_plaquette,
    quantite_stock, seuil_alerte, date_fabrication, date_expiration
) VALUES
('MED-001', 'Paracétamol 500mg', 2, 1, 150, 300, 'comprime_plaquette', 300, 3000, NULL, 10, 500, 50, '2024-06-01', '2027-06-30'),
('MED-002', 'Amoxicilline 500mg', 1, 1, 800, 1500, 'comprime_plaquette', 1500, 15000, NULL, 10, 120, 20, '2025-01-15', '2026-12-31'),
('MED-003', 'Vitamine C 1000mg', 3, 2, 400, 750, 'comprime_plaquette', 750, 7500, NULL, 10, 8, 15, '2025-08-01', '2027-03-15'),
('MED-004', 'Artéméther-Luméfantrine', 4, 2, 1200, 2500, 'comprime_plaquette', 2500, 25000, NULL, 10, 45, 10, '2025-02-01', '2026-08-20'),
('MED-005', 'Crème hydrocortisone 1%', 5, 1, 600, 1200, 'comprime_plaquette', 1200, 12000, NULL, 10, 3, 10, '2024-11-01', '2026-05-01'),
('MED-006', 'Sirop toux 120ml', 2, 1, 900, 1800, 'flacon', NULL, NULL, 1800, 10, 24, 5, '2025-03-01', '2027-01-31');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 12 tables :
--   utilisateurs, categories, fournisseurs, medicaments
--   achats, achat_lignes, ventes, vente_lignes
--   journaux_quotidiens, journal_produits, mouvements_caisse
--   api_tokens
-- ============================================================
