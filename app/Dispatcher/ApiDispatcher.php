<?php

namespace Example\Addons\Authentication\Dispatcher;


use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Example\Addons\Authentication\Middleware\ParamsToPath;

class ApiDispatcher
{


    public $response;

    public function __construct()
    {
        $container = new Container;
        $request = Request::capture();
        $container->instance(Request::class, $request);
        $events = new Dispatcher($container);
        $router = new Router($events, $container);
        $globalMiddleware = [
            ParamsToPath::class,
        ];
//        $router->pushMiddlewareToGroup('', ParamsToPath::class);
        require_once 'routes.php';
        $redirect = new Redirector(new UrlGenerator($router->getRoutes(), $request));
        $this->response = (new Pipeline($container))
            ->send($request)
            ->through($globalMiddleware)
            ->then(function ($request) use ($router) {
                return $router->dispatch($request);
            });
        $this->response->send();
        
    }


}