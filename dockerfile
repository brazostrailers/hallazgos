FROM php:8.1-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/html

WORKDIR /var/www/html
