dashtainer.com
===========

Common commands:

#### Node install:
    docker container run -it --rm -v $PWD:/var/www -w /var/www -u "$(id -u $USER):$(id -g $USER)" node:9 npm install

#### Run yarn for local dev:
    docker container run -it --rm -v $PWD:/var/www -w /var/www -u "$(id -u $USER):$(id -g $USER)" node:9 yarn run encore dev --watch

#### Run composer install:
    CONTAINER=dashtainer_php_1
    docker container exec -it -w /var/www -u "$(id -u $USER):$(id -g $USER)" ${CONTAINER} composer install
