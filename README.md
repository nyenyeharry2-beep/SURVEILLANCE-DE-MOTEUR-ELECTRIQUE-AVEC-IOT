# VoltWatch — Surveillance moteur électrique IoT

Site web pour afficher les données d’un moteur (température, courant, vibration) avec **GitHub + Netlify + Firebase**.

## Ce que j’ai déjà préparé pour toi

- Page d’accueil (`index.html`)
- Tableau de bord (`dashboard.html`) avec **mode démo**
- Fichier Firebase prêt : `js/firebase-config.js`
- Config Netlify : `netlify.toml`

## Ce que TU dois faire (2 comptes)

Je ne peux pas créer Firebase / Netlify à ta place (il faut ton email).

### 1) Netlify (publier le site)

1. Va sur https://app.netlify.com
2. **Sign up with GitHub**
3. **Add new site → Import an existing project**
4. Choisis ce repo : `SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT`
5. Publish directory : `.` (point)
6. **Deploy**
7. Ouvre le lien `https://xxxxx.netlify.app`

Tu dois voir **VoltWatch** et le tableau de bord en mode démo.

### 2) Firebase (données réelles)

1. Va sur https://console.firebase.google.com
2. **Create a project**
3. Ajoute une app **Web** (`</>`)
4. Copie la config (`apiKey`, `projectId`, etc.)
5. Ouvre dans GitHub le fichier `js/firebase-config.js`
6. Remplace chaque `PLACEHOLDER` par ta config
7. **Commit changes**
8. Dans Firebase, active **Realtime Database**
9. Crée un objet comme ceci :

```json
{
  "motors": {
    "motor1": {
      "temperature": 62.5,
      "current": 11.2,
      "vibration": 1.8,
      "status": "OK"
    }
  }
}
```

10. Firebase → Authentication → Settings → Authorized domains  
    → ajoute ton domaine Netlify (`xxxxx.netlify.app`)

Après le redéploiement Netlify, le badge passe à **Firebase connecté**.

## Structure

```
index.html
dashboard.html
css/styles.css
js/firebase-config.js
js/app.js
netlify.toml
```
