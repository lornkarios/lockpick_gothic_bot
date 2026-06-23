FROM php:8.2.17-fpm-bullseye

RUN apt-get update \
    && apt-get install -y  \
        ca-certificates  \
        libzip-dev  \
        libonig-dev \
        libpng-dev  \
        git \
        nginx \
    && docker-php-ext-install bcmath pdo pdo_mysql zip gd mbstring exif pcntl

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer


ARG XDEBUG_FLAG
RUN if [ "$XDEBUG_FLAG" = "true" ]; \
        then \
            pecl install xdebug && docker-php-ext-enable xdebug; \
    fi

ARG SPX_FLAG
RUN if [ "$SPX_FLAG" = "true" ]; \
        then \
            cd /usr/lib && git clone https://github.com/NoiseByNorthwest/php-spx.git && cd /usr/lib/php-spx && phpize && ./configure && make && make install; \
    fi

RUN sed -i 's,^\(MinProtocol[ ]*=\).*,\1'TLSv1.0',g' /etc/ssl/openssl.cnf  \
    && sed -i 's,^\(CipherString[ ]*=\).*,\1'DEFAULT@SECLEVEL=1',g' /etc/ssl/openssl.cnf

RUN docker-php-ext-enable opcache
RUN docker-php-ext-install sockets

RUN apt-get install -y nginx && useradd -u 1000 -m www

COPY ./docker/php-nginx/www.conf               /usr/local/etc/php-fpm.d/zz-docker.conf
COPY ./docker/php-nginx/conf.d/php.ini         /usr/local/etc/php/conf.d/php.iniconf.d
COPY ./docker/php-nginx/nginx.conf             /etc/nginx/nginx.conf
COPY ./docker/php-nginx/entrypoint.sh          /entrypoint.sh

RUN chmod +x /entrypoint.sh \
    && mkdir -p /var/lib/nginx/body \
    && chown -R www:www /var/lib/nginx/ \
    && touch /run/nginx.pid \
    && chown -R www:www /run/nginx.pid \
    && chown -R www:www /var/log/nginx/

COPY --chown=www:www ./app /var/www/html

WORKDIR /var/www/html

RUN composer install --optimize-autoloader --no-dev

USER www

ENTRYPOINT sh /entrypoint.sh