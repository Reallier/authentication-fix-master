<?php

use Illuminate\Routing\Router;
use Example\Addons\Authentication\Controllers\CertAuthController;
use Example\Addons\Authentication\Controllers\InfoController;
use Example\Addons\Authentication\Controllers\PhoneAuthController;

/** @var $router Router */

$router->controller(InfoController::class)->group(function (Router $router) {
    $router->get('/status', 'currentStatus');
});

$router->controller(CertAuthController::class)->group(function (Router $router) {
    $router->post('/certauth/submit', 'submit');
    $router->post('/certauth/check', 'check');
//    $router->get('/certauth/status', 'status');
    
});$router->controller(PhoneAuthController::class)->group(function (Router $router) {
    $router->post('/phoneauth/sendcode', 'sendCode');
    $router->post('/phoneauth/check', 'check');
    $router->get('/phoneauth/status', 'status');
});

// catch-all route
$router->any('{any}', function () {
    return 'four oh four';
});