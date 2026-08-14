FROM php:8.4-cli

RUN apt-get update \
    && apt-get install --yes --no-install-recommends bzip2 curl libxslt1-dev libzip-dev unzip \
    && docker-php-ext-install bcmath xsl zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
