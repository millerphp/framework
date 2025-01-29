<?php

declare(strict_types=1);

namespace Excalibur;

use Excalibur\HTTP\Request;
use Excalibur\Router\Router;
use Excalibur\Container\Container;
use Excalibur\HTTP\Facades\Facade;
use Excalibur\Providers\RequestServiceProvider;
use Excalibur\Providers\RouterServiceProvider;

class App
{
    private static ?self $instance = null;
    private Container $container;

    private function __construct()
    {
        $this->container = new Container();
        
        // Set the container for the Facade system
        Facade::setContainer($this->container);
        
        // Register providers
        $this->registerProviders();
    }

    public static function create(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function registerProviders(): void
    {
        $providers = [
            RequestServiceProvider::class,
            RouterServiceProvider::class,
            // Add other providers here
        ];

        foreach ($providers as $provider) {
            $providerInstance = new $provider();
            $providerInstance->register($this->container);
        }
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getRequest(): Request
    {
        return $this->container->get(Request::class);
    }

    public function getRouter(): Router
    {
        return $this->container->get(Router::class);
    }

    public function callAction(string $controller, string $method, array $parameters = [])
    {
        return $this->container->call([$controller, $method], $parameters);
    }
} 