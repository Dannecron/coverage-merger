ARG PHP_VERSION=8.2.13
ARG COMPOSER_VERSION=2.6.6
ARG MERGER_VERSION=dev-master

FROM composer:${COMPOSER_VERSION} as composer

FROM php:${PHP_VERSION}-cli-alpine as runtime

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.json
RUN composer update --no-dev

COPY merger merger
COPY src src

ENV MERGER_VERSION=${MERGER_VERSION}

ENTRYPOINT ["/app/merger"]
