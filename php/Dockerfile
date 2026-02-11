FROM php:8.2-fpm-alpine
# Install system dependencies
RUN apk add --no-cache \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    libxml2-dev \
    oniguruma-dev \
    sqlite-dev
# Install PHP extensions required by CakePHP 5.0
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    pdo_sqlite \
    mbstring \
    intl \
    xml \
    simplexml \
    zip \
    gd
# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
# Set working directory
WORKDIR /var/www/html
# Expose PHP-FPM port
EXPOSE 9000
CMD ["php-fpm"]