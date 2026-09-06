# Imagen de Moodle construida a partir de un tag/commit fijo del repo oficial.
# La versión de PHP y el ref de Moodle se pinean vía build args en docker-compose.yml,
# No "latest" — así queda claro sobre qué versión se está realizando la POC.

ARG PHP_IMAGE_TAG=8.3-apache
FROM php:${PHP_IMAGE_TAG}

ARG MOODLE_GIT_REPO=https://github.com/moodle/moodle.git
ARG MOODLE_GIT_REF
# MOODLE_GIT_REF es obligatorio: un tag (v4.5.13) o, mejor aún para IaC estricto,
# un commit SHA completo. Ver README.md sobre cómo resolverlo y pinearlo.
RUN test -n "$MOODLE_GIT_REF" || (echo "MOODLE_GIT_REF no definido" && exit 1)

RUN apt-get update && apt-get install -y --no-install-recommends \
      git unzip libpq-dev libzip-dev libpng-dev libonig-dev libxml2-dev \
      libicu-dev libxslt1-dev libfreetype6-dev libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql pdo_mysql mysqli zip gd intl soap xsl opcache \
    && rm -rf /var/lib/apt/lists/*

# Valores mínimos que pide el chequeo de entorno de Moodle (max_input_vars,
# memory_limit, etc.) — el php.ini default de la imagen no los cumple.
COPY moodle-php.ini /usr/local/etc/php/conf.d/moodle.ini

# Clonamos el tag/commit exacto (sin --depth 1 si MOODLE_GIT_REF es un SHA,
# porque shallow clone no siempre permite fetch de un commit arbitrario;
# ver nota en README).
RUN git clone --branch "${MOODLE_GIT_REF}" --depth 1 "${MOODLE_GIT_REPO}" /var/www/html \
    && rm -rf /var/www/html/.git \
    && mkdir -p /var/moodledata \
    && chown -R www-data:www-data /var/www/html /var/moodledata

# Registramos qué se construyó, queda embebido en la imagen para poder auditarla luego.
RUN echo "MOODLE_GIT_REF=${MOODLE_GIT_REF}" > /var/www/html/.build-version \
    && echo "PHP_IMAGE_TAG=${PHP_IMAGE_TAG}" >> /var/www/html/.build-version \
    && echo "BUILD_DATE=$(date -u +%Y-%m-%dT%H:%M:%SZ)" >> /var/www/html/.build-version

EXPOSE 80
