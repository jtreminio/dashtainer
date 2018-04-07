<?php

namespace Dashtainer\Migrations;

use Doctrine\DBAL\Schema\Schema;

class Version1_0_2 extends FixtureMigrationAbstract
{
    public function up(Schema $schema)
    {
        $serializer = $this->container->get('serializer');

        $data = <<<'EOD'
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
ENV NGINX_PREFIX /etc/nginx
ARG SYSTEM_PACKAGES
WORKDIR $NGINX_PREFIX

RUN add-apt-repository ppa:ondrej/nginx \
    && apt-get update \
    && apt-get install --no-install-recommends --no-install-suggests -y \
        nginx ${SYSTEM_PACKAGES} \
    && apt-get -y --purge autoremove \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stdout /var/log/nginx/_.access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log \
    && ln -sf /dev/stderr /var/log/nginx/_.error.log

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]

EOD;

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            JOIN docker_service_type dst ON dstm.service_type_id = dst.id
            SET dstm.data = :data
            WHERE dstm.name = :name
              AND dst.name = "Nginx"
            LIMIT 1
        ', [
            ':data' => json_encode([$data]),
            ':name' => 'Dockerfile',
        ]);

        $data = <<<'EOD'
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
ENV HTTPD_PREFIX /etc/apache2
ARG SYSTEM_PACKAGES
WORKDIR $HTTPD_PREFIX

RUN add-apt-repository ppa:ondrej/apache2 \
    && apt-get update \
    && apt-get install --no-install-recommends --no-install-suggests -y \
        apache2 ${SYSTEM_PACKAGES} \
    && apt-get -y --purge autoremove \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
        conf-enabled/serve-cgi-bin.conf \
        mods-enabled/autoindex.conf \
        mods-enabled/autoindex.load \
    && echo "ServerName localhost" >> apache2.conf \
    && ln -sf /dev/stdout /var/log/apache2/access.log \
    && ln -sf /dev/stdout /var/log/apache2/vhost-access.log \
    && ln -sf /dev/stderr /var/log/apache2/error.log \
    && ln -sf /dev/stderr /var/log/apache2/vhost-error.log

EXPOSE 80

ARG APACHE_MODULES_DISABLE
RUN if [ ! -z "$APACHE_MODULES_DISABLE" ]; then \
    a2dismod $APACHE_MODULES_DISABLE \
;fi

ARG APACHE_MODULES_ENABLE
RUN if [ ! -z "$APACHE_MODULES_ENABLE" ]; then \
    a2enmod $APACHE_MODULES_ENABLE \
;fi

CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]

EOD;

        $this->addSql('
            UPDATE docker_service_type_meta dstm
            JOIN docker_service_type dst ON dstm.service_type_id = dst.id
            SET dstm.data = :data
            WHERE dstm.name = :name
              AND dst.name = "Apache"
            LIMIT 1
        ', [
            ':data' => json_encode([$data]),
            ':name' => 'Dockerfile',
        ]);
    }

    public function down(Schema $schema)
    {
    }

    public function postUp(Schema $schema)
    {
    }
}
