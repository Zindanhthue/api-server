FROM php:8.1-apache

# Cài mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy mã nguồn vào thư mục apache
COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]
