#!/bin/sh

# Directory containing SQL files
#SQL_DIR is in environment in docker compose file, the directory is mount also
#SQL_DIR="/root/test"

# Change to the directory
cd "$SQL_DIR" || exit

# Execute each SQL file in alphabetical order
for sql_file in $(ls *.sql | sort); do
  echo "Executing $sql_file..."
  psql -v ON_ERROR_STOP=1 --username "postgres" --no-password --no-psqlrc -f "$sql_file"
done
