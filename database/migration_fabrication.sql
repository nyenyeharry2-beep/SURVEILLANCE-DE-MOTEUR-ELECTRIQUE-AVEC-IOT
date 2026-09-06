-- Migration : dates fabrication + expiration avec alerte anticipée
-- Exécutez dans phpMyAdmin

ALTER TABLE medicaments
    ADD COLUMN date_fabrication DATE NULL AFTER seuil_alerte;

ALTER TABLE achat_lignes
    ADD COLUMN date_fabrication DATE NULL AFTER prix_unitaire;
