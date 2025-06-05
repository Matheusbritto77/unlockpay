FROM php:8.3-apache

# Instala dependências e extensões PHP necessárias
RUN apt-get update && apt-get install -y \
    curl zip unzip git libzip-dev libpng-dev libonig-dev libxml2-dev \
    libjpeg-dev libfreetype6-dev libicu-dev libssl-dev gnupg2 nodejs npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring zip bcmath intl gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Ativa mod_rewrite do Apache (necessário para Laravel)
RUN a2enmod rewrite

# Define diretório da aplicação
WORKDIR /var/www

# Copia código da aplicação para /var/www
COPY . .

# Ajusta permissões para storage e cache do Laravel
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Configura Apache para apontar para /var/www/public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/public|' /etc/apache2/sites-available/000-default.conf

# Habilita Override All para permitir .htaccess funcionar (Laravel usa isso)
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Instala dependências Node e build do frontend (ajuste se usar yarn)
RUN npm install --legacy-peer-deps && npm run build

# Expõe a porta 80
EXPOSE 80

# Comando padrão para rodar Apache no foreground
CMD ["apache2-foreground"]
