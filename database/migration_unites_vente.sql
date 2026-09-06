-- Migration : vente par comprimé / plaquette / flacon + import Excel
-- Pharmacie Nouvelle Eve

ALTER TABLE medicaments
    ADD COLUMN IF NOT EXISTS type_unite ENUM('comprime_plaquette', 'flacon') NOT NULL DEFAULT 'comprime_plaquette' AFTER prix_vente,
    ADD COLUMN IF NOT EXISTS prix_comprime DECIMAL(12, 2) NULL AFTER type_unite,
    ADD COLUMN IF NOT EXISTS prix_plaquette DECIMAL(12, 2) NULL AFTER prix_comprime,
    ADD COLUMN IF NOT EXISTS prix_flacon DECIMAL(12, 2) NULL AFTER prix_plaquette,
    ADD COLUMN IF NOT EXISTS comprimes_par_plaquette INT NOT NULL DEFAULT 10 AFTER prix_flacon;

-- MySQL < 8.0 : exécuter manuellement si IF NOT EXISTS échoue :
-- ALTER TABLE medicaments ADD COLUMN type_unite ENUM('comprime_plaquette','flacon') NOT NULL DEFAULT 'comprime_plaquette';
-- etc.

ALTER TABLE vente_lignes
    ADD COLUMN IF NOT EXISTS unite_vente ENUM('comprime', 'plaquette', 'flacon', 'unite') NOT NULL DEFAULT 'unite' AFTER medicament_id,
    ADD COLUMN IF NOT EXISTS stock_deduit INT NULL AFTER quantite;

ALTER TABLE achat_lignes
    ADD COLUMN IF NOT EXISTS unite_entree ENUM('comprime', 'plaquette', 'flacon', 'unite') NOT NULL DEFAULT 'unite' AFTER medicament_id,
    ADD COLUMN IF NOT EXISTS stock_ajoute INT NULL AFTER quantite;

-- Remplir les prix unitaires à partir du prix_vente existant
UPDATE medicaments
SET prix_comprime = prix_vente
WHERE type_unite = 'comprime_plaquette' AND (prix_comprime IS NULL OR prix_comprime = 0) AND prix_vente > 0;

UPDATE medicaments
SET prix_flacon = prix_vente
WHERE type_unite = 'flacon' AND (prix_flacon IS NULL OR prix_flacon = 0) AND prix_vente > 0;

UPDATE medicaments
SET prix_plaquette = prix_comprime * comprimes_par_plaquette
WHERE type_unite = 'comprime_plaquette'
  AND (prix_plaquette IS NULL OR prix_plaquette = 0)
  AND prix_comprime > 0
  AND comprimes_par_plaquette > 0;
