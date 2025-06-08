<?php

namespace Lightpack;

use JsonSerializable;
use Lightpack\Http\Response;
use Lightpack\Routing\Dispatcher;
use Lightpack\Container\Container;
use Lightpack\Debug\ExceptionRenderer;
use Lightpack\Debug\Handler;
use Lightpack\Exceptions\FilterNotFoundException;

final class App
{
    public static function bootRouteNames()
    {
        Container::getInstance()->get('route')->bootRouteNames();
    }

    public static function bootProviders(array $providers = [])
    {
        $container = Container::getInstance();
        $providers = array_merge(self::getFrameworkProviders(), $providers);

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
        $request = $container->get('request');
        $response = $container->get('response');
        $router = $container->get('router');
        $filter = $container->get('filter');
        $router->parse($request->path());
        $dispatcher = new Dispatcher($container);
        $routeUri = $container->get('router')->getRoute()->getUri();

        self::bootFilters();

        // Process before filters.
        $result = $filter->processBeforeFilters($routeUri);

        if ($result instanceof Response) {
            return $result;
        }

        // Dispatch app request.
        $response = self::prepareResponse($dispatcher->dispatch());

        // Process after filters.
        $filter->setResponse($response);
        $response = $filter->processAfterFilters($routeUri);

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
            \Lightpack\Providers\StorageProvider::class,
            \Lightpack\Providers\RequestProvider::class,
            \Lightpack\Providers\ResponseProvider::class,
            \Lightpack\Providers\DatabaseProvider::class,
            \Lightpack\Providers\TemplateProvider::class,
            \Lightpack\Providers\AuthProvider::class,
            \Lightpack\Providers\CryptoProvider::class,
            \Lightpack\Providers\ScheduleProvider::class,
            \Lightpack\Providers\ValidationProvider::class,
            \Lightpack\Providers\RedisProvider::class,
            \Lightpack\Providers\CableProvider::class,
            \Lightpack\Providers\CaptchaProvider::class,
            \Lightpack\Providers\PdfProvider::class,
            \Lightpack\Providers\SmsProvider::class,
            \Lightpack\Providers\MfaProvider::class,
            \Lightpack\Providers\AiProvider::class,
            \Lightpack\Providers\SettingsProvider::class,
            \Lightpack\Providers\SecretsProvider::class,
        ];
    }

    private static function bootFilters()
    {
        $container = Container::getInstance();
        $filtersConfig = $container->get('config')->get('filters');
        $routeFilters = $container->get('router')->getRoute()->getFilters();
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

            $filter->register($router->getRoute()->getUri(), $filtersConfig[$filterName], $params);
        }
    }

    private static function prepareResponse(mixed $response): Response
    {
        if ($response instanceof Response) {
            return $response;
        }

        if (is_string($response)) {
            return (new Response)->setBody($response);
        }

        if (is_array($response)) {
            return (new Response)->json($response);
        }

        if ($response instanceof \stdClass) {
            return (new Response)->json((array) $response);
        }

        if ($response instanceof JsonSerializable) {
            return (new Response)->json($response);
        }

        return (new Response);
    }
}
