# Lumen Reader

Application lecteur PDF intelligente — **Phase 2 : Interface** (données fictives).

## Fonctionnalités actuelles

- Accueil avec statistiques et reprise de lecture
- Bibliothèque de documents fictifs
- Import PDF simulé (ajoute un document mock)
- Lecteur avec aperçu PDF placeholder, panneau texte et contrôles
- Simulation de lecture (segment suivant, pause, vitesse)

## Prochaines phases

| Phase | Contenu |
|-------|---------|
| 3 | Import PDF réel, affichage PDF.js, extraction texte |
| 4 | OCR |
| 5 | Traitement texte (paragraphes, chapitres, segments) |
| 6 | Synthèse vocale |
| 7 | Synchronisation texte / audio / page |
| 8 | IA |
| 9 | Stockage persistant |
| 10 | Authentification |
| 11 | Optimisation |

## Démarrage

```bash
cd pdf-reader
npm install
npm run dev
```

Ouvrir l’URL affichée (généralement http://localhost:5173).

## Test manuel (Phase 2)

1. **Accueil** — vérifier les stats et les documents récents
2. **Bibliothèque** — parcourir les 3 documents fictifs
3. **Import** — cliquer « Importer un PDF », choisir un fichier → redirection vers le lecteur
4. **Lecteur** — Lire / Pause / Précédent / Suivant / vitesse
5. **Segment actif** — le passage en cours est surligné dans le panneau texte
6. **Mobile** — navigation bas de page (Accueil / Bibliothèque)

## Stack

- React 19 + TypeScript + Vite
- React Router v6
