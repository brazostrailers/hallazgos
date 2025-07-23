FROM php:8.1-apache

# Instalar extensiones de PHP necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Instalar extensiones adicionales para manejo de archivos
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Configurar directorio de trabajo
ENV APACHE_DOCUMENT_ROOT=/var/www/html
WORKDIR /var/www/html

# Crear directorio de uploads con permisos
RUN mkdir -p /var/www/html/uploads && chmod 755 /var/www/html/uploads

# Configurar PHP para uploads
RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

# Copiar configuración de Apache (opcional)
# COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
