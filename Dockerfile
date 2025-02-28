FROM php:7.4-apache

RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

COPY . /var/www/html/

EXPOSE 80
CMD ["apache2-foreground"]