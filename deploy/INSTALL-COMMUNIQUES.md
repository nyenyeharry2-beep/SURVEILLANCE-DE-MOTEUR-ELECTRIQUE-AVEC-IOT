# Communiqués Admin → Suivi paiements (APK)

## Fonctionnement

1. **Admin** (APK ou web) → onglet **Communiqués** → publier un message
2. **Suivi paiements** (APK parents ou web) → accueil → section **Communiqués de l'école**
3. **Suppression admin** → le message disparaît immédiatement sur Suivi (même base MySQL)

La table `communiques` est créée automatiquement au premier accès (`bootstrap.php`).

## Fichiers à uploader (File Manager → htdocs)

| Fichier | Destination |
|---------|-------------|
| `htdocs/admin/portail.php` | `/htdocs/admin/portail.php` |
| `htdocs/suivi.php` | `/htdocs/suivi.php` |
| `htdocs/includes/parent_ui.php` | `/htdocs/includes/parent_ui.php` |
| `htdocs/config/bootstrap.php` | `/htdocs/config/bootstrap.php` |

**Zip :** `deploy/supergenies-patch-communiques-v5.zip`

## Test

1. Admin → **Communiqués** → titre + message → **Publier**
2. Ouvrir `suivi.php` ou APK Suivi → le communiqué apparaît sous « Bienvenue chers parents »
3. Admin → **Supprimer** → rafraîchir Suivi → le communiqué a disparu

Aucune mise à jour APK nécessaire (WebView charge le serveur).
