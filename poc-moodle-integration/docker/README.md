# POC Moodle — entorno local vía IaC

Dos instancias Moodle completas y reproducibles, definidas 100% en código (`Dockerfile` + `docker-compose.yml`), sin depender de imágenes de terceros con tags móviles. Ver [versions.md](./versions.md) para saber exactamente qué versión de Moodle/PHP/DB corre cada una — **resolver el TODO de esa tabla (pinear 4.1 a un commit exacto) antes de levantar `moodle-41`.**

## Componer contenedor
```bash
cd poc-moodle-integration/docker
docker compose up -d --build
```

- Moodle 4.5 LTS → http://localhost:8045
- Moodle 4.1 LTS → http://localhost:8041

## Instalar Moodle (primera vez, por instancia)

El `git clone` en el build solo trae el código; falta correr el instalador para crear `config.php`, tablas y el usuario admin.

### Recomendado — script único, sin tocar el wizard

```bash
cd poc-moodle-integration/docker
./setup.sh                          # usa acmaccan@gmail.com como admin email
./setup.sh otro-email@equipo.com    # o pasás el email que quieras
```

Levanta ambos contenedores, espera a que las bases de datos estén healthy, e
instala las dos instancias con **los mismos valores fijos que están en
`docker-compose.yml`** (dataroot `/var/moodledata`, dbhost `db-45`/`db-41`,
credenciales `moodle`/`moodle`). Es idempotente: si una instancia ya está
instalada, la salta. Esto evita justamente lo que nos pasó a mano: tipear mal
el dataroot o el dbhost en el wizard. Usá esto salvo que tengas una razón
puntual para pasar por el navegador.

### Alternativa — instalador web (para entender qué hace el script, o debug)

1. Abrir http://localhost:8045 (o :8041 para la 4.1). Moodle detecta que falta
   `config.php` y arranca el wizard.
2. Elegir idioma (ej. Español - Internacional / AR).
3. Pantalla **"Confirme las rutas"**:
   - **Dirección Web**: dejar el default (`http://localhost:8045`).
   - **Directorio de Moodle**: dejar el default (`/var/www/html`).
   - **Directorio de Datos**: ⚠️ **cambiar el default** (`/var/www/moodledata`)
     por **`/var/moodledata`** — es el path real que montamos como volumen
     persistente en `docker-compose.yml`. Si se deja el default, Moodle igual
     instala, pero los archivos subidos quedan en una carpeta que no persiste:
     se pierden al recrear el contenedor.
4. **Controlador de base de datos**: PostgreSQL (pgsql).
5. Pantalla de configuración de DB:
   - Host de la base de datos: `db-45` (o `db-41` para esa instancia — es el
     nombre del servicio en `docker-compose.yml`, no `localhost`).
   - Nombre de la base de datos: `moodle`
   - Usuario: `moodle`
   - Contraseña: `moodle`
   - Puerto/Socket/Tipo de tabla: dejar defaults.
6. Aceptar el chequeo de requisitos del servidor (debería dar todo ✔️ gracias
   a las extensiones instaladas en el `Dockerfile`).
7. Aceptar la licencia GPL.
8. Completar usuario admin (usar tu email real de equipo) y datos del sitio.
9. Esperar a que termine de crear las tablas → queda instalado.

Repetir igual para `moodle-41` en http://localhost:8041, apuntando a `db-41`.

## Decisiones de diseño
- **No `latest`**: cada imagen base, cada ref de Moodle y cada versión de base de datos está pineada explícitamente en [docker-compose.yml](./docker-compose.yml).
- **Versionado en git**: cualquiera que clone el repo y corra `docker compose up` obtiene exactamente el mismo entorno, hoy o dentro de 6 meses.
- **Auditable en runtime**: cada imagen deja registrado su propio ref de build (`/var/www/html/.build-version`), así que se puede verificar en cualquier momento qué quedó corriendo sin confiar en la memoria de nadie.
- **Descartable**: `docker compose down -v` destruye contenedores y volúmenes, sin dejar residuos en el host (a diferencia de una instalación desde ZIP).

## Bajar todo
```bash
docker compose down -v
```
