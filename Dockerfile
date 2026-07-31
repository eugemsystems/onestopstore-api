FROM php:8.5-fpm

# Set working directory inside the container
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git \
    libpq-dev \
    postgresql-client \
    libfreetype6-dev \
    libzip-dev \
    libmagickwand-dev \
    imagemagick \
    supervisor \
    python3 \
    python3-pip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install rembg for AI background removal + pre-download the u2net model (~176MB)
ENV NUMBA_CACHE_DIR=/tmp/numba_cache
ENV U2NET_HOME=/tmp/.u2net
RUN pip3 install --break-system-packages "rembg[cpu]" \
    && mkdir -p /tmp/.u2net /tmp/numba_cache \
    && python3 -c "import os; os.environ['NUMBA_CACHE_DIR']='/tmp/numba_cache'; os.environ['U2NET_HOME']='/tmp/.u2net'; from PIL import Image; import io; from rembg import remove; img=Image.new('RGB',(10,10),'white'); buf=io.BytesIO(); img.save(buf,'PNG'); remove(buf.getvalue()); print('rembg model ready')" \
    && chmod -R 777 /tmp/.u2net /tmp/numba_cache

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl posix bcmath gd zip

# Install Redis extension
RUN pecl install redis \
 && docker-php-ext-enable redis

# Install ImageMagick extension
RUN pecl install imagick \
 && docker-php-ext-enable imagick

# Install Composer (latest version)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy the supervisor config file
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy application code
COPY . .

RUN git config --global --add safe.directory /var/www/html

# Install Laravel dependencies (production only)
# RUN composer install --no-dev --optimize-autoloader
# --no-scripts: skip post-autoload-dump's `artisan package:discover`, which boots
# the app and (without a .env present at build time) falls back to sqlite and
# fails when a provider eagerly queries the DB. Not needed at build time anyway —
# the bind-mounted host vendor/bootstrap/cache take over at container runtime.
RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=php+ --no-scripts

# Set permissions for Laravel (storage and cache)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# --- FIX php-fpm log crash ---
# Override default php-fpm error_log path
RUN sed -i 's|^error_log =.*|error_log = /var/log/php-fpm/error.log|' /usr/local/etc/php-fpm.conf \
 && sed -i 's|^;php_admin_value\[error_log\].*|php_admin_value[error_log] = /var/log/php-fpm/fpm-php.www.log|' /usr/local/etc/php-fpm.d/www.conf

# --- FIX php-fpm slowlog crash ---
# Create log directory and slowlog file
RUN mkdir -p /var/log/php-fpm \
    && touch /var/log/php-fpm/error.log \
    && touch /var/log/php-fpm/slow.log \
    && chown -R www-data:www-data /var/log/php-fpm

# --- OVERRIDE php-fpm pool config with our tuned www.conf ---
COPY conf/www.conf /usr/local/etc/php-fpm.d/www.conf

# --- COPY custom php.ini for upload limits and performance ---
COPY conf/custom-php.ini /usr/local/etc/php/conf.d/custom-php.ini

# Expose PHP-FPM port (used by Nginx)
EXPOSE 9000

# Start PHP-FPM directly (no Supervisor)
# CMD ["php-fpm", "--nodaemonize"]
# Start Supervisor (which will handle both php-fpm and horizon)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
