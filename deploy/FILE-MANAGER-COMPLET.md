# File Manager InfinityFree — LISTE COMPLÈTE

## Téléchargement (tout en un)

**https://raw.githubusercontent.com/nyenyeharry2-beep/SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT/cursor/super-genies-paiements-019d/deploy/supergenies-htdocs-filemanager-complet.zip**

1. Télécharger le zip
2. Extraire sur PC
3. Uploader le dossier **`htdocs`** → contenu dans **`htdocs/`** de InfinityFree (pas dans un sous-dossier)

---

## Liste des 76 fichiers

### Racine `/htdocs/`
| Fichier |
|---------|
| `.htaccess` |
| `index.php` |
| `connexion.php` |
| `suivi.php` |
| `verifier.php` |

### `/htdocs/admin/`
| Fichier |
|---------|
| `_init.php` |
| `portail.php` |
| `index.php` |
| `logout.php` |
| `dashboard.php` |
| `diagnostic.php` |

### `/htdocs/config/`
| Fichier | Note |
|---------|------|
| `app.php` | |
| `bootstrap.php` | |
| `database.php` | ⚠️ Ne pas remplacer si déjà configuré |
| `fee_catalog.php` | |

### `/htdocs/includes/`
| Fichier |
|---------|
| `parent_ui.php` |
| `theme.php` |

### `/htdocs/parser/`
| Fichier |
|---------|
| `PdfParser.php` |

### `/htdocs/assets/`
| Fichier |
|---------|
| `logo.jpg` |
| `logo_spag.png` |
| `logo_banner.png` |
| `carousel_felicitations.jpg` |
| `carousel_inscriptions.jpg` |
| `carousel_petrochimie.jpg` |

### `/htdocs/api/`
| Fichier |
|---------|
| `communiques.php` |
| `student.php` |

### `/htdocs/api/admin/`
| Fichier |
|---------|
| `login.php` |
| `upload.php` |
| `messages.php` |
| `stats.php` |
| `ping.php` |

### `/htdocs/api/info/`
| Fichier |
|---------|
| `inscriptions.php` |
| `trousseau.php` |

### `/htdocs/api/messages/`
| Fichier |
|---------|
| `send.php` |

### `/htdocs/uploads/`
| Fichier |
|---------|
| `.gitkeep` (dossier uploads vide) |

### `/htdocs/vendor/`
Dossier complet PDF (Smalot) — **ne pas oublier**, sinon import PDF échoue.

---

## Vérification après upload

Ouvrir dans le navigateur :
- http://supergenies2026.site.je/suivi.php
- http://supergenies2026.site.je/connexion.php

Les onglets Accueil / Messagerie doivent changer **sans rechargement lent**.

---

## APK (signées)

https://raw.githubusercontent.com/nyenyeharry2-beep/SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT/cursor/super-genies-paiements-019d/deploy/supergenies-apk-v1.1.3.zip

---

## Repartir imports à zéro (phpMyAdmin)

```sql
DELETE FROM student_fees;
DELETE FROM students;
DELETE FROM import_logs;
```
