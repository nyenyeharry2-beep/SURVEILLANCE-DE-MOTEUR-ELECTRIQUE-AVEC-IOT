# Mise en place Firebase Realtime Database

Guide pas à pas pour la **base de données** du projet de surveillance moteur IoT.

Tu as besoin d’un compte Google. Les credentials restent **sur ta machine / ta console** — ne les commit jamais dans Git.

---

## Étape 1 — Créer le projet Firebase

1. Ouvre [https://console.firebase.google.com](https://console.firebase.google.com)
2. **Ajouter un projet** (ou Create a project)
3. Nom suggéré : `surveillance-moteur-iot` (libre)
4. Analytics : tu peux désactiver pour un mémoire
5. Valider → attendre la création

---

## Étape 2 — Activer Realtime Database

1. Menu gauche → **Build** → **Realtime Database**
2. **Créer une base de données**
3. Choisir une région proche (ex. `europe-west1`)
4. Mode de démarrage : **mode test** (lecture/écriture ouvertes ~30 jours)
5. Créer

Tu obtiens une URL du type :

```text
https://TON-PROJET-default-rtdb.europe-west1.firebasedatabase.app
```

Note-la : c’est le `databaseURL` / `FIREBASE_HOST`.

---

## Étape 3 — Importer la structure initiale

1. Onglet **Données**
2. Menu **⋮** (en haut à droite de l’arbre) → **Importer un JSON**
3. Choisir le fichier du dépôt :

```text
Projet_Surveillance_Moteur/Firebase/seed_initial.json
```

4. Confirmer l’import

Tu dois voir l’arbre :

```text
moteur
├── config      ← seuils (RPM, vibrations, auto-stop…)
├── command     ← relais + mute buzzer (depuis le Web)
└── live        ← dernières mesures (remplies par l’ESP32)
```

`historique/` sera créé automatiquement par l’ESP32 lors des premières mesures.

---

## Étape 4 — Règles de sécurité

1. Onglet **Règles**
2. Coller le contenu de `Firebase/database.rules.json`
3. **Publier**

Pour le mémoire / proto, les règles sont ouvertes (`.read` / `.write` = true).  
**Ne pas laisser ainsi en production.**

---

## Étape 5 — Application Web (config JS)

1. Paramètres du projet (icône engrenage) → **Vos applications**
2. **Ajouter une application** → plateforme **Web** `</>`
3. Surnom : `dashboard-moteur`
4. Copier la config affichée
5. Coller dans :

```text
Projet_Surveillance_Moteur/Web/firebase-config.js
```

Remplacer chaque `VOTRE_…` :

| Champ | Où le trouver |
|-------|----------------|
| `apiKey` | Config Web |
| `authDomain` | Config Web |
| `databaseURL` | Config Web **ou** URL Realtime Database |
| `projectId` | Config Web |
| `storageBucket` | Config Web |
| `messagingSenderId` | Config Web |
| `appId` | Config Web |

---

## Étape 6 — Secret pour l’ESP32 (legacy)

L’ESP32 du projet utilise un **secret de base de données** (token legacy) :

1. Paramètres du projet → **Comptes de service**
2. Onglet / section **Secrets de base de données** (Database secrets)
3. Afficher / copier le secret

À coller plus tard dans le sketch ESP32 :

```cpp
#define FIREBASE_HOST  "https://TON-PROJET-default-rtdb.REGION.firebasedatabase.app"
#define FIREBASE_AUTH  "LE_SECRET_COPIE"
```

> Les secrets legacy sont dépréciés mais encore adaptés à un mémoire.  
> En production : Auth Firebase + règles restrictives.

Si l’option « Secrets » n’apparaît plus dans ta console, utilise temporairement le mode test des règles et on passera à Auth anonyme / email ensuite.

---

## Étape 7 — Vérifier que la base est prête

Dans la console Firebase → Realtime Database → Données :

| Chemin | Valeur attendue après import |
|--------|------------------------------|
| `moteur/config/rpm_nominal` | `1500` |
| `moteur/config/auto_stop_on_alarm` | `true` |
| `moteur/command/relay` | `false` |
| `moteur/live/status` | `"ARRET"` |
| `moteur/live/online` | `false` |

Test manuel (optionnel) :

1. Clique sur `moteur/command/relay`
2. Mets `true`
3. L’ESP32 (une fois flashé) lira cette commande et activera le relais D8

---

## Rôles des 4 nœuds

| Nœud | Qui écrit | Qui lit | Fréquence |
|------|-----------|---------|-----------|
| `moteur/live` | ESP32 | Web | ~1 s |
| `moteur/config` | Web (seuils) | ESP32 → Uno | ~2–5 s |
| `moteur/command` | Web (Marche/Arrêt, mute) | ESP32 → Uno | ~1–2 s |
| `moteur/historique/<id>` | ESP32 | Web | ~10 s |

---

## Checklist « base prête »

- [ ] Projet Firebase créé
- [ ] Realtime Database créée (région notée)
- [ ] `seed_initial.json` importé
- [ ] Règles publiées
- [ ] App Web créée → `firebase-config.js` rempli **en local** (non commit des vraies clés)
- [ ] Secret database noté pour l’ESP32 (à coller plus tard dans le `.ino`)

Quand cette checklist est OK, on passe au firmware ESP32 (connexion Wi-Fi + publication).

---

## Fichiers du dépôt liés à la BDD

```text
Projet_Surveillance_Moteur/
├── Firebase/
│   ├── seed_initial.json          ← importer dans la console
│   ├── database.rules.json        ← coller dans Règles
│   └── database_structure.txt     ← documentation des champs
├── Web/
│   └── firebase-config.js         ← credentials Web (placeholders)
└── Docs/
    └── SETUP_FIREBASE.md          ← ce guide
```
