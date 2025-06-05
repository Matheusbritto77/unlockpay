FROM php:8.3-fpm

# Instala dependências
RUN apt-get update && apt-get install -y \
    nginx curl zip unzip git nano libzip-dev libpng-dev libonig-dev libxml2-dev \
    libjpeg-dev libfreetype6-dev libicu-dev libssl-dev gnupg2 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath intl gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt-get install -y nodejs

# Configura PHP-FPM
RUN sed -i 's|listen = .*|listen = /var/run/php/php-fpm.sock|' /usr/local/etc/php-fpm.d/www.conf \
    && echo "listen.owner = www-data\nlisten.group = www-data" >> /usr/local/etc/php-fpm.d/www.conf

# Configuração mínima do NGINX
RUN echo 'server { \
    listen 80; \
    server_name localhost; \
    root /var/www/public; \
    index index.php index.html; \
    access_log /var/log/nginx/access.log; \
    error_log /var/log/nginx/error.log; \
    location / { try_files $uri $uri/ /index.php?$query_string; } \
    location ~ \.php$ { \
        include fastcgi_params; \
        fastcgi_pass unix:/var/run/php/php-fpm.sock; \
        fastcgi_index index.php; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
    } \
    location ~ /\.ht { deny all; } \
}' > /etc/nginx/conf.d/default.conf

# Código da aplicação
WORKDIR /var/www
COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
    && chmod +x node_modules/.bin/vite || true \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R ug+rwx storage bootstrap/cache

# Cria entrypoint
RUN echo '#!/bin/bash\n\
set -e\n\
mkdir -p /var/run/php\n\
php-fpm -F &\n\
nginx -g "daemon off;"' > /entrypoint.sh && chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
