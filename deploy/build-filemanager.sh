#!/bin/bash
# Génère le ZIP complet pour InfinityFree File Manager
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/deploy/pharmagest-filemanager-complet.zip"
STAGE="$ROOT/deploy/_filemanager_staging"

rm -rf "$STAGE"
mkdir -p "$STAGE/config" "$STAGE/database"

# Pages web racine
for f in index.php login.php logout.php dashboard.php medicaments.php stock.php \
         ventes.php achats.php caisse.php journal.php rapports.php recu.php \
         categories.php fournisseurs.php utilisateurs.php; do
  [ -f "$ROOT/$f" ] && cp "$ROOT/$f" "$STAGE/"
done

# Dossiers essentiels
cp -r "$ROOT/api" "$STAGE/"
cp -r "$ROOT/includes" "$STAGE/"
cp -r "$ROOT/assets" "$STAGE/"
cp -r "$ROOT/vendeur" "$STAGE/"

# Config exemple seulement (pas les secrets)
cp "$ROOT/config/config.example.php" "$STAGE/config/"

# Migrations SQL
cp "$ROOT/database/"*.sql "$STAGE/database/"

# htaccess
[ -f "$ROOT/.htaccess" ] && cp "$ROOT/.htaccess" "$STAGE/"

# Guide
cp "$ROOT/deploy/LISEZMOI-FILEMANAGER.txt" "$STAGE/LISEZMOI.txt"

cd "$STAGE"
zip -r "$OUT" . -x "*.DS_Store"
rm -rf "$STAGE"

# Alias nom court
cp "$OUT" "$ROOT/deploy/pharmagest-filemanager.zip"

echo "OK: $OUT"
echo "OK: $ROOT/deploy/pharmagest-filemanager.zip"
ls -lh "$OUT"
