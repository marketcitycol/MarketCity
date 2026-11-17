# Imagen para abrir la versión 1 en cualquier computador con Docker. No es la configuración de producción.
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
RUN npm ci --ignore-scripts
COPY resources ./resources
RUN npm run build

FROM php:8.3-cli-alpine
# La imagen oficial ya trae pdo_sqlite, suficiente para la demo con datos de prueba.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer install --no-dev --optimize-autoloader --no-interaction \
 && cp -n .env.example .env && php artisan key:generate --force \
 && touch database/database.sqlite && php artisan migrate:fresh --seed --force \
 && php artisan storage:link
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
