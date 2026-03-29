FROM php:8.3-apache

# Install PHP extensions and Apache rewrite
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Set Apache document root to /var/www/html/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Optional: silence Apache ServerName warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# App lives under /var/www/html
WORKDIR /var/www/html

# Copy project files into Apache's expected directory
COPY . /var/www/html

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Render uses PORT, default web-service port is 10000
EXPOSE 10000

CMD sh -c 'sed -i "s/Listen 80/Listen ${PORT:-10000}/" /etc/apache2/ports.conf && \
sed -i "s/:80/:${PORT:-10000}/" /etc/apache2/sites-available/000-default.conf && \
apache2-foreground'