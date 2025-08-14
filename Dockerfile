ARG PHP_VERSION=latest
FROM php:${PHP_VERSION}
# This is a fix for buster images being moved to the archive
RUN bash -c '[ "$(source /etc/os-release && echo $VERSION_CODENAME)" == "buster" ] && \
    sed -i s/deb.debian.org/archive.debian.org/g /etc/apt/sources.list && \
    sed -i s/security.debian.org/archive.debian.org/g /etc/apt/sources.list || \
    echo "skip"'

RUN apt-get update && apt-get install -y git unzip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /code

COPY composer.json composer.json
RUN composer install

COPY . .
