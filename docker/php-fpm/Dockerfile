FROM php:8.4-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    wget \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    librdkafka-dev \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    sockets \
    bcmath \
    pdo pdo_pgsql \
    zip \
    intl \
    opcache \
    pcntl

RUN pecl install redis rdkafka \
    && docker-php-ext-enable redis rdkafka

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

USER www

EXPOSE 9000

CMD ["php-fpm"]
