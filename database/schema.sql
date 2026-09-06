-- Nouvelle Eve - Schéma de base de données
-- Importez ce fichier via phpMyAdmin sur InfinityFree

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'pharmacien', 'caissier') NOT NULL DEFAULT 'pharmacien',
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fournisseurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    contact VARCHAR(100),
    telephone VARCHAR(30),
    email VARCHAR(150),
    adresse TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS medicaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(200) NOT NULL,
    categorie_id INT,
    fournisseur_id INT,
    prix_achat DECIMAL(12, 2) NOT NULL DEFAULT 0,
    prix_vente DECIMAL(12, 2) NOT NULL DEFAULT 0,
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

CREATE TABLE IF NOT EXISTS achats (
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

CREATE TABLE IF NOT EXISTS achat_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    achat_id INT NOT NULL,
    medicament_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(12, 2) NOT NULL,
    date_fabrication DATE,
    date_expiration DATE,
    FOREIGN KEY (achat_id) REFERENCES achats(id) ON DELETE CASCADE,
    FOREIGN KEY (medicament_id) REFERENCES medicaments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ventes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(30) NOT NULL UNIQUE,
    utilisateur_id INT NOT NULL,
    client_nom VARCHAR(150),
    date_vente DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    montant_total DECIMAL(12, 2) NOT NULL DEFAULT 0,
    devise ENUM('CDF', 'USD') NOT NULL DEFAULT 'CDF',
    notes TEXT,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vente_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vente_id INT NOT NULL,
    medicament_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(12, 2) NOT NULL,
    sous_total DECIMAL(12, 2) NOT NULL,
    FOREIGN KEY (vente_id) REFERENCES ventes(id) ON DELETE CASCADE,
    FOREIGN KEY (medicament_id) REFERENCES medicaments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- Données initiales
-- Mot de passe par défaut : admin123 (à changer après la première connexion)
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

INSERT INTO medicaments (code, nom, categorie_id, fournisseur_id, prix_achat, prix_vente, quantite_stock, seuil_alerte, date_fabrication, date_expiration) VALUES
('MED-001', 'Paracétamol 500mg', 2, 1, 150, 300, 500, 50, '2024-06-01', '2027-06-30'),
('MED-002', 'Amoxicilline 500mg', 1, 1, 800, 1500, 120, 20, '2025-01-15', '2026-12-31'),
('MED-003', 'Vitamine C 1000mg', 3, 2, 400, 750, 8, 15, '2025-08-01', '2027-03-15'),
('MED-004', 'Artéméther-Luméfantrine', 4, 2, 1200, 2500, 45, 10, '2025-02-01', '2026-08-20'),
('MED-005', 'Crème hydrocortisone 1%', 5, 1, 600, 1200, 3, 10, '2024-11-01', '2026-05-01');

SET FOREIGN_KEY_CHECKS = 1;
