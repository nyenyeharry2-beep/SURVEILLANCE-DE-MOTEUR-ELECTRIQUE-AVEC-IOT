# ESP32 → Lumen (`mesure.php`)

Le message `Erreur POST mesure HTTP 404` avec une page HTML `nginx` signifie que **le fichier PHP n’a pas été trouvé** (mauvaise URL, site Netlify statique, ou `mesure.php` absent de `htdocs`).

## URL à utiliser

```
http://VOTRE-DOMAINE/mesure.php
```

Exemple : `http://otornyenye.rf.gd/mesure.php`

- Mettez **`mesure.php`** dans `htdocs` (pas seulement le site web).
- N’envoyez **pas** vers `https://….netlify.app/mesure` : Netlify ne lance pas PHP et nginx répond 404.
- Préférez **HTTP** si le certificat HTTPS InfinityFree est expiré.
- En-tête `X-Device-Key: lumen-esp32-nyenye-7f3a9c` (ou le champ JSON `key`).
- User-Agent de type navigateur (InfinityFree bloque souvent un client vide).

Test navigateur : ouvrez `http://VOTRE-DOMAINE/mesure.php` — vous devez voir du JSON (`"endpoint":"mesure"`), pas une page 404 nginx.
Test ping : `http://VOTRE-DOMAINE/ping.php` → `{"ok":true,...}`.
