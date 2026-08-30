#!/bin/bash
# =====================================================================
# KARE sandbox launcher
# Starts MariaDB, (re)creates + migrates db_kare, then serves the app
# with PHP's built-in server.
# =====================================================================
set -e

echo "Starting MariaDB..."
service mariadb start
sleep 2

DB_DIR="$(dirname "$0")/db"

echo "Ensuring database exists..."
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS db_kare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "Applying schema/migrations (safe to re-run)..."
mysql -uroot db_kare < "$DB_DIR/db_kare_backup_16-7.sql" 2>/dev/null || true
mysql -uroot db_kare < "$DB_DIR/02_states_districts.sql" 2>/dev/null || true
mysql -uroot db_kare < "$DB_DIR/03_seed_states_districts.sql"
mysql -uroot db_kare < "$DB_DIR/04_reports_and_meds.sql" 2>/dev/null || true
mysql -uroot db_kare < "$DB_DIR/05_user_side_modules.sql" 2>/dev/null || true

echo "Starting PHP server on http://localhost:8000 ..."
cd "$(dirname "$0")"
php -S 0.0.0.0:8000 -t .
