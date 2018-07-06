FROM ubuntu:16.04

ENV DEBIAN_FRONTEND noninteractive
ENV LANG C.UTF-8
ENV LANGUAGE C.UTF-8
ENV LC_ALL C.UTF-8

RUN apt-get update \
    && apt-get install --no-install-recommends --no-install-suggests -y \
        apt-utils curl locales software-properties-common python-software-properties \
    && locale-gen en_US.UTF-8 \
    && /usr/sbin/update-locale \
    && apt-key adv --keyserver keyserver.ubuntu.com --recv-keys \
        14AA40EC0831756756D7F66C4F4EA0AAE5267A6C \
    && apt-get update

# Custom user and group ID
ARG USER_ID
ARG GROUP_ID
RUN if [ ${USER_ID:-0} -ne 0 ] && [ ${GROUP_ID:-0} -ne 0 ]; then \
    userdel -f www-data \
    && if getent group www-data ; then groupdel www-data; fi \
    && groupadd -g ${GROUP_ID} www-data \
    && useradd -l -u ${USER_ID} -g www-data www-data \
;fi

# Per-image commands
ENV PHP_VER 7.2
ENV COMPOSER_HOME /.composer

ARG SYSTEM_PACKAGES
RUN add-apt-repository ppa:ondrej/php \
    && apt-get update \
    && apt-get install --no-install-recommends --no-install-suggests -y \
        php${PHP_VER}-fpm php${PHP_VER}-cli ${SYSTEM_PACKAGES} \
    && apt-get -y --purge autoremove \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

ARG PEAR_PACKAGES
RUN if [ ! -z "$PEAR_PACKAGES" ]; then \
    printf "\n" | pear install -o -f ${PEAR_PACKAGES} \
;fi

ARG PECL_PACKAGES
RUN if [ ! -z "$PECL_PACKAGES" ]; then \
    pecl channel-update pecl.php.net \
    && printf "\n" | pecl install -o -f ${PECL_PACKAGES} \
;fi

ARG COMPOSER_INSTALL=0
RUN if [ $COMPOSER_INSTALL -ne 0 ]; then \
    install -d -m 0755 -o www-data -g www-data /.composer \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin \
        --filename=composer \
;fi

ARG BLACKFIRE_SERVER
RUN if [ ! -z "$BLACKFIRE_SERVER" ]; then \
    curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s \
        https://blackfire.io/api/v1/releases/probe/php/linux/amd64/${PHP_VER} \
    && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp \
    && mv /tmp/blackfire-*.so $(php -r "echo ini_get('extension_dir');")/blackfire.so \
    && printf "extension=blackfire.so\nblackfire.agent_socket=tcp://${BLACKFIRE_SERVER}:8707\n" > \
        /etc/php/${PHP_VER}/cli/conf.d/blackfire.ini \
    && printf "extension=blackfire.so\nblackfire.agent_socket=tcp://${BLACKFIRE_SERVER}:8707\n" > \
        /etc/php/${PHP_VER}/fpm/conf.d/blackfire.ini \
;fi

COPY xdebug-bin /usr/bin/xdebug
COPY fpm-bin /usr/bin/php-fpm

RUN install -d -m 0755 -o www-data -g www-data /var/run/php-fpm \
    && install -d -m 0755 -o www-data -g www-data /var/lib/php/sessions \
    && chmod +x /usr/bin/xdebug \
    && chmod +x /usr/bin/php-fpm \
    && ln -sf /usr/sbin/php-fpm${PHP_VER} /usr/sbin/php-fpm

WORKDIR /var/www
USER www-data

EXPOSE 9000

CMD /usr/bin/php-fpm
