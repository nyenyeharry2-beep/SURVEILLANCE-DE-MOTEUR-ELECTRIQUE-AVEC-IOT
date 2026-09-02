-- Migration : support Franc Congolais (CDF) et Dollar (USD)
-- Exécutez ce fichier dans phpMyAdmin si la base existe déjà

ALTER TABLE ventes
    ADD COLUMN IF NOT EXISTS devise ENUM('CDF', 'USD') NOT NULL DEFAULT 'CDF' AFTER montant_total;

-- Si votre MySQL ne supporte pas IF NOT EXISTS sur ADD COLUMN, utilisez :
-- ALTER TABLE ventes ADD COLUMN devise ENUM('CDF', 'USD') NOT NULL DEFAULT 'CDF' AFTER montant_total;
