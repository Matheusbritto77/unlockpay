FROM php:8.3-fpm

# Instala extensões e pacotes de sistema
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev libpq-dev \
    libjpeg-dev libfreetype6-dev libicu-dev libmcrypt-dev libssl-dev \
    nginx supervisor nodejs npm gnupg2 nano \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath intl \
    && apt-get clean

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instala Node.js 18 e NPM
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

# Configura diretório de trabalho
WORKDIR /var/www

# Copia todos os arquivos da aplicação Laravel
COPY . .

# Instala dependências do PHP
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Instala dependências do Node
RUN npm ci && chmod +x node_modules/.bin/vite && npm run build

# Permissões corretas
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www

# Expõe a porta do Nginx
EXPOSE 80

# Inicia PHP-FPM e Nginx via Supervisor
COPY ./docker/supervisord.conf /etc/supervisord.conf
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
