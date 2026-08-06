FROM php:8.2-cli

# Install system dependencies and PHP extensions required by Laravel
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Install PHP and Node dependencies & build frontend assets
RUN composer install --no-dev --optimize-autoloader \
    && npm install -g pnpm@9 \
    && pnpm install \
    && pnpm run build

# Expose default port
EXPOSE 10000

# Start Laravel application server
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
