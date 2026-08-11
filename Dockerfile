FROM php:8.2-apache

# Habilitar mod_rewrite si es necesario
RUN a2enmod rewrite

# Copiar los archivos de la aplicación al contenedor
COPY . /var/www/html/

# Dar permisos adecuados
RUN chown -www-data:www-data /var/www/html -R

EXPOSE 80