FROM php:7.1-apache

RUN apt-get update

RUN apt-get install -y netcat zlib1g-dev libpq-dev git libicu-dev libxml2-dev libpng-dev libjpeg-dev libmcrypt-dev libxslt-dev libfreetype6-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && docker-php-ext-install zip \
    && docker-php-ext-install xml \
    && docker-php-ext-configure gd --with-png-dir=/usr/local/ --with-jpeg-dir=/usr/local/ --with-freetype-dir=/usr/local/ \
    && docker-php-ext-install gd \
    && docker-php-ext-install mcrypt \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install soap \
    && docker-php-ext-install xsl \
    && docker-php-ext-install mbstring

RUN apt-get install -y wget

RUN wget -O /usr/bin/phpunit https://phar.phpunit.de/phpunit-7.phar && chmod +x /usr/bin/phpunit
RUN curl --insecure https://getcomposer.org/download/1.9.3/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer

# Set timezone
RUN rm /etc/localtime && \
    ln -s /usr/share/zoneinfo/Europe/Moscow /etc/localtime && \
    "date"

ARG TEST_SUITE
ARG OPENCART
ARG SERVER_PORT
ARG OC_DB_HOSTNAME
ARG OC_DB_USERNAME
ARG OC_DB_PASSWORD
ARG OC_DB_DATABASE
ARG OC_USERNAME
ARG OC_PASSWORD
ARG OC_EMAIL

ENV TEST_SUITE=${TEST_SUITE}
ENV OPENCART=${OPENCART}
ENV PORT=${SERVER_PORT}
ENV OC_DB_HOSTNAME=${OC_DB_HOSTNAME}
ENV OC_DB_USERNAME=${OC_DB_USERNAME}
ENV OC_DB_PASSWORD=${OC_DB_PASSWORD}
ENV OC_DB_DATABASE=${OC_DB_DATABASE}
ENV OC_USERNAME=${OC_USERNAME}
ENV OC_PASSWORD=${OC_PASSWORD}
ENV OC_EMAIL=${OC_EMAIL}

ADD .docker/entrypoint.sh /usr/local/bin/docker-php-entrypoint

RUN chmod +x /usr/local/bin/docker-php-entrypoint

RUN sed -i "s/80/$PORT/g" /etc/apache2/sites-enabled/000-default.conf /etc/apache2/ports.conf && \
    sed -i 's/var\/www\/html/opencart-module\/www/g' /etc/apache2/sites-enabled/000-default.conf && \
    sed -i 's/var\/www/opencart-module/g' /etc/apache2/apache2.conf
