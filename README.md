## Setup Guide

If you wish to run a copy of Dashtainer yourself for local development, the following
steps will get you up and running within minutes.

### Requirements

* [Docker ≥ 18.04](https://docs.docker.com/install/)
* [Docker Compose ≥ 1.21](https://docs.docker.com/compose/install/)
* Clone this repo

### Container init

* Run `./bin/docker-traefik-up` if you do not have another 
    [Traefik](https://traefik.io/) instance running
* Run `./bin/docker-up`

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

### Unit tests

* Run `./bin/phpunit` to run unit tests
