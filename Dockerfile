FROM php:8.3-fpm

# Instala dependências de sistema e bibliotecas para extensões PHP
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev libpq-dev \
    libjpeg-dev libfreetype6-dev libicu-dev libssl-dev \
    nginx supervisor gnupg2 nano \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo pdo_mysql mbstring zip exif pcntl bcmath intl gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copia o Composer do container oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instala Node.js 18 e npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest \
    && npm cache clean --force

# Define o diretório de trabalho
WORKDIR /var/www

# Copia o código da aplicação para dentro do container
COPY . .

# Instala as dependências do Laravel
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# ⚠️ Não faz build do Vite aqui (supondo que já esteja buildado no host)
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

# Copia a configuração do Supervisor (para iniciar php-fpm e nginx juntos)
COPY ./docker/supervisord.conf /etc/supervisord.conf

# Comando de inicialização do container
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
