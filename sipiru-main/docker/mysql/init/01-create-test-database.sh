#!/bin/bash
# Membuat database terpisah untuk automated test: <DB_DATABASE>_test
set -e
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}_test\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}_test\`.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
SQL
