FROM php:8.3-fpm-bookworm

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        ca-certificates \
        chromium \
        curl \
        fonts-dejavu-core \
        fonts-liberation \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        procps \
        unzip \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        soap \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && if [ ! -x /usr/bin/chromium ]; then ln -sf "$(command -v chromium || command -v chromium-browser)" /usr/bin/chromium; fi \
    && chromium --version \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/nubicta.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
