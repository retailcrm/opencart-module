FROM php:7.1-cli

RUN apt-get update

RUN apt-get install -y zlib1g-dev libpq-dev git libicu-dev libxml2-dev libpng-dev libjpeg-dev libmcrypt-dev libxslt-dev libfreetype6-dev \
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
RUN rm /etc/localtime
RUN ln -s /usr/share/zoneinfo/Europe/Moscow /etc/localtime
RUN "date"
