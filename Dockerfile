FROM php:7.1-apache

RUN apt-get update && apt-get install -y git libpq-dev libmcrypt-dev zlib1g-dev libicu-dev g++ graphviz && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo_pgsql pdo_mysql mbstring mcrypt zip sockets intl bcmath

RUN curl -o /usr/local/bin/composer https://getcomposer.org/composer.phar && \
	chmod +x /usr/local/bin/composer

RUN pecl install xdebug-stable ast

RUN echo "zend_extension=/usr/local/lib/php/extensions/no-debug-non-zts-20160303/xdebug.so" > /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.default_enable = 1" >> /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.remote_enable = 1" >> /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.remote_handler = dbgp" >> /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.remote_autostart = 0" >> /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.remote_connect_back = 1" >> /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.remote_port = 9000" >> /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.remote_host = 172.17.42.1" >> /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.profiler_enable=0" >> /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.profiler_enable_trigger=1" >> /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.profiler_output_dir=\"/tmp\"" >> /usr/local/etc/php/conf.d/xdebug.ini

RUN echo "zend_extension=/usr/local/lib/php/extensions/no-debug-non-zts-20160303/opcache.so" > /usr/local/etc/php/conf.d/opcache.ini

RUN echo "extension=ast.so" > /usr/local/etc/php/conf.d/ast.ini

RUN echo "realpath_cache_size=4096k" > /usr/local/etc/php/conf.d/tuning.ini && \
    echo "realpath_cache_ttl=300" >> /usr/local/etc/php/conf.d/tuning.ini

RUN echo "date.timezone = \"UTC\"" >> /usr/local/etc/php/conf.d/timezone.ini

RUN a2enmod rewrite

EXPOSE 80