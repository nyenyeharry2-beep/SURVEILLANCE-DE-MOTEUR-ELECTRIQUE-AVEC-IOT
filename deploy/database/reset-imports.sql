-- Réinitialiser élèves et paiements (repartir imports à zéro)
-- phpMyAdmin InfinityFree — ne touche pas aux communiqués ni messages parents

DELETE FROM student_fees;
DELETE FROM students;
DELETE FROM import_logs;
