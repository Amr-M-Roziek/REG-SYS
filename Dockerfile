FROM php:8.2-apache

# Enable required PHP extensions and Apache modules
RUN docker-php-ext-install mysqli \
  && a2enmod rewrite headers

# Set DocumentRoot to icpm2026
ENV APACHE_DOCUMENT_ROOT=/var/www/html/icpm2026
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf

# Copy source
COPY . /var/www/html

# Recommended PHP settings
RUN { \
  echo 'date.timezone=UTC'; \
  echo 'expose_php=Off'; \
  echo 'session.cookie_httponly=1'; \
  echo 'session.use_strict_mode=1'; \
  echo 'log_errors=On'; \
  echo 'error_log=/proc/self/fd/2'; \
  echo 'display_errors=On'; \
  echo 'session.save_path=/tmp'; \
} > /usr/local/etc/php/conf.d/custom.ini
