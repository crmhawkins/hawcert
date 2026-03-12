FROM serversideup/php:8.4-fpm-nginx

# Cambiamos a root para instalar Node.js y tocar configs
USER root
RUN apt-get update && apt-get install -y nodejs npm

# --- ESTA ES LA LÍNEA NUEVA ---
# Desactivamos aio en la configuración de Nginx para evitar el error io_setup
RUN sed -i 's/aio on;/aio off;/g' /etc/nginx/nginx.conf || true
# ------------------------------

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .

RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

USER www-data
