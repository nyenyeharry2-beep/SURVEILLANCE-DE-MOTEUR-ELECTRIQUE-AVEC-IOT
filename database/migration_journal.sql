-- Migration : Journal quotidien (stock matin/soir, entrées, sorties)
-- Exécutez dans phpMyAdmin

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
