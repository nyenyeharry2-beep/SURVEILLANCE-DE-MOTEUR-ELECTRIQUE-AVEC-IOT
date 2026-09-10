-- =====================================================
-- Mise à jour tarifs bus : 15, 20, 25, 30 USD
-- phpMyAdmin → onglet SQL → Coller → Exécuter
-- =====================================================

UPDATE `tariffs` SET `is_default` = 0;

UPDATE `tariffs` SET `nom` = '15 USD', `montant` = 15.00, `is_default` = 1, `statut` = 'actif' WHERE `id` = 1;
UPDATE `tariffs` SET `nom` = '20 USD', `montant` = 20.00, `is_default` = 0, `statut` = 'actif' WHERE `id` = 2;
UPDATE `tariffs` SET `nom` = '25 USD', `montant` = 25.00, `is_default` = 0, `statut` = 'actif' WHERE `id` = 3;
UPDATE `tariffs` SET `nom` = '30 USD', `montant` = 30.00, `is_default` = 0, `statut` = 'actif' WHERE `id` = 4;

UPDATE `settings` SET `setting_value` = '15' WHERE `setting_key` = 'default_tariff';
