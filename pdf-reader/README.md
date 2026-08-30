# Lumen Reader

Application web complète de lecture PDF intelligente avec synthèse vocale, OCR, stockage local, authentification et assistant IA.

## Installation

```bash
cd pdf-reader
npm install
npm run dev
```

Ouvrir **http://localhost:5173**

### Build production

```bash
npm run build
npm run preview
```

### IA avancée (optionnel)

Créez un fichier `.env` :

```env
VITE_OPENAI_API_KEY=sk-...
```

Sans clé API, l'assistant IA fonctionne en **mode local** (résumé/extraction basique).

## Fonctionnalités

| Phase | Fonctionnalité |
|-------|----------------|
| Interface | Accueil, bibliothèque, lecteur, contrôles |
| PDF | Import réel, affichage PDF.js, extraction texte, détection scanné |
| OCR | Tesseract.js pour PDF scannés |
| Texte | Paragraphes, titres, chapitres, segments, progression |
| Voix | Web Speech API — Lire, Pause, Reprise, Vitesse, Suivant |
| Sync | Texte surligné + page PDF + progression sauvegardée |
| IA | Résumé, Q&R, explication, recherche sémantique |
| Stockage | IndexedDB — documents, progression, préférences, historique |
| Auth | Inscription, connexion, profil (local) |
| PWA | Manifest installable, responsive mobile/desktop |

## Utilisation

1. **Importer un PDF** depuis l'accueil ou la bibliothèque
2. Attendre l'extraction (OCR automatique si scanné)
3. Ouvrir le **lecteur** → **Lire** pour la synthèse vocale
4. La progression est **sauvegardée** automatiquement
5. Créer un **compte** (Connexion → Inscription) pour le profil et préférences
6. Utiliser le panneau **Assistant IA** en bas du lecteur

## Test manuel

1. Importer un PDF texte → vérifier affichage + extraction
2. Cliquer **Lire** → entendre la voix, voir le segment surligné
3. **Pause** / **Reprendre** / modifier la **vitesse**
4. Fermer l'onglet, rouvrir le document → reprise au bon segment
5. Tester **Résumé** et **Question** dans le panneau IA
6. Créer un compte, modifier les préférences voix/langue

## Stack

- React 19 + TypeScript + Vite
- PDF.js, Tesseract.js, Dexie (IndexedDB)
- Web Speech API
