# Super Genies - Système de Suivi des Paiements

Applications pour **C.S. LES SUPER GENIES** (Lubumbashi) : suivi des paiements scolaires par matricule, avec backend hébergé sur InfinityFree.

## Architecture

```
backend/          → API PHP + MySQL (InfinityFree)
android/payment-app/  → APK parents (suivi paiements)
android/admin-app/    → APK administrateur (import PDF)
shared/assets/    → Logos et images carousel
```

## Fonctionnalités

### Application Suivi Paiements (APK)
- Logo SPAG / Congrès scolaires
- Carousel feuilleté : Félicitations, Pétrochimie, Inscriptions
- Recherche par matricule (`CSLSG-2026-2027-00167`)
- Affichage des frais et statut de paiement
- **Messagerie** : signalement problème → facturation (nom parent, téléphone, matricule, classe, motif)
- Onglets Inscriptions et Trousseau (informations restructurées)

### Application Administrateur (APK)
- Connexion sécurisée
- Upload de fiches PDF (inscriptions ou paiements)
- **Réception messages parents** (nouveau / en cours / traité)
- Classification automatique par classe et section
- Statistiques et historique des imports

### Backend API
- Hébergement : `https://supergenies2026.site.je/`
- Base MySQL InfinityFree : `if0_42853060_supergenies`

## Déploiement Backend (InfinityFree)

1. **Créer la base de données** via phpMyAdmin :
   - Exécuter `backend/sql/schema.sql`
   - Si la base existe déjà : exécuter `backend/sql/migration_messages.sql`
   - Optionnel : `backend/sql/seed_demo.sql` (données de démo)

2. **Uploader les fichiers** dans `htdocs/` via File Manager :
   - Copier tout le contenu de `backend/` dans `htdocs/`

3. **Vérifier** : ouvrir `https://supergenies2026.site.je/` → JSON de l'API

### Endpoints API

| Méthode | URL | Description |
|---------|-----|-------------|
| GET | `/api/student.php?matricule=CSLSG-2026-2027-00167` | Consultation élève |
| GET | `/api/info/inscriptions.php` | Conditions d'admission |
| GET | `/api/info/trousseau.php` | Trousseau |
| POST | `/api/admin/login.php` | Connexion admin |
| POST | `/api/admin/upload.php` | Import PDF |
| GET | `/api/admin/stats.php` | Statistiques |

**Mot de passe admin par défaut :** `SuperGenies2026!` (à changer dans `config/app.php`)

## Compilation des APK (Android Studio)

### Prérequis
- Android Studio Hedgehog ou plus récent
- JDK 17

### App Paiements
```bash
cd android/payment-app
./gradlew assembleRelease
```
APK : `app/build/outputs/apk/release/app-release.apk`

### App Admin
```bash
cd android/admin-app
./gradlew assembleRelease
```

> Ouvrir chaque dossier comme projet Gradle dans Android Studio si `./gradlew` n'est pas présent.

## Import PDF

L'administrateur publie des fiches PDF générées par le système Super Genies :

- **Inscriptions** : détecte matricules, noms, classes → classement automatique par section (Primaire, Secondaire, Pétrochimie...)
- **Paiements** : associe frais aux matricules existants

Format matricule : `CSLSG-2026-2027-NNNNN`

## Configuration

Modifier l'URL API dans les apps si nécessaire :
- `android/payment-app/app/build.gradle.kts` → `API_BASE_URL`
- `android/admin-app/app/build.gradle.kts` → `API_BASE_URL`

Credentials DB dans `backend/config/database.php` (variables d'environnement supportées).

## Matricule de test

Après exécution du seed :
- **Matricule :** `CSLSG-2026-2027-00167`
- **Élève :** KABIKA AURELIA (1ère Maternelle)
