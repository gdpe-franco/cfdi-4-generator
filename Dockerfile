FROM php:8.4-cli

RUN apt-get update \
    && apt-get install --yes --no-install-recommends libzip-dev unzip \
    && docker-php-ext-install bcmath zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
