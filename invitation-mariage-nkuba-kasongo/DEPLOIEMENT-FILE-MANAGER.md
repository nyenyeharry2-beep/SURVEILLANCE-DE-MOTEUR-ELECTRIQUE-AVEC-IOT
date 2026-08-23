# invitation-mariage-nkuba-kasongo
## Déploiement sur marriage1.site.je

---

## ÉTAPE 1 — Base de données (phpMyAdmin)

1. Ouvrez **MySQL Databases** → bouton **phpMyAdmin** sur `if0_42732689_mariage1`
2. Sélectionnez la base **`if0_42732689_mariage1`**
3. Onglet **Importer** → choisissez le fichier :
   ```
   database/schema.sql
   ```
4. Cliquez **Exécuter**

---

## ÉTAPE 2 — File Manager (htdocs)

1. Ouvrez **File Manager** de votre hébergement
2. Allez dans le dossier **`htdocs`** (racine du site)
3. **Supprimez** tout ancien contenu si vous réinstallez
4. Uploadez **`htdocs-upload.zip`**
5. **Extrayez** le ZIP **dans `htdocs`**
6. **IMPORTANT** — vérifiez que ces fichiers sont **directement** dans `htdocs`, pas dans un sous-dossier :

```
htdocs/
├── index.html          ← DOIT être ici (pas dans htdocs/htdocs/)
├── index.php
├── .htaccess
├── api/
├── assets/
├── app/
└── _private/
```

> **Erreur 403 ?** Si vous voyez un dossier `htdocs/htdocs/` après extraction, déplacez tout le contenu du sous-dossier vers la racine `htdocs/`.

Structure attendue :

> Le dossier `_private/` est **bloqué** — les visiteurs ne voient jamais la config technique.

---

## ÉTAPE 3 — Vérifier le site

- Site : **https://marriage1.site.je**
- Téléchargement APK : **https://marriage1.site.je/app/invitation-mariage-nkuba-kasongo.apk**
- API invités : **https://marriage1.site.je/api/guests.php?action=list**

---

## ÉTAPE 4 — Application Android v2.5.0

1. Installez l'APK (lien ci-dessus ou depuis l'accueil du site)
2. **Configurer l'événement** → importez **vos photos** (couple + affiches)
3. **Ajouter un invité** → l'invitation avec **QR code** se génère
4. Les invités se **synchronisent automatiquement** avec le site

---

## Fonctionnalités identiques site ↔ app

| Fonction | Site (index.html) | App Android |
|----------|-------------------|-------------|
| Config événement | ✓ | ✓ |
| Photo couple (logo) | Upload web | Galerie téléphone |
| Affiches personnalisées | Upload web | Galerie téléphone |
| Invitation + QR 200px | generate.php | WebView Java |
| Liste invités | API | Dashboard + sync cloud |
| Export CSV | api/guests.php?action=export | Dashboard |
| WhatsApp | Bouton web | Intent WhatsApp |
| Télécharger APK | Bouton accueil site | Bouton accueil app |

---

## Nom du projet

**invitation-mariage-nkuba-kasongo**

Aucun texte technique (hébergeur, type de base) n'apparaît dans l'interface utilisateur.
