# Importer la base KYRIOS dans phpMyAdmin (InfinityFree)

Votre base : **if0_42679313_KYRIOS** sur **sql109.infinityfree.com**

## Étape 1 — Télécharger le fichier SQL

Téléchargez ce fichier sur votre PC :

**https://github.com/nyenyeharry2-beep/SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT/raw/cursor/kyrios-messaging-app-798d/kyrios/releases/kyrios_mysql.sql**

## Étape 2 — Importer dans phpMyAdmin

1. Ouvrez phpMyAdmin (comme sur votre capture)
2. Cliquez sur la base **`if0_42679313_KYRIOS`** à gauche
3. Cliquez l'onglet **Import** (pas Structure)
4. Cliquez **Choose File** → sélectionnez `kyrios_mysql.sql`
5. Laissez le format **SQL**
6. Cliquez **Go** / **Exécuter**

✅ Vous devez voir **14 tables** créées : users, conversations, messages, posts, etc.

## Étape 3 — Vérifier

Onglet **Structure** → vous devez voir les tables au lieu de "No tables found".

Cliquez sur la table **users** → onglet **Browse** → 8 comptes demo doivent apparaître.

## Étape 4 — Uploader l'API PHP

Dans le **File Manager** InfinityFree, uploadez dans `htdocs/` :

```
htdocs/
├── .htaccess          ← depuis kyrios/infinityfree/
├── config.php         ← éditez le mot de passe MySQL
└── api/
    └── index.php
```

Dans `config.php`, remplacez `VOTRE_MOT_DE_PASSE_ICI` par votre mot de passe MySQL (panneau InfinityFree → MySQL → Show Password).

## Étape 5 — Tester

Ouvrez dans le navigateur :

```
https://VOTRE-SITE.infinityfreeapp.com/api/health
```

Réponse attendue :
```json
{"status":"ok","app":"KYRIOS","host":"InfinityFree"}
```

## Compte demo après import

| Email | Mot de passe |
|-------|-------------|
| me@kyrios.app | Kyrios2026! |

## Problème courant

Si l'import échoue (fichier trop gros), copiez-collez le contenu du fichier SQL dans l'onglet **SQL** de phpMyAdmin et cliquez **Go**.
