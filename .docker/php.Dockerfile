FROM php:7.3-fpm-alpine

# -------------------- Install dependancies
RUN apk add --update --no-cache \
        icu-libs \
        libintl \
        libxslt \
        freetype \
        libpng \
        libjpeg-turbo \
        yaml && \
# -------------------- Install build dependancies \
    apk add --update --no-cache --virtual .docker-php-global-dependancies \
        # Build dependency for intl \
        icu-dev \
        # Build dependencies for XML \
        libxml2-dev \
        ldb-dev \
        libxslt-dev \
        # Build dependencies for Image \
        libpng-dev libjpeg-turbo-dev freetype-dev \
        # Build dependencies for YAML \
        yaml-dev \
        # Build dependancies for Pecl \
        autoconf \
        g++ \
        make && \
# -------------------- Install php extensions \
    docker-php-ext-configure bcmath --enable-bcmath && \
    docker-php-ext-configure intl --enable-intl && \
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && \
    docker-php-ext-install bcmath intl xsl gd && \
    # Build dependancy for YAML \
    pecl install yaml && \
    docker-php-ext-enable yaml && \
# -------------------- Clean up \
    apk del .docker-php-global-dependancies && \
        rm -rf /var/cache/apk/* && \
        docker-php-source delete
