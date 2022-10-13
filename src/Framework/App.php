<?php

namespace Lightpack;

use Lightpack\Http\Response;
use Lightpack\Routing\Dispatcher;
use Lightpack\Container\Container;
use Lightpack\Debug\ExceptionRenderer;
use Lightpack\Debug\Handler;
use Lightpack\Exceptions\FilterNotFoundException;

final class App
{
    public static function bootProviders(array $providers = [])
    {
        $container = Container::getInstance();
        $providers = self::getFrameworkProviders() + $providers;

        foreach ($providers as $provider) {
            $provider = $container->resolve($provider);
            $provider->register($container);
        }
    }

    public static function bootEvents()
    {
        $container = Container::getInstance();
        $events = $container->get('config')->get('events');

        foreach ($events as $event => $listeners) {
            foreach ($listeners as $listener) {
                $container->get('event')->subscribe($event, $listener);
            }
        }
    }

    public static function bootDebugHandler()
    {
        $container = Container::getInstance();
        $logger = $container->get('logger');
        $environment = $container->get('config')->get('app.env');
        $exceptionRenderer = new ExceptionRenderer($environment);
        $handler = new Handler($logger, $exceptionRenderer);

        set_exception_handler([$handler, 'handleException']);
        set_error_handler([$handler, 'handleError']);
        register_shutdown_function([$handler, 'handleShutdown']);
    }

    public static function run(): Response
    {
        $container = Container::getInstance();
        $response = $container->get('response');
        $filter = $container->get('filter');
        $dispatcher = new Dispatcher($container);
        $route = $container->get('router')->getRouteDefinition()->getRoute();

        self::bootFilters();

        // Process before filters.
        $result = $filter->processBeforeFilters($route);

        if ($result instanceof Response) {
            return $result;
        }

        // Dispatch app request.
        $result = $dispatcher->dispatch();

        if ($result instanceof Response) {
            $response = $result;
        }

        // Process after filters.
        $filter->setResponse($response);
        $response = $filter->processAfterFilters($route);

        return $response;
    }

    private static function getFrameworkProviders(): array
    {
        return [
            \Lightpack\Providers\LogProvider::class,
            \Lightpack\Providers\RouteProvider::class,
            \Lightpack\Providers\EventProvider::class,
            \Lightpack\Providers\CacheProvider::class,
            \Lightpack\Providers\ConfigProvider::class,
            \Lightpack\Providers\RouterProvider::class,
            \Lightpack\Providers\FilterProvider::class,
            \Lightpack\Providers\CookieProvider::class,
            \Lightpack\Providers\SessionProvider::class,
            \Lightpack\Providers\RequestProvider::class,
            \Lightpack\Providers\ResponseProvider::class,
            \Lightpack\Providers\DatabaseProvider::class,
            \Lightpack\Providers\TemplateProvider::class,
            \Lightpack\Providers\AuthProvider::class,
            \Lightpack\Providers\CryptoProvider::class,
            \Lightpack\Providers\ScheduleProvider::class,
        ];
    }

    private static function bootFilters()
    {
        $container = Container::getInstance();
        $filtersConfig = $container->get('config')->get('filters');
        $routeFilters = $container->get('router')->getRouteDefinition()->getFilters();
        $filter = $container->get('filter');
        $router = $container->get('router');

        foreach ($routeFilters as $filterAlias) {
            // if $filterAlias has ':' then it is a filter with parameters.
            [$filterName, $params] = explode(':', $filterAlias) + [1 => []];
            $filterName = trim($filterName);
            $params = !empty($params) ? explode(',', $params) : [];
            $params = array_map('trim', $params);

            if (!array_key_exists($filterName, $filtersConfig)) {
                throw new FilterNotFoundException(
                    "No filter class registered for: {$filterName}"
                );
            }

            $filter->register($router->getRouteDefinition()->getRoute(), $filtersConfig[$filterName], $params);
        }
    }
}
