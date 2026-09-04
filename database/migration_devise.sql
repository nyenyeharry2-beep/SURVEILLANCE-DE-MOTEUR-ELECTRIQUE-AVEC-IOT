-- Migration : Franc Congolais (CDF) et Dollar (USD)
-- Exécutez dans phpMyAdmin si votre base existe déjà

ALTER TABLE ventes
    ADD COLUMN devise ENUM('CDF', 'USD') NOT NULL DEFAULT 'CDF' AFTER montant_total;
