# Menggunakan image resmi PHP 8.1 dengan Apache
FROM php:8.1-apache

# Install ekstensi MySQL agar PHP bisa terhubung ke database
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Set direktori kerja di dalam kontainer
WORKDIR /var/www/html

# Salin isi folder 'app' lokal ke dalam kontainer
COPY ./app /var/www/html

# Atur hak akses folder
RUN chown -R www-data:www-data /var/www/html

# Buka port 80 untuk web server
EXPOSE 80
