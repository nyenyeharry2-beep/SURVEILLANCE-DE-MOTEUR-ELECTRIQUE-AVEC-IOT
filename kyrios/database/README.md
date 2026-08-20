# KYRIOS — Base de données

## Fichiers

| Fichier | Description |
|---------|-------------|
| `schema.sql` | Schéma complet (utilisateurs, chats, messages, posts, communautés, appels) |
| `seed.sql` | Données de démonstration |
| `kyrios.db` | Base SQLite prête à l'emploi (générée automatiquement) |

## Tables principales

- **users** — Comptes utilisateurs et authentification
- **conversations** / **conversation_members** — Chats privés et groupes
- **messages** / **message_media** / **message_reactions** — Messagerie
- **stories** — Stories éphémères
- **posts** / **post_likes** / **post_comments** — Fil Discover
- **communities** / **community_members** — Communautés
- **calls** — Historique d'appels
- **notifications** — Notifications push
- **follows** — Abonnements

## Initialisation

```bash
cd ../backend
npm install
npm run init-db
```

La base est créée dans `database/kyrios.db`.

## Compte démo

| Email | Mot de passe |
|-------|-------------|
| me@kyrios.app | Kyrios2026! |

Autres comptes : kira@kyrios.app, darlene@kyrios.app, etc. (même mot de passe)

## PostgreSQL (production)

Le schéma SQLite est compatible PostgreSQL avec ces modifications :
- Remplacer `INTEGER` par `BOOLEAN` pour les champs booléens
- Remplacer `TEXT DEFAULT (datetime('now'))` par `TIMESTAMPTZ DEFAULT NOW()`
- Remplacer `randomblob(16)` par `gen_random_uuid()`

## Sauvegarde

```bash
cp kyrios.db kyrios-backup-$(date +%Y%m%d).db
```
