# syntax=docker/dockerfile:1.6.0

FROM php:8.3-fpm-alpine as php

RUN apk add --update make curl

COPY --from=composer:2.6.2 /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN curl "https://github.com/jolicode/castor/releases/latest/download/castor.linux-amd64.phar" -Lso /usr/local/bin/castor && chmod u+x /usr/local/bin/castor

RUN set -eux; \
    install-php-extensions \
		apcu \
        ast \
		intl \
		opcache \
        openswoole \
        pcntl \
        pcov \
		zip \
    ;

RUN apk add --no-cache bash
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh' | bash
RUN apk add symfony-cli

WORKDIR /flow-live

CMD ["php-fpm"]
