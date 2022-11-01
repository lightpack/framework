<?php

namespace Lightpack\Routing;

use Lightpack\Container\Container;
use Lightpack\Http\Request;
use Lightpack\Routing\Router;

class Dispatcher
{
    /** @var Container */
    private Container $container;

    /** @var Request */
    private Request $request;

    /** @var Router */
    private Router $router;

    /** @var Route */
    private Route $route;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->request = $container->get('request');
        $this->router = $container->get('router');
        $this->throwExceptionIfRouteNotFound();
        $this->route = $this->router->getRoute();
        $this->request->setRoute($this->route);
    }

    public function dispatch()
    {
        $controller = $this->route->getController();
        $action = $this->route->getAction();
        $params = $this->route->getParams();

        if (!\class_exists($controller)) {
            throw new \Lightpack\Exceptions\ControllerNotFoundException(
                sprintf("Controller Not Found Exception: %s", $controller)
            );
        }

        if (!\method_exists($controller, $action)) {
            throw new \Lightpack\Exceptions\ActionNotFoundException(
                sprintf("Action Not Found Exception: %s@%s", $controller, $action)
            );
        }

        return $this->container->call($controller, $action, array_values($params));
    }

    private function throwExceptionIfRouteNotFound()
    {
        if (!$this->router->hasRoute()) {
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
