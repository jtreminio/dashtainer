FROM blitznote/debase:18.04

ENV APT_KEY_DONT_WARN_ON_DANGEROUS_USAGE=DontWarn

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
ARG SYSTEM_PACKAGES
ENV PHP_VER 7.2
ENV COMPOSER_HOME /.composer

RUN echo "#!/bin/bash" > /usr/bin/php-fpm \
    && echo "/usr/sbin/php-fpm -F -R -O 2>&1 | sed -u 's,.*: \\\"\(.*\)$,\1,'| sed -u 's,\"$,,' 1>&1" \
>> /usr/bin/php-fpm \
    && chmod +x /usr/bin/php-fpm

RUN echo "#!/bin/bash" > /usr/bin/xdebug \
    && echo "XDEBUG_CONFIG=\"idekey=xdebug\" php -dxdebug.remote_enable=1 \"\$@\"" \
>> /usr/bin/xdebug \
    && chmod +x /usr/bin/xdebug

RUN apt-get update \
    && get-gpg-key 0x14AA40EC0831756756D7F66C4F4EA0AAE5267A6C | apt-key add \
    && printf "deb [arch=binary-amd64] http://ppa.launchpad.net/ondrej/php/ubuntu bionic main\n" \
>/etc/apt/sources.list.d/ondrej.list \
    && apt-get update \
    && apt-get -y install --no-install-recommends --no-install-suggests \
        php${PHP_VER}-fpm php${PHP_VER}-cli ${SYSTEM_PACKAGES} \
    && apt-get -y --purge autoremove \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && rm -rf /usr/lib/x86_64-linux-gnu/perl /usr/share/perl /usr/lib/x86_64-linux-gnu/libperl.* \
    && rm -rf /usr/share/{man,doc} \
    \
    && install -d -m 0755 -o www-data -g www-data /var/run/php-fpm \
    && install -d -m 0755 -o www-data -g www-data /var/lib/php/sessions \
    && ln -sf /usr/sbin/php-fpm${PHP_VER} /usr/sbin/php-fpm

ARG PEAR_PACKAGES
RUN if [ ! -z "$PEAR_PACKAGES" ]; then \
    printf "\n" | pear install -o -f ${PEAR_PACKAGES} \
;fi

ARG PECL_PACKAGES
RUN if [ ! -z "$PECL_PACKAGES" ]; then \
    pecl channel-update pecl.php.net \
    && printf "\n" | pecl install -o -f ${PECL_PACKAGES} \
;fi

ARG COMPOSER_INSTALL
RUN if [ ${COMPOSER_INSTALL:-0} -ne 0 ]; then \
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
    && rm -f /tmp/* \
;fi

WORKDIR /var/www
USER www-data

EXPOSE 9000

CMD /usr/bin/php-fpm
