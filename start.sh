#!/bin/bash
php-fpm -D
sed -i "s/RAILWAY_PORT/${PORT:-80}/" /etc/nginx/nginx.conf
nginx -g "daemon off;"