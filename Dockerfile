FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends unzip \
    && docker-php-ext-install pdo_mysql bcmath \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

EXPOSE 80
CMD ["sh", "-c", "php artisan migrate --force && php artisan storage:link --force && chown -R www-data:www-data storage bootstrap/cache && apache2-foreground"]