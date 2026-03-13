## ─── Stage: production ────────────────────────────────────────────────────────
FROM php:8.4-fpm-alpine

LABEL maintainer="Mini WMS" version="2.0.0"

# ─── install-php-extensions (handles Alpine/ARM64 reliably) ───────────────────
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/ipe
RUN chmod +x /usr/local/bin/ipe

# ─── System dependencies + PHP extensions ────────────────────────────────────
RUN apk upgrade --no-cache \
    && apk add --no-cache bash curl zip unzip \
    && ipe pdo_mysql opcache redis \
    && rm /usr/local/bin/ipe

# ─── PHP config ────────────────────────────────────────────────────────────────
COPY deploy/docker/php.ini /usr/local/etc/php/conf.d/app.ini

# ─── Non-root user ─────────────────────────────────────────────────────────────
RUN addgroup -g 1000 appgroup \
    && adduser -u 1000 -G appgroup -s /bin/sh -D appuser

# ─── App files ─────────────────────────────────────────────────────────────────
WORKDIR /var/www/html

COPY --chown=appuser:appgroup . .

RUN mkdir -p logs \
    && chmod 775 logs \
    && chown -R appuser:appgroup logs

# ─── Entrypoint ────────────────────────────────────────────────────────────────
COPY deploy/docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

USER appuser

EXPOSE 9000

# ─── Health check (PHP-FPM process) ────────────────────────────────────────────
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD pgrep php-fpm > /dev/null || exit 1

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
