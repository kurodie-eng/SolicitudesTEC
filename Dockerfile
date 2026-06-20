FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx bash openssl && \
    docker-php-ext-install mysqli


RUN echo "allow_url_fopen = On" > /usr/local/etc/php/conf.d/custom.ini

COPY nginx.conf /etc/nginx/nginx.conf
COPY start.sh /start.sh
RUN chmod +x /start.sh

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["/start.sh"]
