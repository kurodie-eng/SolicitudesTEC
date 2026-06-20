FROM php:8.2-apache

RUN docker-php-ext-install zip mysqli

COPY . /var/www/html/

RUN cp /var/www/html/php.ini /usr/local/etc/php/conf.d/app.ini
