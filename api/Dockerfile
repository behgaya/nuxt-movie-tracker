# PHP runtime for the API: FrankenPHP (Caddy + PHP), the server used by the official symfony-docker template
FROM dunglas/frankenphp:1-php8.5

RUN install-php-extensions pdo_pgsql intl opcache zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
