# Vérification déploiement InfinityFree — Super Genies

## Problème actuel : erreur 502

Le domaine `supergenies2026.site.je` répond **502 Bad Gateway** avec **0 octet**.
Cela signifie que le **domaine n'est pas encore relié au bon hébergement**, même si les fichiers sont uploadés.

Dans votre capture InfinityFree, l'**adresse IP était vide** → le domaine n'est pas encore actif.

---

## Étape 1 — Vérifier la structure dans File Manager

Ouvrez `htdocs` et vérifiez :

### CORRECT
```
htdocs/
  index.php          ← directement ici
  verifier.php
  .htaccess
  config/
  api/
  parser/
  uploads/
```

### INCORRECT (erreur fréquente)
```
htdocs/
  backend/           ← NE PAS avoir ce dossier
    index.php
    api/
```

Si vous voyez un dossier `backend/` dans `htdocs` :
1. Ouvrez `backend/`
2. Sélectionnez **tout** (index.php, api, config…)
3. **Déplacer** vers `htdocs/` (niveau au-dessus)
4. Supprimez le dossier `backend/` vide

---

## Étape 2 — Uploader verifier.php

Téléchargez et mettez `verifier.php` à la racine de `htdocs` :
https://raw.githubusercontent.com/nyenyeharry2-beep/SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT/cursor/super-genies-paiements-019d/backend/verifier.php

Ouvrez dans le navigateur :
```
http://supergenies2026.site.je/verifier.php
```

- **Si page blanche ou 502** → problème domaine/hébergement (étape 3)
- **Si page Diagnostic avec PHP OK** → fichiers bien placés
- **Si MySQL en rouge** → réimporter schema.sql dans phpMyAdmin

---

## Étape 3 — Activer le domaine sur InfinityFree

1. Panneau InfinityFree → **Overview** de `supergenies2026.site.je`
2. Vérifiez que **IP Address** n'est plus vide
3. Si vide : attendez 24–72 h (propagation DNS) ou contactez le support InfinityFree
4. Section **Health Check** : lisez l'avertissement DNS

### URL alternative (souvent plus rapide)

InfinityFree donne aussi une URL gratuite du type :
- `votresite.infinityfreeapp.com`
- ou `votresite.rf.gd`

Dans le panneau → **Overview** → cherchez **Website URL** ou **Assigned Domain**.

Si cette URL fonctionne mais pas `.site.je`, dites-le moi : on mettra cette URL dans les applications.

---

## Étape 4 — Base de données

phpMyAdmin → base `if0_42853060_supergenies` :

Vérifiez que ces tables existent :
- students
- student_fees
- fee_types
- parent_messages
- admin_sessions
- import_logs

Si `parent_messages` manque → importer `migration_messages.sql`

---

## Étape 5 — Test final

Quand tout fonctionne, ces liens doivent répondre :

```
http://supergenies2026.site.je/
http://supergenies2026.site.je/verifier.php
http://supergenies2026.site.je/api/student.php?matricule=CSLSG-2026-2027-00455
```

Ensuite les apps Android fonctionneront.

---

## Besoin d'aide ?

Envoyez une capture de votre File Manager montrant le contenu de `htdocs` (liste des fichiers/dossiers).
