# KYRIOS — Cahier des charges

> **Note:** Le fichier `KYRIOS_Cahier_des_charges.zip` n’a pas été trouvé dans l’environnement. Ce document synthétise les exigences à partir des maquettes UI fournies.

## 1. Vision produit

KYRIOS est une application mobile de **messagerie sociale** combinant conversations (1‑à‑1 et groupes), fil d’actualité, stories, communautés thématiques et tableaux de bord d’**insights** pour les administrateurs de communautés.

## 2. Périmètre fonctionnel

### 2.1 Messagerie

- Liste des conversations avec recherche et filtres (Tous, Non lus, Favoris, Groupes, Archivés, Favoris).
- Bandeau horizontal de contacts actifs / stories en tête de liste.
- Indicateurs : non‑lus, accusés de lecture, statut « en ligne », « en train d’écrire… ».
- Conversation : bulles texte, images, messages vocaux (lecteur waveform), vidéo en en‑tête, pièces jointes (+, trombone, caméra).
- Réactions emoji sur les messages (ex. 🔥, ❤️).
- Appels audio / vidéo depuis l’en‑tête de conversation.

### 2.2 Fil social & découverte

- Onglets **Discover** / **Following**.
- Publications avec texte, médias superposés, mentions (@).
- Stories : « Votre story » + cercle d’amis.
- Navigation basse : Accueil, Recherche, Vidéo/Reels, Profil ; FAB « + » pour créer.

### 2.3 Communautés

- Liste de communautés par thème (Foodies, Fitness, etc.) avec nombre de membres.
- Recherche et bouton « + » pour créer / rejoindre.
- Chat de groupe lié à une communauté.

### 2.4 Profil utilisateur

- Photo, nom, handle, bouton Suivre.
- Statistiques : publications, abonnés, abonnements.
- Grille photos (masonry) et onglets Post / Mentions.
- Story highlights.

### 2.5 Insights (analytics communauté)

- Total membres et croissance sur 7 jours.
- Top localisations (barres horizontales).
- Pyramide / barres d’âge avec filtres genre (Tous, Femme, Homme, Non‑binaire).
- Horodatage « Mis à jour il y a X heures ».

### 2.6 Onboarding

- Écran d’accueil « Connect with Creators on Socially » (ou KYRIOS).
- CTA « Start Exploring ».

## 3. Exigences non fonctionnelles

- Interface **mobile-first** (viewport téléphone, ~390×844).
- Thèmes visuels supportés dans les maquettes : clair (bleu royal), sombre (glassmorphism), corail sur fond navy, mint/teal, lavender.
- **Thème par défaut implémenté :** clair bleu (#2563eb) aligné sur l’écran Messages / Communities / Insights.
- Données mock en local pour la démo (pas de backend obligatoire en V1).

## 4. Parcours utilisateur principaux

1. Ouvrir l’app → Messages (inbox).
2. Onglet Communautés → parcourir et ouvrir un groupe.
3. Onglet Insights → consulter les métriques.
4. Ouvrir une conversation → envoyer texte / consulter médias.
5. Profil → stats, photos, suivre un utilisateur.

## 5. Livrables V1 (cette itération)

- Application web React (Vite) responsive format mobile.
- Écrans : Messages, Chat, Communities, Insights, Profil, Discover (feed simplifié).
- Barre de navigation inférieure persistante.
- Jeu de données fictives cohérent avec les maquettes.

## 6. Évolutions hors scope V1

- Authentification, API temps réel, notifications push.
- Upload réel de médias et appels WebRTC.
