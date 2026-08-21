-- Schema SQL pour phpMyAdmin - InfinityFree
-- Base: if0_42713537_surveillancemoteurharry
-- Alternative : ouvrir install.php dans le navigateur (creation automatique)

CREATE TABLE IF NOT EXISTS moteur_surveillance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_mesure DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ax FLOAT NOT NULL,
    ay FLOAT NOT NULL,
    az FLOAT NOT NULL,
    rpm FLOAT NOT NULL,
    arms FLOAT NOT NULL,
    vrms FLOAT NOT NULL,
    ecart FLOAT NOT NULL,
    etat VARCHAR(20) NOT NULL,
    relay_state VARCHAR(3) NOT NULL,
    anomalie_vibration TINYINT(1) NOT NULL DEFAULT 0,
    anomalie_vitesse TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_date_mesure (date_mesure)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cmd VARCHAR(3) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed TINYINT(1) NOT NULL DEFAULT 0,
    processed_at DATETIME NULL,
    INDEX idx_processed (processed, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS etat_relais (
    id INT PRIMARY KEY,
    relay_state VARCHAR(3) NOT NULL DEFAULT 'OFF',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO etat_relais (id, relay_state) VALUES (1, 'OFF');
