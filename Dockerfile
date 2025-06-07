FROM php:8.1-apache

# Copy tất cả file trong repo vào thư mục web của apache
COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]
