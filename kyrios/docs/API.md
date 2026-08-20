# KYRIOS — Documentation API (V1 MVP)

Base URL : `https://your-domain.infinityfreeapp.com/kyrios/api/public`

Toutes les réponses sont en JSON. Les routes protégées nécessitent :

```
Authorization: Bearer <token>
```

## Authentification

### POST /auth/register

Créer un compte.

```json
{
  "username": "alice",
  "email": "alice@example.com",
  "password": "secret123",
  "display_name": "Alice"
}
```

Réponse `201` :

```json
{
  "success": true,
  "user": { "id": 1, "username": "alice", ... },
  "token": "..."
}
```

### POST /auth/login

```json
{
  "identifier": "alice",
  "password": "secret123"
}
```

`identifier` accepte username, e-mail ou téléphone.

## Utilisateurs

### GET /users/me

Profil de l'utilisateur connecté.

### PUT /users/me

```json
{ "display_name": "Alice D.", "bio": "Hello KYRIOS" }
```

### GET /users/search?q=alice

Recherche par username, display_name ou e-mail.

### GET /users/{id}

Profil public d'un utilisateur.

## Messagerie

### GET /conversations

Liste des conversations de l'utilisateur.

### POST /conversations/direct

```json
{ "user_id": 2 }
```

Crée ou retourne une conversation directe.

### GET /conversations/{id}/messages

Messages d'une conversation (marque comme lus).

### POST /conversations/{id}/messages

```json
{
  "message": "Salut !",
  "message_type": "text"
}
```

## Publications

### GET /posts

Fil public de publications.

### POST /posts

```json
{
  "content": "Mon premier post KYRIOS",
  "visibility": "public"
}
```

### POST /posts/{id}/like

Aimer une publication.

### GET /posts/{id}/comments

Liste des commentaires.

### POST /posts/{id}/comments

```json
{ "content": "Super post !" }
```

## Codes d'erreur

| Code | Signification |
|------|---------------|
| 401 | Token manquant ou invalide |
| 403 | Accès refusé |
| 404 | Ressource introuvable |
| 409 | Conflit (ex. déjà liké) |
| 422 | Données invalides |
| 500 | Erreur serveur |
