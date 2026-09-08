# Design original APK — Suivi paiements

## Fichiers à déployer

Extraire [supergenies-patch-design-v4.zip](supergenies-patch-design-v4.zip) dans `htdocs/` sur InfinityFree :

| Fichier | Destination |
|---------|-------------|
| `htdocs/suivi.php` | racine htdocs |
| `htdocs/includes/parent_ui.php` | `includes/` |
| `htdocs/assets/logo_spag.png` | `assets/` |
| `htdocs/assets/carousel_*.jpg` | `assets/` |

## Vérification

Ouvrir : `http://supergenies2026.site.je/suivi.php`

Vous devez voir :
- En-tête bleu navy + logo **C.S. LES SUPER GENIES**
- **Bienvenue chers parents**
- Carousel 3 images (félicitations, pétrochimie, inscriptions)
- Champ **Matricule élève** + bouton rouge **Rechercher**
- Barre du bas : Accueil · Messagerie · Inscriptions · Trousseau

L’APK Paiements v1.1.0 (WebView) charge `suivi.php?app=1` — aucune mise à jour APK nécessaire si le serveur est à jour.
