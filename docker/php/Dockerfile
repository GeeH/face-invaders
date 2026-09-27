FROM php:8.5-cli

COPY --from=ghcr.io/mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions bcmath intl pcntl pdo_pgsql zip

# Run as the host user's UID/GID so files created in the bind mount stay editable.
ARG UID=1000
ARG GID=1000
RUN groupadd --force --gid ${GID} app \
    && useradd --uid ${UID} --gid ${GID} --create-home --non-unique app

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint

USER app
WORKDIR /var/www/html

ENTRYPOINT ["entrypoint"]
