# Dépannage — Erreur de connexion (502)

## Cause principale

Le domaine `supergenies2026.site.je` répond **502 Bad Gateway**.
Cela signifie que **le backend PHP n'est pas encore actif** sur InfinityFree.

Les applications Android ne peuvent pas fonctionner tant que le serveur ne répond pas.

## Vérification rapide

Ouvrez dans le navigateur du téléphone ou PC :

```
http://supergenies2026.site.je/
```

**Résultat attendu :** un texte JSON avec `"app": "Super Genies - API Suivi Paiements"`

**Si vous voyez une page blanche ou erreur 502 :** le backend n'est pas déployé.

## Solution — 3 étapes

### 1. phpMyAdmin
- Importer `schema.sql`
- Si déjà fait : importer `migration_messages.sql`

### 2. File Manager → htdocs
- Télécharger [supergenies-htdocs-filemanager.zip](https://github.com/nyenyeharry2-beep/SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT/raw/cursor/super-genies-paiements-019d/supergenies-htdocs-filemanager.zip)
- Uploader dans `htdocs`
- **Extraire** le ZIP
- Vérifier que `index.php` est directement dans `htdocs/` (pas dans un sous-dossier)

### 3. Tester
```
http://supergenies2026.site.je/api/student.php?matricule=CSLSG-2026-2027-00167
```

## Nouvelles APK v1.0.1

- Nouveau logo C.S. LES SUPER GENIES
- Connexion HTTP (compatible InfinityFree)
- Inscriptions et Trousseau fonctionnent **hors-ligne** si serveur down
- Messages d'erreur plus clairs

[Télécharger supergenies-apk.zip](https://github.com/nyenyeharry2-beep/SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT/raw/cursor/super-genies-paiements-019d/supergenies-apk.zip)
