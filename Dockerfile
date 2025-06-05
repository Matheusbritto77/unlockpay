FROM php:8.3-fpm

# Instala dependências do sistema, libs PHP, nginx e nodejs
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev libpq-dev \
    libjpeg-dev libfreetype6-dev libicu-dev libssl-dev gnupg2 \
    nginx nano nodejs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo pdo_mysql mbstring zip exif pcntl bcmath intl gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copia o Composer oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define diretório de trabalho
WORKDIR /var/www

# Copia código
COPY . .

# Instala dependências PHP do Laravel
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Ajusta permissões
RUN chmod +x node_modules/.bin/vite || true \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && find /var/www -type f -exec chmod 644 {} \; \
    && find /var/www -type d -exec chmod 755 {} \; \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

# Remove configuração default do nginx para evitar a página welcome
RUN rm -f /etc/nginx/sites-enabled/default

# Cria arquivo de configuração do nginx embutido
RUN echo 'server { \
    listen 80; \
    server_name localhost; \
    root /var/www/public; \
    index index.php index.html index.htm; \
    access_log /var/log/nginx/access.log; \
    error_log /var/log/nginx/error.log; \
    location / { try_files $uri $uri/ /index.php?$query_string; } \
    location ~ \.php$ { \
        include fastcgi_params; \
        fastcgi_pass unix:/run/php/php-fpm.sock; \
        fastcgi_index index.php; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
    } \
    location ~ /\.ht { deny all; } \
}' > /etc/nginx/conf.d/default.conf

# Ajusta php-fpm para usar socket Unix em /run/php/php-fpm.sock
RUN sed -i 's/listen = .*/listen = \/run\/php\/php-fpm.sock/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/listen.owner = .*/listen.owner = www-data/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/listen.group = .*/listen.group = www-data/' /usr/local/etc/php-fpm.d/www.conf

# Cria script de entrada para iniciar php-fpm e nginx juntos
RUN echo '#!/bin/bash\n\
set -e\n\
# Cria pasta para socket php-fpm\n\
mkdir -p /run/php\n\
# Inicia php-fpm em background\n\
php-fpm &\n\
# Inicia nginx em foreground\n\
nginx -g "daemon off;"' > /entrypoint.sh \
    && chmod +x /entrypoint.sh

# Expõe porta HTTP
EXPOSE 80

# Define o script de entrada
ENTRYPOINT ["/entrypoint.sh"]
