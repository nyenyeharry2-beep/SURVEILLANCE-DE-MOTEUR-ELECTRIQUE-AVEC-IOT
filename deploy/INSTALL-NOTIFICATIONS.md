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

## APK parents v1.1.2

**Zip :** `supergenies-apk-v1.1.2.zip` → installer `SuperGenies-Paiements-v1.1.2.apk`

Au premier lancement, accepter **Notifications** pour les alertes système.
