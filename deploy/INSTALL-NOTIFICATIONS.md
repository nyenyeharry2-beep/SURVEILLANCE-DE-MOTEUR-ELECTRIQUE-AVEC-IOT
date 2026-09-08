# Notifications communiqués (style WhatsApp)

## Fonctionnement

1. **Admin** publie un communiqué
2. **Suivi / APK** :
   - Bandeau en haut de l'écran (comme WhatsApp)
   - **Balayez vers le haut** ou **sur le côté** pour fermer
   - Chaque téléphone mémorise les communiqués fermés (localStorage)
3. **APK v1.1.2** : notification système Android (barre de statut) si permission accordée

Vérification automatique toutes les 45 secondes + à l'ouverture de l'app.

## Fichiers File Manager (htdocs)

| Fichier | Destination |
|---------|-------------|
| `api/communiques.php` | `/htdocs/api/communiques.php` |
| `suivi.php` | `/htdocs/suivi.php` |
| `includes/parent_ui.php` | `/htdocs/includes/parent_ui.php` |

**Zip serveur :** `supergenies-patch-notifications-v6.zip`

## APK parents v1.1.3 (signée — installable)

**Zip :** `supergenies-apk-v1.1.3.zip`

Contient :
- `SuperGenies-Paiements-v1.1.3.apk` (Suivi + notifications)
- `SuperGenies-Admin-v1.1.2.apk` (Admin + communiqués)

Au premier lancement Suivi, accepter **Notifications**.

**Important :** désinstaller l'ancienne app avant d'installer la v1.1.3 si l'installation échoue.
