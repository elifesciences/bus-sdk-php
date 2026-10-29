ARG PHP_VERSION=latest
FROM php:${PHP_VERSION}

# Debian's live mirrors drop packages for EOL point releases (this has
# broken both buster and bullseye-based php images so far), so fall back to
# the snapshot.debian.org mirror already pinned (commented out) in the base
# image's sources.list, when that file exists. Harmless no-op on codenames
# still on live mirrors (e.g. trixie has no sources.list at all - it moved
# to the deb822 sources.list.d format instead).
RUN if [ -f /etc/apt/sources.list ]; then \
        sed -i \
            -e 's|^# \(deb http://snapshot.debian.org.*\)|\1|' \
            -e 's|^deb http://deb.debian.org.*|# &|' \
            -e 's|^deb http://security.debian.org.*|# &|' \
            /etc/apt/sources.list; \
    fi

# Snapshot archives are pinned to a past date, so their Release file's
# Valid-Until has already passed - Check-Valid-Until=false stops apt
# rejecting them as stale. No effect on live (non-snapshot) mirrors.
RUN apt-get update -o Acquire::Check-Valid-Until=false && apt-get install -y git unzip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

WORKDIR /code

COPY composer.json composer.json
RUN composer install

COPY . .
