# Use the official PHP image with Apache
FROM php:8.2-apache

# Install the MySQL extensions required for your database
RUN docker-php-ext-install pdo pdo_mysql

# Change Apache to listen on Render's required port (10000)
RUN sed -ri 's/^Listen 80$/Listen 10000/' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' /etc/apache2/sites-available/000-default.conf

# Copy all your website files into the server's root folder
COPY . /var/www/html/

# Expose port 10000
EXPOSE 10000