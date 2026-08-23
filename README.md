# Invitations Moïse & Sarah

Application **Android native Java** + **backend PHP** pour générer des invitations personnalisées avec le **design Adrian**.

## Télécharger l'APK (Java natif — v2.3.0 signé)

**[Télécharger invitations-moise-sarah-v2.3.0.apk](https://github.com/nyenyeharry2-beep/SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT/raw/cursor/invitation-generator-apk-06a4/releases/invitations-moise-sarah-v2.3.0.apk)** (~6 Mo)

> **v2.3.0** — correction polices + affiches HTML/CSS nettes • Désinstallez l'ancienne version avant d'installer

---

## Application web (index.html — identique à l'app Android)

**[Ouvrir index.html sur GitHub](https://github.com/nyenyeharry2-beep/SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT/blob/cursor/invitation-generator-apk-06a4/php-backend/index.html)** • En local : `cd php-backend && php -S localhost:8080` → `http://localhost:8080/index.html`

| Écran web | Écran Android |
|-----------|---------------|
| **Accueil** | MainActivity — affiche, cartes menu |
| **Configurer l'événement** | ConfigActivity — fond dark `#0D0D0D` |
| **Ajouter un invité** | AddGuestActivity — formulaire crème |
| **Aperçu final** | PreviewActivity — cadre téléphone + WhatsApp |

**Logo** : `couple_photo.png` + cadre blanc bordure or `#D4B896` (18px) • **Icône** : `app-icon.png`

```
php-backend/
├── index.html
└── assets/
    ├── app-icon.png
    ├── couple_photo.png
    ├── template_mariage_civil.png
    ├── template_affiche_blanche.png
    ├── css/app.css
    ├── css/invitation.css
    ├── js/invitation.js
    └── invitations/
        ├── mariage_civil.html
        └── affiche_blanche.html
```

### Installation APK

1. Téléchargez l'APK sur votre téléphone Android
2. Autorisez **Sources inconnues** si demandé
3. Installez — icône = affiche « Invitation » Sarah & Moïse

---

## Stack technique

| Composant | Technologie |
|-----------|-------------|
| **App mobile** | Java + Android SDK + Material Design XML |
| **Rendu invitations** | WebView HTML/CSS (1200×1700) + ZXing (QR code) |
| **Stockage local** | SQLite + SharedPreferences |
| **Backend web** | PHP 8 (API REST + dashboard) |
| **WhatsApp** | Intent Android + lien `wa.me` |

---

## Design Adrian implémenté

| Écran | Style |
|-------|-------|
| **Configurer l'événement** | Dark mode `#0D0D0D`, champs outlined, bouton bleu `#4A7BFF` |
| **Ajouter un invité** | Fond crème `#F5F0EB`, cartes blanches arrondies, or/champagne, icône ⚙️ |
| **Styles** | Kipushi Floral (Sarah), Royal Bordeaux (Adriel `~ ~ NOM ~ ~`), Ivory Prestige, Ville de Kipushi |
| **Aperçu final** | Cadre sombre, bouton vert WhatsApp, lien bleu « Fermer » |

---

## Structure du projet

```
android-native/     ← App Java Android (APK)
php-backend/          ← API PHP + dashboard web
releases/             ← APK prêt à installer
invitation-app/       ← Ancienne version Expo (archivée)
```

---

## Backend PHP

```bash
cd php-backend
php -S localhost:8080
```

Ouvrez `http://localhost:8080` — dashboard avec liste invités et export CSV.

API :
- `GET api/guests.php?action=list` — liste invités
- `POST api/guests.php?action=sync` — synchroniser depuis l'app
- `GET api/guests.php?action=export` — export CSV

---

## Reconstruire l'APK Java

```bash
cd android-native
export ANDROID_HOME=$HOME/android-sdk
./gradlew assembleRelease
```

APK : `android-native/app/build/outputs/apk/release/app-release-unsigned.apk`

---

*Mariage civil de Moïse NKUBA & Sarah KASONGO — Kipushi, 11 Septembre 2026*
