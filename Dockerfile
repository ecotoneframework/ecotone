FROM debian:stable


RUN echo "deb http://ftp.us.debian.org/debian testing main contrib non-free" >> /etc/apt/sources.list \
    && apt-get update \
    && apt-get install -yqq curl \
    && apt-get install -yqq --no-install-recommends php7.1 php7.1-mbstring php7.1-bcmath php7.1-xml \
    && rm -rf /var/lib/apt/lists/*

RUN curl -o /usr/local/bin/composer https://getcomposer.org/composer.phar && \
	chmod +x /usr/local/bin/composer

RUN apt-get update && apt-get install -yqq --no-install-recommends php7.1-dev build-essential \
    && curl -fsSL 'https://xdebug.org/files/xdebug-2.5.1.tgz' -o xdebug.tar.gz \
    && mkdir -p xdebug \
    && tar -xf xdebug.tar.gz -C xdebug --strip-components=1 \
    && rm xdebug.tar.gz \
    && ( \
    cd xdebug \
    && phpize \
    && ./configure --enable-xdebug \
    && make -j$(nproc) \
    && make install \
    ) \
    && rm -r xdebug \
    && apt-get purge php7.1-dev build-essential -yqq

RUN echo "zend_extension=xdebug.so" > /etc/php/7.1/mods-available/xdebug.ini && \
    echo "xdebug.default_enable = 1" >> /etc/php/7.1/mods-available/xdebug.ini && \
    echo "xdebug.remote_enable = 1" >> /etc/php/7.1/mods-available/xdebug.ini && \
    echo "xdebug.remote_handler = dbgp" >> /etc/php/7.1/mods-available/xdebug.ini && \
    echo "xdebug.remote_autostart = 0" >> /etc/php/7.1/mods-available/xdebug.ini && \
    echo "xdebug.remote_connect_back = 1" >> /etc/php/7.1/mods-available/xdebug.ini && \
    echo "xdebug.remote_port = 9000" >> /etc/php/7.1/mods-available/xdebug.ini && \
    echo "xdebug.remote_host = 172.17.42.1" >> /etc/php/7.1/mods-available/xdebug.ini && \
    echo "xdebug.profiler_enable=0" >> /etc/php/7.1/mods-available/xdebug.ini && \
    echo "xdebug.profiler_enable_trigger=1" >> /etc/php/7.1/mods-available/xdebug.ini && \
    echo "xdebug.profiler_output_dir=\"/tmp\"" >> /etc/php/7.1/mods-available/xdebug.ini

RUN phpenmod xdebug