<?php

declare(strict_types=1);

namespace Excalibur\Router\Exception;

use Exception;

class RouterException extends Exception
{
    public static function routeNotFound(string $method, string $uri): self
    {
        return new self(
            sprintf('No route found for %s %s', $method, $uri),
            404
        );
    }

    public static function controllerNotFound(string $controller): self
    {
        return new self(
            sprintf('Controller class %s not found', $controller),
            500
        );
    }

    public static function invalidController(string $controller): self
    {
        return new self(
            sprintf('Controller class %s must be invokable (implement __invoke method)', $controller),
            500
        );
    }

    public static function namedRouteNotFound(string $name): self
    {
        return new self(
            sprintf('Route [%s] not found', $name),
            500
        );
    }
}
