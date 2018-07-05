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
ARG USER_ID=0
ARG GROUP_ID=0
ARG USERNAME=root
RUN if [ $USER_ID -ne 0 ] && [ $GROUP_ID -ne 0 ]; then \
    userdel -f www-data \
    && if getent group www-data ; then groupdel www-data; fi \
    && groupadd -g ${GROUP_ID} www-data \
    && useradd -l -u ${USER_ID} -g www-data www-data \
;fi

# Per-image commands
ARG SYSTEM_PACKAGES
RUN add-apt-repository ppa:ondrej/nginx \
    && apt-get update \
    && apt-get install --no-install-recommends --no-install-suggests -y \
        nginx ${SYSTEM_PACKAGES} \
    && apt-get -y --purge autoremove \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && chown -R www-data:www-data /var/lib/nginx \
    && install -d -m 0755 -o www-data -g www-data /var/cache/nginx \
    && install -d -m 0755 -o www-data -g www-data /var/run/nginx \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stdout /var/log/nginx/_.access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log \
    && ln -sf /dev/stderr /var/log/nginx/_.error.log

WORKDIR /var/www
USER ${USERNAME}

EXPOSE 8080

CMD ["nginx", "-g", "daemon off;"]
