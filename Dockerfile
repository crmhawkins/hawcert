FROM serversideup/php:8.4-fpm-nginx

# Cambiamos a root para instalar Node.js
USER root
RUN apt-get update && apt-get install -y nodejs npm

# Directorio de trabajo
WORKDIR /var/www/html

# Copiamos el código
COPY --chown=www-data:www-data . .

# Instalamos dependencias de PHP
RUN composer install --no-dev --optimize-autoloader

# Instalamos dependencias de Node y compilamos con Vite
RUN npm install && npm run build

# Volvemos al usuario por defecto de la imagen
USER www-data
