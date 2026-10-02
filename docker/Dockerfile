FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers

# Composer is only needed to install PHPUnit for the test runner.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Allow .htaccess overrides (used to block script execution in the upload folder)
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

COPY docker/php.ini /usr/local/etc/php/conf.d/drivenow.ini
