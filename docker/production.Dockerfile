# syntax=docker/dockerfile:1.7

FROM node:24-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json vite.config.js ./
RUN npm ci

COPY resources ./resources
RUN npm run build

FROM php:8.3-fpm AS php-base

ARG PHP_INI=production.ini

RUN apt-get update \
  && apt-get install -y --no-install-recommends libonig-dev \
  && docker-php-ext-install -j$(nproc) pdo_mysql mbstring \
  && rm -rf /var/lib/apt/lists/*

COPY docker/${PHP_INI} /usr/local/etc/php/conf.d/wps-micro.ini

WORKDIR /var/www/wps-micro

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
  --no-dev \
  --prefer-dist \
  --no-interaction \
  --no-progress \
  --optimize-autoloader

FROM php-base AS fpm

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p storage/cache/twig storage/logs \
  && chown -R www-data:www-data storage

EXPOSE 9000

CMD ["php-fpm"]

FROM nginx:alpine AS nginx

COPY docker/conf/vhost.production.conf /etc/nginx/conf.d/default.conf
COPY --from=fpm /var/www/wps-micro/public /var/www/wps-micro/public

WORKDIR /var/www/wps-micro
