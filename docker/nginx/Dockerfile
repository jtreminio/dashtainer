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

RUN apt-get update \
    && get-gpg-key 0x14AA40EC0831756756D7F66C4F4EA0AAE5267A6C | apt-key add \
    && printf "deb [arch=binary-amd64] http://ppa.launchpad.net/ondrej/nginx/ubuntu bionic main\n" \
>/etc/apt/sources.list.d/ondrej.list \
    && apt-get update \
    && apt-get -y install --no-install-recommends --no-install-suggests \
        nginx ${SYSTEM_PACKAGES} \
    && apt-get -y --purge autoremove \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && rm -rf /usr/lib/x86_64-linux-gnu/perl /usr/share/perl /usr/lib/x86_64-linux-gnu/libperl.* \
    && rm -rf /usr/share/{man,doc} \
    \
    && chown -R www-data:www-data /var/lib/nginx \
    && install -d -m 0755 -o www-data -g www-data /var/cache/nginx \
    && install -d -m 0755 -o www-data -g www-data /var/run/nginx \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stdout /var/log/nginx/_.access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log \
    && ln -sf /dev/stderr /var/log/nginx/_.error.log

WORKDIR /var/www
USER www-data

EXPOSE 8080

CMD ["nginx", "-g", "daemon off;"]
