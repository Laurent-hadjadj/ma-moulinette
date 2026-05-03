#!/bin/sh
set -e

# Ensure SQL_DIR is set
if [ -z "$INIT_SQL_DIR" ]; then
  echo "INIT_SQL_DIR not set"
  exit 1
fi

# Mot de passe applicatif obligatoire (utilisé par 01_create_roles.sql via :'db_user_password')
if [ -z "$DB_USER_PASSWORD" ]; then
  echo "DB_USER_PASSWORD not set (required by 01_create_roles.sql)"
  exit 1
fi

cd "$INIT_SQL_DIR" || exit 1

# Find all .sql files recursively, sort them, and execute
for sql_file in $(find . -type f -name '*.sql' | sort); do
  echo "Executing $sql_file..."
  psql -v ON_ERROR_STOP=1 \
        -v db_user_password="$DB_USER_PASSWORD" \
      --username "$POSTGRES_USER" \
      --dbname "$POSTGRES_DB" \
      -f "$sql_file"
done
