<?php

namespace Lightpack\Routing;

use Lightpack\Container\Container;
use Lightpack\Http\Request ;
use Lightpack\Routing\Router;

class Dispatcher
{
    /** @var Container */
    private $container;

    /** @var string */
    private $controller;

    /** @var string */
    private $action;

    /** @var array */
    private $params;

    /** @var Request */
    private $request;

    /** @var Router */
    private $router;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->request = $container->get('request');
        $this->router = $container->get('router');
        $this->throwExceptionIfRouteNotFound($this->router->meta());
        $this->controller = $this->router->controller();
        $this->action = $this->router->action();
        $this->params = $this->router->params();
    }

    public function dispatch() 
    {
        if(! \class_exists($this->controller)) {
            throw new \Lightpack\Exceptions\ControllerNotFoundException(
                sprintf("Controller Not Found Exception: %s", $this->controller)
            );
        }

        if(! \method_exists($this->controller, $this->action)) {
            throw new \Lightpack\Exceptions\ActionNotFoundException(
                sprintf("Action Not Found Exception: %s@%s", $this->controller, $this->action)
            );
        }

        return $this->container->call($this->controller, $this->action, $this->params);
    }

    private function throwExceptionIfRouteNotFound()
    {
        if(!$this->router->meta()) {
            throw new \Lightpack\Exceptions\RouteNotFoundException(
                sprintf(
                    "No route registered for request: %s %s", 
                    $this->request->method(), 
                    $this->request->path()
                )
            );
        }
    }
}
