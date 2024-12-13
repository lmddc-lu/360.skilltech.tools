FROM php:8.2-fpm
RUN docker-php-ext-install -j$(nproc) pdo_mysql
RUN apt-get update; \
    apt-get install -y --no-install-recommends libzip4 libzip-dev libicu-dev unzip;
RUN docker-php-ext-configure intl \
    && docker-php-ext-install intl
RUN docker-php-ext-configure exif \
    && docker-php-ext-install exif
RUN docker-php-ext-configure zip \
    && docker-php-ext-install zip
RUN mkdir -p /usr/share/nginx/
RUN curl -o /usr/share/nginx/composer.phar https://getcomposer.org/download/latest-stable/composer.phar
COPY composer.json composer.lock /usr/share/nginx/
RUN cd /usr/share/nginx/ && php composer.phar install
RUN rm /usr/share/nginx/composer.phar
