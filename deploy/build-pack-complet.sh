#!/bin/bash
# Pack complet : ZIP site InfinityFree + APK mobile + guides
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PACK_DIR="$ROOT/deploy/_pack_staging"
OUT="$ROOT/deploy/pharmagest-pack-complet-v1.7.2.zip"

bash "$ROOT/deploy/build-filemanager.sh"

rm -rf "$PACK_DIR"
mkdir -p "$PACK_DIR"

cp "$ROOT/deploy/pharmagest-filemanager-complet.zip" "$PACK_DIR/"
cp "$ROOT/deploy/LISEZMOI-FILEMANAGER.txt" "$PACK_DIR/"
cp "$ROOT/deploy/NE-PAS-METTRE-DANS-FILEMANAGER.txt" "$PACK_DIR/"

if [ -f "$ROOT/deploy/nouvelle-eve-mobile.apk" ]; then
  cp "$ROOT/deploy/nouvelle-eve-mobile.apk" "$PACK_DIR/"
fi

cat > "$PACK_DIR/LIRE-EN-PREMIER.txt" << 'EOF'
PHARMACIE NOUVELLE EVE — PACK COMPLET v1.7.2
============================================

Contenu de ce pack :
  1. pharmagest-filemanager-complet.zip  → Site web InfinityFree (File Manager)
  2. nouvelle-eve-mobile.apk             → Application vendeur (téléphone)
  3. LISEZMOI-FILEMANAGER.txt            → Guide installation site
  4. NE-PAS-METTRE-DANS-FILEMANAGER.txt  → Erreurs à éviter

═══════════════════════════════════════════════════════════════
 ÉTAPE 1 — SITE WEB (InfinityFree File Manager → htdocs/)
═══════════════════════════════════════════════════════════════

1. Uploadez pharmagest-filemanager-complet.zip dans htdocs/
2. Clic droit → Extract DANS htdocs/ (pas dans un sous-dossier)
3. Ouvrez : https://mapharmaciepk.xo.je/setup.php
4. Entrez vos identifiants MySQL InfinityFree → Enregistrer
5. Connectez-vous : https://mapharmaciepk.xo.je/login.php
   admin@pharmagest.local / admin123
6. Mise à jour base : install_migration.php (connecté admin)
7. Vérification : diagnostic.php

Corrections incluses dans ce pack :
  ✓ setup.php — création automatique de config.php
  ✓ diagnostic.php — test serveur + base de données
  ✓ install_migration.php — migration SQL en un clic
  ✓ Vente comprimé / plaquette / flacon
  ✓ Import Excel médicaments + entrées stock
  ✓ Achats multi-produits + unités
  ✓ Heure locale Kinshasa
  ✓ Compatibilité SQL InfinityFree (erreurs 500 corrigées)

═══════════════════════════════════════════════════════════════
 ÉTAPE 2 — APK MOBILE (téléphone vendeur)
═══════════════════════════════════════════════════════════════

NE PAS mettre l'APK dans File Manager !
Transférez nouvelle-eve-mobile.apk sur le téléphone et installez-le.

═══════════════════════════════════════════════════════════════
 DÉPANNAGE
═══════════════════════════════════════════════════════════════

« Configuration manquante » → setup.php
Erreur 500 → diagnostic.php puis install_migration.php
403 sur achats.php → supprimez le dossier htdocs/achats/ s'il existe

Téléphone : +243990525309
EOF

cd "$PACK_DIR"
zip -r "$OUT" . -x "*.DS_Store"
rm -rf "$PACK_DIR"

echo "OK: $OUT"
ls -lh "$OUT"
