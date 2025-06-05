FROM php:8.3-fpm

# Instala dependências do sistema e bibliotecas para extensões PHP
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev libpq-dev \
    libjpeg-dev libfreetype6-dev libicu-dev libssl-dev gnupg2 \
    nginx supervisor nano \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo pdo_mysql mbstring zip exif pcntl bcmath intl gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copia o Composer oficial para o container
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instala Node.js 20.x e limpa cache do npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm cache clean --force

# Define o diretório de trabalho da aplicação
WORKDIR /var/www

# Copia o código da aplicação para dentro do container
COPY . .

# Instala dependências PHP do Laravel (sem pacotes de desenvolvimento)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Ajusta permissão para evitar erro caso node_modules/.bin/vite não exista
RUN chmod +x node_modules/.bin/vite || true

# Cria diretórios obrigatórios do Laravel
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Ajusta permissões de forma segura para www-data
RUN chown -R www-data:www-data /var/www \
    && find /var/www -type f -exec chmod 644 {} \; \
    && find /var/www -type d -exec chmod 755 {} \; \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

# Substitui a configuração padrão do Nginx pela personalizada
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf

# Copia a configuração do Supervisor para gerenciar PHP-FPM e Nginx
COPY ./docker/supervisord.conf /etc/supervisord.conf

# Expõe a porta 80 para acesso HTTP
EXPOSE 80

# Comando para iniciar supervisor, que gerencia PHP-FPM e Nginx juntos
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
