FROM node:22-alpine AS frontend
WORKDIR /src
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM php:8.4-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libzip-dev libonig-dev libxml2-dev unzip git \
    && docker-php-ext-install pdo_pgsql mbstring bcmath zip pcntl dom xml xmlreader xmlwriter \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .
COPY --from=frontend /src/public/build ./public/build
COPY docker/php.ini /usr/local/etc/php/conf.d/puro.ini
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/entrypoint.sh
USER www-data
EXPOSE 8000
ENTRYPOINT ["/app/docker/entrypoint.sh"]
CMD ["php","artisan","serve","--host=0.0.0.0","--port=8000","--no-reload"]
