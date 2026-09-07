#!/usr/bin/env bash
# Levanta y configura las dos instancias de punta a punta, sin pasar por el
# wizard web. Los valores (dataroot, dbhost, credenciales) están fijos acá
# y coinciden 1:1 con docker-compose.yml -- una sola fuente de verdad,
# sin margen para que alguien tipee mal un campo en el navegador.
#
# Uso: ./setup.sh [admin-email]
# Requiere: docker compose, bash. Idempotente: si una instancia ya tiene
# config.php, la saltea en vez de reinstalar encima.

set -euo pipefail
cd "$(dirname "$0")"

# En Git Bash / MSYS (Windows), argumentos como /var/moodledata se
# "traducen" a rutas de Windows (ej. C:/Program Files/Git/var/moodledata)
# antes de llegar a docker. Esto rompe --dataroot=/var/moodledata silenciosamente.
# MSYS_NO_PATHCONV desactiva esa traducción. No-op en Linux/macOS.
export MSYS_NO_PATHCONV=1

ADMIN_EMAIL="${1:-acmaccan@gmail.com}"
ADMIN_PASS="Admin1234!"

install_instance () {
  local service="$1" port="$2" dbhost="$3" shortname="$4" fullname="$5"

  if docker compose exec -T "$service" test -f /var/www/html/config.php 2>/dev/null; then
    echo "==> $service ya tiene config.php, se omite instalación."
    return
  fi

  echo "==> Instalando $service (puerto $port, db $dbhost)..."
  docker compose exec -T "$service" php admin/cli/install.php \
    --non-interactive --agree-license \
    --wwwroot="http://localhost:${port}" \
    --dataroot=/var/moodledata \
    --dbtype=pgsql --dbhost="$dbhost" --dbname=moodle --dbuser=moodle --dbpass=moodle \
    --fullname="$fullname" --shortname="$shortname" \
    --adminuser=admin --adminpass="$ADMIN_PASS" --adminemail="$ADMIN_EMAIL"

  # install.php corre como root (usuario default de `exec`), así que
  # config.php queda root:root 640 -- Apache (www-data) no puede leerlo.
  # Sin esto, el sitio tira "Permission denied" al abrir config.php.
  docker compose exec -T "$service" chown www-data:www-data /var/www/html/config.php
}

echo "==> Levantando contenedores (build si hace falta)..."
docker compose up -d --build

echo "==> Esperando a que las bases de datos estén healthy..."
for svc in db-45 db-41; do
  until [ "$(docker compose ps -q "$svc" | xargs docker inspect -f '{{.State.Health.Status}}')" = "healthy" ]; do
    sleep 2
  done
done

install_instance moodle-45 8045 db-45 poc45 "POC Moodle 4.5"
install_instance moodle-41 8041 db-41 poc41 "POC Moodle 4.1"

cat <<EOF

Listo.
  Moodle 4.5 -> http://localhost:8045  (admin / ${ADMIN_PASS})
  Moodle 4.1 -> http://localhost:8041  (admin / ${ADMIN_PASS})
EOF
