-- =====================================================
-- Mise à jour tarifs bus : 15, 20, 25, 30 USD
-- phpMyAdmin → SQL → Coller et Exécuter
-- =====================================================

UPDATE `tariffs` SET `is_default` = 0;

UPDATE `tariffs` SET `nom` = '15 USD', `montant` = 15.00, `is_default` = 1, `statut` = 'actif'
WHERE `id` = (SELECT id FROM (SELECT MIN(id) AS id FROM `tariffs`) AS t);

UPDATE `tariffs` SET `nom` = '20 USD', `montant` = 20.00, `is_default` = 0, `statut` = 'actif'
WHERE `id` = (SELECT id FROM (SELECT id FROM `tariffs` ORDER BY id LIMIT 1 OFFSET 1) AS t);

UPDATE `tariffs` SET `nom` = '25 USD', `montant` = 25.00, `is_default` = 0, `statut` = 'actif'
WHERE `id` = (SELECT id FROM (SELECT id FROM `tariffs` ORDER BY id LIMIT 1 OFFSET 2) AS t);

UPDATE `tariffs` SET `nom` = '30 USD', `montant` = 30.00, `is_default` = 0, `statut` = 'actif'
WHERE `id` = (SELECT id FROM (SELECT id FROM `tariffs` ORDER BY id LIMIT 1 OFFSET 3) AS t);

UPDATE `tariffs` SET `statut` = 'inactif' WHERE `nom` NOT IN ('15 USD', '20 USD', '25 USD', '30 USD');

INSERT INTO `tariffs` (`nom`, `montant`, `devise`, `is_default`, `statut`)
SELECT '15 USD', 15.00, 'USD', 1, 'actif' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `tariffs` WHERE `nom` = '15 USD' AND `statut` = 'actif');

INSERT INTO `tariffs` (`nom`, `montant`, `devise`, `is_default`, `statut`)
SELECT '20 USD', 20.00, 'USD', 0, 'actif' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `tariffs` WHERE `nom` = '20 USD' AND `statut` = 'actif');

INSERT INTO `tariffs` (`nom`, `montant`, `devise`, `is_default`, `statut`)
SELECT '25 USD', 25.00, 'USD', 0, 'actif' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `tariffs` WHERE `nom` = '25 USD' AND `statut` = 'actif');

INSERT INTO `tariffs` (`nom`, `montant`, `devise`, `is_default`, `statut`)
SELECT '30 USD', 30.00, 'USD', 0, 'actif' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `tariffs` WHERE `nom` = '30 USD' AND `statut` = 'actif');

UPDATE `settings` SET `setting_value` = '15' WHERE `setting_key` = 'default_tariff';
