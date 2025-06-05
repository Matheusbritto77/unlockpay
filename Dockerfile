FROM php:8.3-fpm

# Instala pacotes necessários do sistema e extensões PHP
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev libpq-dev \
    libjpeg-dev libfreetype6-dev libicu-dev libssl-dev \
    nginx supervisor gnupg2 nano \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo pdo_mysql mbstring zip exif pcntl bcmath intl gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copia o Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instala Node.js 18 e npm mais recente
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest \
    && npm cache clean --force

# Define o diretório de trabalho
WORKDIR /var/www

# Copia o projeto Laravel
COPY . .

# Instala dependências PHP (Laravel)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# ⚠️ NÃO executa build do Vite aqui, pois o `public/` já contém o build
# Opcional: apenas garante permissões e Vite executável (se necessário para outros scripts)
RUN chmod +x node_modules/.bin/vite || true

# Ajusta permissões
RUN chown -R www-data:www-data /var/www \
    && find /var/www -type f -exec chmod 644 {} \; \
    && find /var/www -type d -exec chmod 755 {} \;

# Expondo porta do Nginx
EXPOSE 80

# Copia configuração do Supervisor (que inicia php-fpm + nginx)
COPY ./docker/supervisord.conf /etc/supervisord.conf

# Define comando de inicialização
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
