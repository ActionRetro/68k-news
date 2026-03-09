FROM php:8.2-fpm

RUN apt update && apt -y install libzip-dev unzip git

RUN pecl install zip

COPY . /68k-news

WORKDIR /68k-news

RUN mkdir -pv cache

RUN chmod +x composer.phar
RUN ./composer.phar i

RUN curl -LO https://github.com/simplepie/simplepie/releases/download/1.8.0/SimplePie.compiled.php && mv SimplePie.compiled.php vendor

EXPOSE 8080

CMD [ "php", "-S", "0.0.0.0:8080", "-t", "./" ]
