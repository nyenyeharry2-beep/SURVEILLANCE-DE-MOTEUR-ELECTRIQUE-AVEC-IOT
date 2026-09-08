-- Migration messagerie parents → facturation
-- À exécuter dans phpMyAdmin si schema.sql a déjà été importé avant cette mise à jour

CREATE TABLE IF NOT EXISTS parent_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_parent VARCHAR(120) NOT NULL,
    telephone_parent VARCHAR(30) NOT NULL,
    matricule VARCHAR(50) NOT NULL,
    nom_eleve VARCHAR(100) NULL,
    prenom_eleve VARCHAR(100) NULL,
    classe_eleve VARCHAR(120) NULL,
    section_eleve VARCHAR(80) NULL,
    motif ENUM(
        'paiement_non_enregistre',
        'montant_incorrect',
        'double_paiement',
        'probleme_inscription',
        'autre'
    ) NOT NULL DEFAULT 'autre',
    message TEXT NOT NULL,
    statut ENUM('nouveau', 'en_cours', 'traite') NOT NULL DEFAULT 'nouveau',
    note_admin TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_statut (statut),
    INDEX idx_matricule (matricule),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
