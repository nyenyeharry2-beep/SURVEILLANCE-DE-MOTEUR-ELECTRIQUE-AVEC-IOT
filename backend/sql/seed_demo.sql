-- Données de démonstration (1ère Maternelle - extrait du PDF)
INSERT IGNORE INTO students (matricule, nom, prenom, genre, date_naissance, classe, section, telephone, annee_scolaire, statut_inscription, date_inscription) VALUES
('CSLSG-2026-2027-00167', 'KABIKA', 'AURELIA', 'Féminin', '2023-03-30', '1ère ANNEE MATERNELLE', 'Primaire', '0993257572', '2026-2027', 'Actif', '2026-08-28'),
('CSLSG-2026-2027-00393', 'KABUYA', 'FLAME PRODIGE', 'Masculin', '2023-06-15', '1ère ANNEE MATERNELLE', 'Primaire', '0972543987', '2026-2027', 'Actif', '2026-09-03'),
('CSLSG-2026-2027-00280', 'KALOMBO', 'MANASSE', 'Masculin', '2023-08-26', '1ère ANNEE MATERNELLE', 'Primaire', '0854455522', '2026-2027', 'Actif', '2026-09-01'),
('CSLSG-2026-2027-00323', 'LUMANO', 'JEMIMA', 'Féminin', '2023-08-05', '1ère ANNEE MATERNELLE', 'Primaire', '0976885887', '2026-2027', 'Actif', '2026-09-02'),
('CSLSG-2026-2027-00205', 'MAHINA', 'JASPE', 'Masculin', '2023-01-12', '1ère ANNEE MATERNELLE', 'Primaire', '0972407308', '2026-2027', 'Actif', '2026-08-29'),
('CSLSG-2026-2027-00009', 'NKONGOLO', 'LOCHRIS', 'Masculin', '2023-05-08', '1ère ANNEE MATERNELLE', 'Primaire', '0810572823', '2026-2027', 'Actif', '2026-08-24'),
('CSLSG-2026-2027-00008', 'TSHISOLA', 'ELIETTE', 'Féminin', '2023-07-31', '1ère ANNEE MATERNELLE', 'Primaire', '0974502917', '2026-2027', 'Actif', '2026-08-24');

-- Frais exemple pour démonstration
INSERT INTO student_fees (student_id, label, montant_du, montant_paye, statut, annee_scolaire)
SELECT id, 'Frais connexe (Primaire)', 30.00, 30.00, 'paye', '2026-2027' FROM students WHERE matricule = 'CSLSG-2026-2027-00167';

INSERT INTO student_fees (student_id, label, montant_du, montant_paye, statut, mois, annee_scolaire)
SELECT id, 'Frais scolaires mensuels', 65.00, 65.00, 'paye', 9, '2026-2027' FROM students WHERE matricule = 'CSLSG-2026-2027-00167';

INSERT INTO student_fees (student_id, label, montant_du, montant_paye, statut, mois, annee_scolaire)
SELECT id, 'Frais scolaires mensuels', 65.00, 0.00, 'impaye', 10, '2026-2027' FROM students WHERE matricule = 'CSLSG-2026-2027-00167';

INSERT INTO student_fees (student_id, label, montant_du, montant_paye, statut, annee_scolaire)
SELECT id, 'Frais connexe (Primaire)', 30.00, 15.00, 'partiel', '2026-2027' FROM students WHERE matricule = 'CSLSG-2026-2027-00393';
