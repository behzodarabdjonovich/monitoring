FROM php:8.4-cli

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*
    
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN printf "upload_max_filesize=10M\npost_max_size=12M\n" > /usr/local/etc/php/conf.d/uploads.ini

RUN composer dump-autoload --no-dev --optimize

RUN mkdir -p storage storage/backups storage/uploads \
    && chmod -R 775 storage

ENV APP_ENV=production
ENV DB_DRIVER=sqlite
ENV DB_DATABASE=/tmp/database.sqlite

EXPOSE 10000

CMD php bin/console migrate && \
    php bin/console seed && \
    php -S 0.0.0.0:${PORT:-10000} -t public public/index.php
