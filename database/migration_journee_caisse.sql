-- Migration : fond caisse, taux du jour, clôture caisse sur journaux_quotidiens
-- Exécutez dans phpMyAdmin si la migration auto échoue

ALTER TABLE journaux_quotidiens
    ADD COLUMN IF NOT EXISTS taux_usd_cdf DECIMAL(12, 2) NULL AFTER date_jour,
    ADD COLUMN IF NOT EXISTS fond_caisse_cdf DECIMAL(14, 2) NOT NULL DEFAULT 0 AFTER taux_usd_cdf,
    ADD COLUMN IF NOT EXISTS fond_caisse_usd DECIMAL(14, 2) NOT NULL DEFAULT 0 AFTER fond_caisse_cdf,
    ADD COLUMN IF NOT EXISTS caisse_cloture_cdf DECIMAL(14, 2) NULL AFTER fond_caisse_usd,
    ADD COLUMN IF NOT EXISTS caisse_cloture_usd DECIMAL(14, 2) NULL AFTER caisse_cloture_cdf;
