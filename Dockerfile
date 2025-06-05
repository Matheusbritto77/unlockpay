FROM php:8.3-fpm

# Instala dependências e extensões PHP necessárias
RUN apt-get update && apt-get install -y \
    nginx curl zip unzip git libzip-dev libpng-dev libonig-dev libxml2-dev \
    libjpeg-dev libfreetype6-dev libicu-dev libssl-dev gnupg2 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring zip bcmath intl gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instala Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Configura PHP-FPM para usar socket com dono www-data
RUN sed -i 's|listen = .*|listen = /var/run/php/php-fpm.sock|' /usr/local/etc/php-fpm.d/www.conf \
    && printf "\nlisten.owner = www-data\nlisten.group = www-data\n" >> /usr/local/etc/php-fpm.d/www.conf

# Configura NGINX
RUN rm -f /etc/nginx/sites-enabled/default && \
    echo 'server { \
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
        location ~ /\. { deny all; } \
    }' > /etc/nginx/conf.d/default.conf

# Define diretório da aplicação
WORKDIR /var/www

# Copia todo o código
COPY . .

# Instala dependências PHP do Laravel e prepara permissões
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Instala dependências Node e faz build do frontend (ajuste o comando caso use yarn)
RUN npm install --legacy-peer-deps && npm run build

# Expondo porta 80
EXPOSE 80

# Entrypoint inline para iniciar PHP-FPM e NGINX
CMD ["/bin/bash", "-c", "mkdir -p /var/run/php && php-fpm -D && nginx -g 'daemon off;'"]
