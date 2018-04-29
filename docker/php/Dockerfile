FROM ubuntu:16.04

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && apt-get install -y apt-utils

# Install common / shared packages
RUN apt-get install -y \
    curl \
    locales \
    software-properties-common \
    python-software-properties

# Set up locales
RUN locale-gen en_US.UTF-8
ENV LANG C.UTF-8
ENV LANGUAGE C.UTF-8
ENV LC_ALL C.UTF-8
RUN /usr/sbin/update-locale

RUN apt-key adv --keyserver keyserver.ubuntu.com --recv-keys \
        14AA40EC0831756756D7F66C4F4EA0AAE5267A6C \
    && apt-get update

# Per-image commands
ENV PHP_DIR /etc/php/7.2
ENV PHP_FPM php7.2-fpm
ARG SYSTEM_PACKAGES
ARG PHP_PACKAGES
WORKDIR $PHP_DIR

RUN add-apt-repository ppa:ondrej/php \
    && apt-get update \
    && apt-get install --no-install-recommends --no-install-suggests -y \
        ${PHP_FPM} ${SYSTEM_PACKAGES} ${PHP_PACKAGES} \
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
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin \
        --filename=composer \
    && mkdir /.composer && chmod 1777 /.composer \
;fi

ARG BLACKFIRE_INSTALL=0
RUN if [ $BLACKFIRE_INSTALL -ne 0 ]; then \
    version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
    && curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s \
        https://blackfire.io/api/v1/releases/probe/php/linux/amd64/$version \
    && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp \
    && mv /tmp/blackfire-*.so $(php -r "echo ini_get('extension_dir');")/blackfire.so \
    && printf "extension=blackfire.so\nblackfire.agent_socket=tcp://blackfire:8707\n" > \
        ${PHP_DIR}/cli/conf.d/blackfire.ini \
    && printf "extension=blackfire.so\nblackfire.agent_socket=tcp://blackfire:8707\n" > \
        ${PHP_DIR}/fpm/conf.d/blackfire.ini \
;fi

COPY xdebug /usr/bin/xdebug
RUN chmod +x /usr/bin/xdebug

EXPOSE 9000

COPY php-fpm-startup /usr/bin/php-fpm
RUN chmod +x /usr/bin/php-fpm \
    && ln -sf /usr/sbin/php-fpm7.2 /usr/sbin/php-fpm

CMD /usr/bin/php-fpm
