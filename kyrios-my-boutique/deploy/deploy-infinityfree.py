#!/usr/bin/env python3
"""Deploy Kyrios My Boutique to InfinityFree via FTP + MySQL."""

import ftplib
import os
import sys
import pymysql

FTP_HOST = 'ftpupload.net'
FTP_USER = 'if0_42727746'
FTP_PASS = '3SJiUMv1dY'

DB_HOST = 'sql301.infinityfree.com'
DB_USER = 'if0_42727746'
DB_PASS = '3SJiUMv1dY'
DB_NAME = 'if0_42727746_kyriosboutique'

LOCAL_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'public'))
DEPLOY_ROOT = os.path.dirname(__file__)

SKIP_FILES = {'.gitkeep'}
SKIP_DIRS = {'.git', '__pycache__'}


def ftp_makedirs(ftp, path):
    parts = [p for p in path.strip('/').split('/') if p]
    current = ''
    for part in parts:
        current += '/' + part
        try:
            ftp.mkd(current)
        except ftplib.error_perm:
            pass


def ftp_upload_file(ftp, local_path, remote_path):
    remote_path = remote_path.replace('\\', '/')
    remote_dir = os.path.dirname(remote_path).replace('\\', '/')
    if remote_dir:
        ftp_makedirs(ftp, remote_dir)
    with open(local_path, 'rb') as f:
        ftp.storbinary(f'STOR {remote_path}', f)


def upload_tree(ftp, local_root, remote_prefix='htdocs'):
    count = 0
    for dirpath, dirnames, filenames in os.walk(local_root):
        dirnames[:] = [d for d in dirnames if d not in SKIP_DIRS]
        for name in filenames:
            if name.startswith('.') and name != '.env' and name != '.htaccess':
                continue
            if name in SKIP_FILES:
                continue
            local = os.path.join(dirpath, name)
            rel = os.path.relpath(local, local_root).replace('\\', '/')
            remote = f'{remote_prefix}/{rel}'
            try:
                ftp_upload_file(ftp, local, remote)
                count += 1
                if count % 10 == 0 or 'uploads/products' in remote:
                    print(f'  ↑ {remote} ({os.path.getsize(local)//1024} Ko)')
            except Exception as e:
                print(f'  ✗ {remote}: {e}')
    return count


def run_sql():
    sql_path = os.path.join(DEPLOY_ROOT, 'catalog-kyrios-v2.sql')
    with open(sql_path, 'r', encoding='utf-8') as f:
        sql = f.read()

    conn = pymysql.connect(
        host=DB_HOST, user=DB_USER, password=DB_PASS,
        database=DB_NAME, charset='utf8mb4', connect_timeout=30
    )
    cur = conn.cursor()
    done = 0
    buffer = ''
    for line in sql.split('\n'):
        line = line.strip()
        if not line or line.startswith('--'):
            continue
        buffer += line + ' '
        if line.endswith(';'):
            stmt = buffer.strip()
            buffer = ''
            try:
                cur.execute(stmt)
                done += 1
            except Exception as e:
                msg = str(e)
                if 'Duplicate' not in msg:
                    print(f'  ! SQL: {msg[:120]}')
    conn.commit()
    conn.close()
    return done


def main():
    print('=== Déploiement Kyrios My Boutique ===\n')

    env_file = os.path.join(LOCAL_ROOT, '.env')
    if not os.path.exists(env_file):
        template = os.path.join(DEPLOY_ROOT, 'htdocs-env-template.env')
        import shutil
        shutil.copy(template, env_file)
        print('  ✓ .env créé depuis template')

    print('[1/3] Connexion FTP...')
    ftp = ftplib.FTP(FTP_HOST, timeout=120)
    ftp.login(FTP_USER, FTP_PASS)
    print(f'  ✓ Connecté à {FTP_HOST}')

    print('\n[2/3] Upload fichiers web + photos...')
    count = upload_tree(ftp, LOCAL_ROOT)
    ftp.quit()
    print(f'  ✓ {count} fichiers uploadés')

    print('\n[3/3] Migration SQL catalog-v2...')
    try:
        n = run_sql()
        print(f'  ✓ {n} requêtes SQL exécutées')
    except Exception as e:
        print(f'  ✗ Erreur SQL: {e}')
        sys.exit(1)

    print('\n=== DÉPLOIEMENT TERMINÉ ===')
    print('Site       : https://kyriosboutique.page.gd/marketplace.php')
    print('Upload     : https://kyriosboutique.page.gd/seller-upload.php')
    print('Compte     : boutique@kyrios.page.gd / password')


if __name__ == '__main__':
    main()
