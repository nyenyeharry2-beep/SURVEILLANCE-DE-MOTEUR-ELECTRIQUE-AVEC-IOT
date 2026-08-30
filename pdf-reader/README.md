# Lumen Reader — Application hors ligne

Lecteur PDF **100 % autonome** : fonctionne **sans internet** après la première installation.

## Installation (une seule fois avec internet)

```bash
cd pdf-reader
npm install
npm run build
npm run preview
```

Ouvrez l’URL affichée (ex. http://localhost:4173), puis :

### Sur ordinateur (Chrome / Edge)
1. Cliquez sur **Installer l'application** (bannière en haut)
2. Ou menu navigateur → **Installer Lumen Reader**

### Sur Android
1. Ouvrez l’app dans **Chrome**
2. Menu ⋮ → **Ajouter à l'écran d'accueil** / **Installer l'application**

### Sur iPhone (Safari)
1. Ouvrir dans Safari
2. Bouton **Partager** → **Sur l'écran d'accueil**

---

## Après installation : plus besoin d'internet

| Fonction | Hors ligne |
|----------|------------|
| Importer PDF | ✅ |
| Afficher PDF | ✅ |
| Extraire texte | ✅ |
| OCR (PDF scanné) | ✅ |
| Synthèse vocale | ✅ |
| Progression sauvegardée | ✅ |
| Bibliothèque | ✅ |
| Compte / profil | ✅ |
| IA locale (résumé basique) | ✅ |
| IA OpenAI | ❌ (nécessite internet + clé API) |

Toutes les données sont stockées **localement** dans votre appareil (IndexedDB).

---

## Développement

```bash
npm run dev
```

## Préparer les ressources offline (OCR)

```bash
npm run setup:offline
```

Télécharge les fichiers OCR français/anglais (~17 Mo) et copie Tesseract dans `public/`.

---

## Héberger sur un serveur local (sans cloud)

Après `npm run build`, copiez le dossier `dist/` sur une clé USB ou un mini-serveur :

```bash
npx serve dist -p 8080
```

Installez la PWA une fois → fonctionne ensuite **sans connexion**.

---

## Stack offline

- **PWA** + Service Worker (vite-plugin-pwa)
- **PDF.js** — rendu et extraction locale
- **Tesseract.js** — OCR local (fra + eng)
- **Web Speech API** — voix système
- **Dexie / IndexedDB** — stockage local

## IA avancée (optionnel, internet requis)

```env
VITE_OPENAI_API_KEY=sk-...
```

Sans clé ou sans internet : l'assistant IA utilise le **mode local**.
