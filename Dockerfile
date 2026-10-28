FROM php:8.5-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY . /var/www/html/