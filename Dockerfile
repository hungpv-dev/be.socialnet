FROM php:8.1-fpm

ARG user
ARG uid

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:2.5.4 /usr/bin/composer /usr/bin/composer
    
RUN groupadd -g 1000 www
RUN useradd -u $uid -ms /bin/bash -g www-data $user

COPY . /var/www

COPY --chown=$user:www-data . /var/www

USER $user  

EXPOSE 9000

CMD ["php-fpm"]
