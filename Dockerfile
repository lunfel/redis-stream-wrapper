ARG PHP_VERSION="8.1"

FROM php:$PHP_VERSION-cli AS base

RUN pecl install redis && docker-php-ext-enable redis

FROM base AS dev

RUN pecl install xdebug && docker-php-ext-enable xdebug

# Configure Xdebug for coverage mode
RUN echo "zend_extension=xdebug" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.mode=debug,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
