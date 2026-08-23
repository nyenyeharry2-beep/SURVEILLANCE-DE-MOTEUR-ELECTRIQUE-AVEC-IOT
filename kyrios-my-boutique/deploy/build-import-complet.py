#!/usr/bin/env python3
"""Build complete Kyrios deployment package for InfinityFree import."""

import os
import shutil
import zipfile

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
DEPLOY = os.path.dirname(__file__)
PUBLIC = os.path.join(ROOT, 'public')
OUT_DIR = os.path.join(DEPLOY, 'pack-complet')
ZIP_PATH = os.path.join(DEPLOY, 'KYRIOS-IMPORT-COMPLET.zip')


def main():
    if os.path.exists(OUT_DIR):
        shutil.rmtree(OUT_DIR)
    os.makedirs(OUT_DIR)

    # 1. LISEZMOI
    shutil.copy(os.path.join(DEPLOY, 'LISEZMOI.txt'), OUT_DIR)
    shutil.copy(os.path.join(DEPLOY, 'GUIDE-PHOTOS.txt'), OUT_DIR)

    # 2. SQL
    sql_dir = os.path.join(OUT_DIR, 'sql')
    os.makedirs(sql_dir)
    for f in ['kyriosboutique-database.sql', 'update-v2.sql', 'catalog-kyrios.sql', 'catalog-kyrios-v2.sql']:
        shutil.copy(os.path.join(DEPLOY, f), sql_dir)

    # 3. htdocs (site complet)
    htdocs = os.path.join(OUT_DIR, 'htdocs')
    shutil.copytree(PUBLIC, htdocs, ignore=shutil.ignore_patterns('.gitkeep'))

    # .env production
    env_src = os.path.join(DEPLOY, 'htdocs-env-template.env')
    shutil.copy(env_src, os.path.join(htdocs, '.env'))

    # 4. photos separees (backup)
    photos_dir = os.path.join(OUT_DIR, 'photos-produits')
    src_photos = os.path.join(PUBLIC, 'uploads', 'products')
    if os.path.isdir(src_photos):
        shutil.copytree(src_photos, photos_dir, ignore=shutil.ignore_patterns('.gitkeep'))

    # 5. Instructions rapides
    with open(os.path.join(OUT_DIR, 'INSTALLATION-RAPIDE.txt'), 'w', encoding='utf-8') as f:
        f.write("""═══════════════════════════════════════════════════════════
  KYRIOS MY BOUTIQUE — Installation InfinityFree
═══════════════════════════════════════════════════════════

ÉTAPE 1 — BASE DE DONNÉES (phpMyAdmin)
  Importer dans l'ordre :
  1. sql/kyriosboutique-database.sql  (structure)
  2. sql/update-v2.sql                (paiements)
  3. sql/catalog-kyrios.sql           (16 produits)
  4. sql/catalog-kyrios-v2.sql        (+8 produits = 24 total)

ÉTAPE 2 — FICHIERS WEB (File Manager)
  1. Ouvrir htdocs sur InfinityFree
  2. Uploader TOUT le contenu du dossier htdocs/ 
     (index.php, seller-upload.php, uploads/, etc.)
  3. Vérifier que .env est présent à la racine htdocs

ÉTAPE 3 — PHOTOS (si pas déjà dans htdocs/uploads/products/)
  Option A : photos-produits/ → copier dans htdocs/uploads/products/
  Option B : https://kyriosboutique.page.gd/seller-upload.php
             (boutique@kyrios.page.gd / password)

ÉTAPE 4 — TESTER
  https://kyriosboutique.page.gd/marketplace.php

Comptes test (mot de passe: password):
  demo.client@kyrios.local   → Client
  boutique@kyrios.page.gd    → Vendeur catalogue

═══════════════════════════════════════════════════════════
""")

    # Create zip
    if os.path.exists(ZIP_PATH):
        os.remove(ZIP_PATH)

    with zipfile.ZipFile(ZIP_PATH, 'w', zipfile.ZIP_DEFLATED) as zf:
        for dirpath, _, filenames in os.walk(OUT_DIR):
            for name in filenames:
                full = os.path.join(dirpath, name)
                arc = os.path.relpath(full, OUT_DIR)
                zf.write(full, arc)

    size_mb = os.path.getsize(ZIP_PATH) / (1024 * 1024)
    print(f'✓ Package créé: {ZIP_PATH}')
    print(f'  Taille: {size_mb:.2f} Mo')
    print(f'  Contenu: htdocs + sql + photos + guides')


if __name__ == '__main__':
    main()
