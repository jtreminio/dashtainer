FROM jtreminio/php:7.2

# Custom user and group ID
ARG USER_ID
ARG GROUP_ID
RUN if [ ${USER_ID:-0} -ne 0 ] && [ ${GROUP_ID:-0} -ne 0 ]; then \
  userdel -f www-data &&\
  if getent group www-data ; then groupdel www-data; fi &&\
  groupadd -g ${GROUP_ID} www-data &&\
  useradd -l -u ${USER_ID} -g www-data www-data &&\
  install -d -m 0755 -o www-data -g www-data /home/www-data &&\
  chown --changes --silent --no-dereference --recursive --from=33:33 ${USER_ID}:${GROUP_ID} \
    /home/www-data \
    /.composer \
    /var/run/php-fpm \
    /var/lib/php/sessions \
;fi

USER www-data

WORKDIR /var/www

CMD /usr/bin/php-fpm
