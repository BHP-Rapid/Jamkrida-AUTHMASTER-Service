FROM php:8.2-fpm

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip unzip curl git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# ===== PHP Upload Configuration =====
RUN echo "max_file_uploads=50" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "upload_max_filesize=50M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=50M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit=-1" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "max_execution_time=300" > /usr/local/etc/php/conf.d/execution-time.ini \
    && echo "request_terminate_timeout=300" >> /usr/local/etc/php/conf.d/execution-time.ini

# Install Composer (copy dari official image)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy seluruh kode aplikasi
COPY . .

# Install dependencies Laravel
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www

EXPOSE 9000
CMD ["php-fpm"]
