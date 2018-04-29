<?php

use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Dotenv\Dotenv;

umask(0000);

if (getenv('APP_ENV') !== 'dev') {
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__.'/../vendor/autoload.php';
Debug::enable();

(new Dotenv())->load(__DIR__.'/../.env');

$kernel = new AppKernel('dev', true);
if (PHP_VERSION_ID < 70000) {
    $kernel->loadClassCache();
}
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
