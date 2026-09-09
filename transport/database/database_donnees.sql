-- =====================================================
-- DONNÉES SEULEMENT - C.S LES SUPER GENIES
-- À utiliser si les tables existent déjà (après erreur duplicate)
-- phpMyAdmin → SQL → Coller et Exécuter
-- =====================================================

INSERT IGNORE INTO `academic_years` (`label`, `start_date`, `end_date`, `is_active`) VALUES
('2025-2026', '2025-09-01', '2026-06-30', 0),
('2026-2027', '2026-09-01', '2027-06-30', 1);

DELETE FROM `classes`;

INSERT INTO `classes` (`nom`, `section`, `ordre`) VALUES
('1ère maternelle', 'Maternelle', 1),
('2ème maternelle', 'Maternelle', 2),
('3ème maternelle', 'Maternelle', 3),
('1ère primaire', 'Primaire', 10),
('2ème primaire', 'Primaire', 11),
('3ème primaire', 'Primaire', 12),
('4ème primaire', 'Primaire', 13),
('5ème primaire', 'Primaire', 14),
('6ème primaire', 'Primaire', 15),
('7ème', 'Secondaire', 20),
('8ème', 'Secondaire', 21),
('1ère pédagogie', 'Pédagogie', 30),
('2ème pédagogie', 'Pédagogie', 31),
('3ème pédagogie', 'Pédagogie', 32),
('4ème pédagogie', 'Pédagogie', 33),
('1ère pétrochimie', 'Pétrochimie', 40),
('2ème pétrochimie', 'Pétrochimie', 41),
('3ème pétrochimie', 'Pétrochimie', 42),
('4ème pétrochimie', 'Pétrochimie', 43),
('1ère commercial', 'Commercial', 50),
('2ème commercial', 'Commercial', 51),
('3ème commercial', 'Commercial', 52),
('4ème commercial', 'Commercial', 53),
('1ère électronique', 'Électronique', 60),
('2ème électronique', 'Électronique', 61),
('3ème électronique', 'Électronique', 62),
('4ème électronique', 'Électronique', 63),
('1ère science', 'Science', 70),
('2ème science', 'Science', 71),
('3ème science', 'Science', 72),
('4ème science', 'Science', 73),
('1ère électricité', 'Électricité', 80),
('2ème électricité', 'Électricité', 81),
('3ème électricité', 'Électricité', 82),
('4ème électricité', 'Électricité', 83),
('1ère mécanique auto', 'Mécanique Auto', 90),
('2ème mécanique auto', 'Mécanique Auto', 91),
('3ème mécanique auto', 'Mécanique Auto', 92),
('4ème mécanique auto', 'Mécanique Auto', 93),
('1ère mécanique générale', 'Mécanique Générale', 100),
('2ème mécanique générale', 'Mécanique Générale', 101),
('3ème mécanique générale', 'Mécanique Générale', 102),
('4ème mécanique générale', 'Mécanique Générale', 103);

INSERT IGNORE INTO `bus_stops` (`nom`, `adresse`, `description`) VALUES
('Golf Maisha', 'Q/ Golf Maisha', 'Zone principale'),
('Kenya', 'Quartier Kenya', NULL),
('Bel-Air', 'Quartier Bel-Air', NULL),
('Katuba', 'Quartier Katuba', NULL),
('Annexe', 'Commune Annexe', NULL);

INSERT IGNORE INTO `tariffs` (`nom`, `montant`, `devise`, `is_default`) VALUES
('Standard', 50.00, 'USD', 1),
('Zone 1', 40.00, 'USD', 0),
('Zone 2', 50.00, 'USD', 0),
('Zone 3', 60.00, 'USD', 0);

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('school_name', 'C.S LES SUPER GENIES'),
('school_foundation', 'FONDATION EBEN EZER – ORA S.A.R.I'),
('school_project', 'PROJET EDUCATIF'),
('school_address', '26, AV. BIN MALISAWA'),
('school_quarter', 'Q/ Golf Maisha, Commune Annexe'),
('school_city', 'VILLE DE LUBUMBASHI'),
('school_email', 'cslessupergenies@gmail.com'),
('school_phone', '+243815454401 / +243858357777'),
('default_tariff', '50'),
('default_currency', 'USD'),
('active_academic_year', '2026-2027'),
('receipt_prefix', 'BUS'),
('dossier_prefix', 'BUS')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

INSERT IGNORE INTO `users` (`nom`, `username`, `password`, `role`) VALUES
('Administrateur', 'admin', '$2b$12$osSzJCmpWXc1BWcZVHklAum47.JlBPHiPjOgl.q7yrdF5Ii1f7X2.', 'superadmin');

INSERT IGNORE INTO `counters` (`name`, `year`, `value`) VALUES
('dossier', 2026, 0),
('receipt', 2026, 0);
