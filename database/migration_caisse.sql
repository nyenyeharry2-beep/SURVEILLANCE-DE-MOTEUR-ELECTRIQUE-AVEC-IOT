-- Entrées / sorties caisse (motif obligatoire) — vendeurs mobile + site web
CREATE TABLE IF NOT EXISTS mouvements_caisse (
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
