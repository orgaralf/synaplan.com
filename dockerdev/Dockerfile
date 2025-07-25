# Use PHP 8.3 with Apache
FROM php:8.3-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies including FFmpeg
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libsodium-dev \
    zip \
    unzip \
    nodejs \
    npm \
    libffi-dev \
    # FFmpeg and multimedia dependencies
    ffmpeg \
    libavcodec-extra \
    libavformat-dev \
    libavutil-dev \
    libswscale-dev \
    libavfilter-dev \
    libavdevice-dev \
    libpostproc-dev \
    libswresample-dev \
    libx264-dev \
    libx265-dev \
    libvpx-dev \
    libmp3lame-dev \
    libopus-dev \
    libvorbis-dev \
    libass-dev \
    libfreetype6-dev \
    libfontconfig1-dev \
    libfribidi-dev \
    libharfbuzz-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        sodium \
        ffi

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files first for better caching
COPY web/composer.json web/composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy web directory contents to the document root
COPY web/ ./

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Enable Apache modules
RUN a2enmod rewrite

# Configure Apache for PHP
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n\
\n\
<FilesMatch \.php$>\n\
    SetHandler application/x-httpd-php\n\
</FilesMatch>\n\
\n\
DirectoryIndex index.php index.html' > /etc/apache2/conf-available/docker-php.conf \
    && a2enconf docker-php

# Block access to sensitive files
RUN echo '<FilesMatch "^\.">\n\
    Require all denied\n\
</FilesMatch>\n\
\n\
<FilesMatch "\.(env|htaccess|htpasswd|ini|log|sh|sql|conf|bak|backup|swp|swo|tmp|temp)$">\n\
    Require all denied\n\
</FilesMatch>\n\
\n\
<FilesMatch "(composer\.(json|lock)|package\.json|package-lock\.json|yarn\.lock|README\.md|CHANGELOG\.md|LICENSE|\.gitignore)$">\n\
    Require all denied\n\
</FilesMatch>\n\
\n\
<FilesMatch "(Dockerfile|docker-compose\.yml|\.dockerignore)$">\n\
    Require all denied\n\
</FilesMatch>\n\
\n\
<FilesMatch "(\.(git|svn|hg|bzr|cvs))">\n\
    Require all denied\n\
</FilesMatch>' > /etc/apache2/conf-available/security-headers.conf \
    && a2enconf security-headers

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"] 