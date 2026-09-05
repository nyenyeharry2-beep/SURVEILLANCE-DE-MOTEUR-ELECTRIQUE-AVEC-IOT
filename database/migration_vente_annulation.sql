-- Migration : annulation ventes + date_jour métier (6h-20h)
ALTER TABLE ventes
    ADD COLUMN IF NOT EXISTS date_jour DATE NULL AFTER date_vente,
    ADD COLUMN IF NOT EXISTS annulee TINYINT(1) NOT NULL DEFAULT 0 AFTER notes,
    ADD COLUMN IF NOT EXISTS annulee_at DATETIME NULL AFTER annulee,
    ADD COLUMN IF NOT EXISTS annulee_par INT NULL AFTER annulee_at,
    ADD COLUMN IF NOT EXISTS motif_annulation VARCHAR(255) NULL AFTER annulee_par;

UPDATE ventes SET date_jour = DATE(date_vente) WHERE date_jour IS NULL;
