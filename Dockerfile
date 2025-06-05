FROM php:8.3-fpm

# Instala dependências de sistema e bibliotecas para extensões PHP
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev libpq-dev \
    libjpeg-dev libfreetype6-dev libicu-dev libssl-dev gnupg2 \
    nginx supervisor nano \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo pdo_mysql mbstring zip exif pcntl bcmath intl gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copia o Composer do container oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instala Node.js 20.x (compatível com npm@11.x+)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm cache clean --force

# Define o diretório de trabalho
WORKDIR /var/www

# Copia o código da aplicação para dentro do container
COPY . .

# Instala as dependências do Laravel (sem dev se for produção)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Evita erro no build do Vite se não estiver presente
RUN chmod +x node_modules/.bin/vite || true

# Cria diretórios obrigatórios do Laravel se não existirem
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Ajusta permissões de segurança
RUN chown -R www-data:www-data /var/www \
    && find /var/www -type f -exec chmod 644 {} \; \
    && find /var/www -type d -exec chmod 755 {} \; \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

# Expõe a porta padrão do Nginx
EXPOSE 80

# Copia a configuração do Supervisor
COPY ./docker/supervisord.conf /etc/supervisord.conf

# Comando de inicialização do container
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
