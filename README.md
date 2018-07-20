## Setup Guide

If you wish to run a copy of Dashtainer yourself for local development, the following
steps will get you up and running within minutes.

### Requirements

* [Docker ≥ 18.04](https://docs.docker.com/install/)
* [Docker Compose ≥ 1.21](https://docs.docker.com/compose/install/)
* Clone this repo

### User and Group ID

Docker recommends not running container as root.

For development purposes, running containers as root means any files created by the container
will show as owned by root on your host. Things like `composer install` or similar will be
root owned and makes deleting them a pain.

Included is a helper script to generate a `.env` file that Composer will read and apply. All
it does is pass along your user and group ID to Docker so it can create a user with the same
values. Any files then created by the container will show as owned by your current user.

* Run `./bin/init` to generate the `.env` file

If you would rather the containers run as root, simply copy the included `.env.dist` to `.env`.

### Container init

* Run `./bin/init`

### App configuration

* Run `./bin/php composer install` to install Composer requirements
* Run `./bin/php ./bin/console dashtainer:db:create` to create and seed database
* Run `./bin/node npm install` to install NPM dependencies
* Run `./bin/webpack` to begin the 
    [Encore](https://symfony.com/doc/3.4/frontend.html) daemon

### Open the app in browser

* Open [dashtainer.localhost/app_dev.php](http://dashtainer.localhost/app_dev.php) in Chrome
    * If you do not use Chrome, you must first configure dnsmasq to forward all
        `*.localhost` requests to 127.0.0.1. Or just use Chrome.

The default user credentials are:

* User: `test@dashtainer.com`
* Password: `test123`

### Database credentials

Open [adminer.dashtainer.localhost](http://adminer.dashtainer.localhost) in Chrome to
use Adminer with the following credentials:

* Host/Server: `mariadb`
* Post: `3306`
* Database Name: `dashtainer`
* Database User: `dashtainer`
* Database Password: `dashtainer`

You may also access the database using Sequel Pro or any other MySQL-compatible GUI with:

* Host/Server: `localhost`
* Post: `3600`
* Database Name: `dashtainer`
* Database User: `dashtainer`
* Database Password: `dashtainer`

### Xdebug

Two PHP containers are created, `php` and `php_xdebug`. While Xdebug is installed on both
containers, only `php_xdebug` has it activated by default.

To trigger Xdebug, you must set a cookie, `XDEBUG_SESSION`, to `xdebug` value.

Use the [PhpStorm Bookmarklets](https://www.jetbrains.com/phpstorm/marklets/)
generator to create your Start and Stop bookmarks. The IDE key should be `xdebug`.

### Linux users
If spinning up Docker on a Linux host, the `xdebug.remote_host` value in PHP INI
use `host.docker.internal` which does not currently work on Linux hosts (only Windows and MacOS for now).

To fix this, simply copy the
[docker-compose.override.yml.dist](https://github.com/jtreminio/dashtainer/blob/master/docker/docker-compose.override.yml.dist)
file to `docker-compose.override.yml`.

### Unit tests

* Run `./bin/php ./vendor/bin/phpunit` to run unit tests
