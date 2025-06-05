FROM php:8.3-fpm

# Instala dependências básicas + extensões PHP necessárias
RUN apt-get update && apt-get install -y \
    nginx curl zip unzip git libzip-dev libpng-dev libonig-dev libxml2-dev \
    libjpeg-dev libfreetype6-dev libicu-dev libssl-dev gnupg2 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring zip bcmath intl gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala Composer (copiando da imagem oficial)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instala Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Configura PHP-FPM para usar socket
RUN sed -i 's|listen = .*|listen = /var/run/php/php-fpm.sock|' /usr/local/etc/php-fpm.d/www.conf \
    && echo -e "listen.owner = www-data\nlisten.group = www-data" >> /usr/local/etc/php-fpm.d/www.conf

# Configura NGINX
RUN rm /etc/nginx/sites-enabled/default && \
    echo 'server { \
        listen 80; \
        server_name localhost; \
        root /var/www/public; \
        index index.php index.html; \
        location / { try_files $uri $uri/ /index.php?$query_string; } \
        location ~ \.php$ { \
            include fastcgi_params; \
            fastcgi_pass unix:/var/run/php/php-fpm.sock; \
            fastcgi_index index.php; \
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        } \
        location ~ /\. { deny all; } \
    }' > /etc/nginx/conf.d/default.conf

# Define diretório da aplicação
WORKDIR /var/www
COPY . .

# Instala dependências do Laravel e ajusta permissões
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint: inicia PHP-FPM e NGINX juntos
COPY ./entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
