# Use official PHP image
FROM php:8.2-apache

# Copy your PHP files into the container
COPY . /var/www/html/

# Expose port 10000 for Render
EXPOSE 10000

# Start PHP built-in server on port 10000
CMD ["php", "-S", "0.0.0.0:10000", "-t", "/var/www/html"]
