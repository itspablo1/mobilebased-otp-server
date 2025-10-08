# Use PHP with Apache
FROM php:8.2-apache

# Copy all files to web directory
COPY . /var/www/html/

# Install required dependencies for PHPMailer
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html/

# Expose port 80
EXPOSE 80

# Start Apache server
CMD ["apache2-foreground"]
