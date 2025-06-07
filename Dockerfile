FROM php:8.2-apache

# Copy toàn bộ nội dung thư mục public vào thư mục web của Apache
COPY ./public /var/www/html/

# Mở cổng 80 để web chạy
EXPOSE 80

# Chạy Apache ở foreground (để container không bị tắt)
CMD ["apache2-foreground"]
