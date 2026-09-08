# Patch complet Super Genies v7 (tout en un)

**Utilisez ce zip** — il remplace les patches séparés (design, communiqués, notifications, frais-v6).

Contient déjà :
- Design Suivi parents (APK)
- Communiqués Admin → Suivi
- Notifications bandeau + API
- Import PDF (inscriptions, connexe, minerval, bus, équipements)
- Logo officiel

## Installation File Manager

Extraire **supergenies-patch-complet-v7.zip** dans `htdocs/` :

| Dossier / fichier | Destination |
|-------------------|-------------|
| `suivi.php` | `/htdocs/` |
| `admin/portail.php` | `/htdocs/admin/` |
| `includes/parent_ui.php` | `/htdocs/includes/` |
| `config/bootstrap.php` | `/htdocs/config/` |
| `config/fee_catalog.php` | `/htdocs/config/` |
| `parser/PdfParser.php` | `/htdocs/parser/` |
| `api/communiques.php` | `/htdocs/api/` |
| `assets/*` | `/htdocs/assets/` |

## APK (signées, installables)

- **Suivi :** `supergenies-apk-v1.1.3.zip` → SuperGenies-Paiements-v1.1.3.apk
- **Admin :** même zip → SuperGenies-Admin-v1.1.2.apk

## Repartir à zéro (données seulement)

phpMyAdmin → SQL :

```sql
DELETE FROM student_fees;
DELETE FROM students;
DELETE FROM import_logs;
```

Puis imports dans l'ordre : **Inscriptions → Connexe → Minerval (mois) → Bus → Équipements**

## Ne pas utiliser

- ~~supergenies-patch-frais-v6.zip~~ (annulé)
