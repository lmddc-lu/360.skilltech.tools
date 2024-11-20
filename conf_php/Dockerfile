FROM php:8.2-fpm
RUN docker-php-ext-install -j$(nproc) pdo_mysql
RUN apt-get update; \
    apt-get install -y libmagickwand-dev libzip4 libzip-dev; \
    pecl install imagick; \
    docker-php-ext-enable imagick;
RUN docker-php-ext-configure intl \
    && docker-php-ext-install intl
RUN docker-php-ext-configure exif \
    && docker-php-ext-install exif
RUN docker-php-ext-configure zip \
    && docker-php-ext-install zip
