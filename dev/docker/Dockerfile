# Use PHP 8.3 with Apache
FROM php:8.3-apache

# Install system dependencies including FFmpeg, whisper.cpp, and mail dependencies
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
    libffi-dev \
    # Build tools for whisper.cpp
    build-essential \
    cmake \
    pkg-config \
    # Additional whisper.cpp dependencies
    libopenblas-dev \
    liblapack-dev \
    #libatlas-base-dev \
    gfortran \
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
    libfontconfig1-dev \
    libfribidi-dev \
    libharfbuzz-dev \
    # Mail and SSL dependencies
    ca-certificates \
    openssl \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html
RUN git config --global --add safe.directory /var/www/html

# Install whisper.cpp with proper error handling and dependencies
RUN cd /tmp && \
    git clone https://github.com/ggerganov/whisper.cpp.git && \
    cd whisper.cpp && \
    # Checkout a stable version to avoid build issues
    git checkout v1.5.4 && \
    # Try building with different options if the default fails
    (make clean && make -j$(nproc) || \
     (make clean && make -j$(nproc) WHISPER_NO_AVX=1 WHISPER_NO_AVX2=1) || \
     (make clean && make -j$(nproc) WHISPER_NO_AVX=1 WHISPER_NO_AVX2=1 WHISPER_NO_F16C=1) || \
     (make clean && make -j1)) && \
    cp main /usr/local/bin/whisper && \
    cp quantize /usr/local/bin/whisper-quantize && \
    chmod +x /usr/local/bin/whisper /usr/local/bin/whisper-quantize && \
    cd / && rm -rf /tmp/whisper.cpp

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

# Enable Apache modules (headers first for CSP)
RUN a2enmod headers rewrite

# Configure Apache document root to point to public directory
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|<Directory /var/www/>|<Directory /var/www/html/public>|g' /etc/apache2/apache2.conf \
    && echo '<Directory /var/www/html/public>\n\
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
    && a2enconf docker-php \
    && echo 'DocumentRoot /var/www/html/public' >> /etc/apache2/apache2.conf

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

# Security headers with CSP
RUN echo 'Header always set X-Content-Type-Options nosniff' > /etc/apache2/conf-available/secure-basics.conf \
    && echo 'Header always set Referrer-Policy "strict-origin-when-cross-origin"' >> /etc/apache2/conf-available/secure-basics.conf \
    && echo 'Header always set Permissions-Policy "camera=(),microphone=(),geolocation=()"' >> /etc/apache2/conf-available/secure-basics.conf \
    && a2enconf secure-basics

# CSP Report-Only für DEV
RUN echo 'Header always set Content-Security-Policy-Report-Only "default-src '"'"'self'"'"'; script-src '"'"'self'"'"' '"'"'unsafe-inline'"'"'; style-src '"'"'self'"'"' '"'"'unsafe-inline'"'"'; img-src '"'"'self'"'"' data: blob:; font-src '"'"'self'"'"' data:; connect-src '"'"'self'"'"' wss: https://api.openai.com https://api.anthropic.com https://generativelanguage.googleapis.com https://api.thehive.ai https://api.search.brave.com https://graph.search.brave.com https://graph.facebook.com; worker-src '"'"'self'"'"' blob:; media-src '"'"'self'"'"' blob:; manifest-src '"'"'self'"'"'; frame-src '"'"'self'"'"'; object-src '"'"'none'"'"'; base-uri '"'"'self'"'"'; form-action '"'"'self'"'"'; frame-ancestors '"'"'none'"'"'"' > /etc/apache2/conf-available/csp-reportonly.conf \
    && a2enconf csp-reportonly


# Ensure public/up exists with proper permissions at build-time and runtime
RUN install -d -m 2775 -o www-data -g www-data /var/www/html/public/up \
    && printf '#!/bin/bash\nset -e\nmkdir -p /var/www/html/public/up\nchown -R www-data:www-data /var/www/html/public/up || true\nfind /var/www/html/public/up -type d -exec chmod 2775 {} \\; || true\nfind /var/www/html/public/up -type f -exec chmod 664 {} \\; || true\n' > /usr/local/bin/ensure-up-perms.sh \
    && chmod +x /usr/local/bin/ensure-up-perms.sh

# Expose port 80
EXPOSE 80

# Start Apache (ensure /public/up is writable each start)
CMD ["bash","-lc","/usr/local/bin/ensure-up-perms.sh && apache2-foreground"] 