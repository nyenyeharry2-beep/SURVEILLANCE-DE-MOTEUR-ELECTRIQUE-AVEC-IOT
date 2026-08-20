# CAHIER DES CHARGES — APPLICATION KYRIOS

## 1. Présentation
**Nom :** KYRIOS  
**Type :** application mobile de messagerie et réseau social.  
**Cible :** jeunes et utilisateurs souhaitant communiquer, publier et rejoindre des communautés.  
**Technologies :** Kotlin/Android, API REST PHP, MySQL, hébergement initial InfinityFree.

## 2. Vision
KYRIOS doit être un espace moderne où l'utilisateur peut discuter, publier, découvrir du contenu et participer à des communautés dans une interface simple et conviviale.

**Slogan proposé :** « KYRIOS — Connecte-toi. Partage. Sois toi-même. »

## 3. Objectifs
- Créer un compte par e-mail ou numéro de téléphone.
- Se connecter et gérer son profil.
- Échanger des messages texte et vocaux.
- Partager photos, vidéos et fichiers.
- Publier des statuts/stories.
- Publier, aimer, commenter et partager du contenu.
- Rechercher des utilisateurs et communautés.
- Créer ou rejoindre des communautés.
- Recevoir des notifications.
- Bloquer et signaler des utilisateurs/contenus.

## 4. Fonctionnalités

### 4.1 Authentification
- Inscription e-mail ou téléphone.
- Mot de passe sécurisé et haché côté serveur.
- Connexion/déconnexion.
- Réinitialisation du mot de passe.
- Validation des données.

### 4.2 Profil
- Photo de profil.
- Nom, @username et biographie.
- Publications, abonnés et abonnements.
- Modification du profil.

### 4.3 Messagerie privée
- Conversations individuelles.
- Messages texte.
- Emojis et réactions.
- Photos, vidéos et fichiers.
- Messages vocaux.
- Réponse et suppression de messages.
- Statuts envoyé/reçu/lu.

### 4.4 Stories
- Texte, photo et vidéo.
- Durée de visibilité : 24 heures.
- Liste des vues.

### 4.5 Découvrir
- Fil de publications publiques.
- Likes, commentaires, partage et enregistrement.
- Contenus : texte, photo, vidéo, citation, humour, motivation, éducation, etc.

### 4.6 Communautés
- Création de communautés.
- Description et image.
- Membres et administrateurs.
- Publications et discussion collective.
- Exemples : Gaming, Football, Musique, Technologie, Étudiants, Business, Humour.

### 4.7 Notifications
- Nouveau message.
- Réaction/commentaire.
- Mention.
- Activité dans une communauté.
- Autres événements sociaux.

### 4.8 Sécurité
- Hachage des mots de passe.
- HTTPS.
- Contrôle d'accès API.
- Validation des entrées.
- Blocage.
- Signalement.
- Ne jamais stocker les mots de passe en clair.

## 5. Architecture technique
```text
Application Android Kotlin
        │
        │ HTTPS / JSON
        ▼
API REST PHP
        │
        ▼
MySQL — InfinityFree
```

Pour les médias :
```text
Kotlin → PHP Upload → stockage serveur
                    ├── images/
                    ├── videos/
                    └── audio/
```

## 6. Base de données prévisionnelle
Tables : users, profiles, conversations, conversation_members, messages, message_reactions, posts, post_likes, comments, stories, story_views, communities, community_members, notifications, followers, blocked_users, reports, media

## 7. Écrans
1. Splash Screen
2. Inscription
3. Connexion
4. Accueil
5. Stories
6. Messagerie
7. Conversation
8. Découvrir
9. Publication
10. Notifications
11. Profil
12. Communautés
13. Paramètres
14. Signalement/blocage

## 8. Navigation principale
- Messages
- Découvrir
- Publier
- Notifications
- Profil

## 10. Développement par versions

### KYRIOS V1 — MVP
- Inscription/connexion.
- Profil.
- Recherche.
- Messagerie texte.
- Conversations.
- Publications.
- Likes/commentaires.

### KYRIOS V2
- Messages vocaux.
- Photos/vidéos.
- Stories.
- Notifications.
- Communautés.

### KYRIOS V3
- Appels audio/vidéo.
- Notifications push avancées.
- Sécurité renforcée.
- Stockage évolutif.
- Optimisation pour un grand nombre d'utilisateurs.

## 13. Critères de réussite
Le prototype sera considéré fonctionnel si :
- un utilisateur peut créer un compte ;
- il peut se connecter ;
- il peut rechercher un autre utilisateur ;
- deux utilisateurs peuvent échanger des messages ;
- un utilisateur peut publier ;
- un autre peut aimer/commenter ;
- les données sont correctement enregistrées dans MySQL ;
- les accès non autorisés sont refusés.
