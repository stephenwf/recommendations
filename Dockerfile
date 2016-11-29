FROM php:7.0-fpm

RUN apt-get update && \
    apt-get upgrade -y

RUN apt-get install -y git curl wget zlibc zlib1g zlib1g-dev

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer
RUN curl -sS -L https://github.com/puli/cli/releases/download/1.0.0-beta10/puli.phar -o puli.phar && \
   mv puli.phar /usr/bin/puli && \
   chmod 755  /usr/bin/puli

RUN composer self-update 1.0.0-beta2

RUN echo 'date.timezone = "Europe/London"' >> /usr/local/etc/php/conf.d/php.ini
RUN docker-php-ext-install zip

