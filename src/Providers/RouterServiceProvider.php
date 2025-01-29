<?php

declare(strict_types=1);

namespace Excalibur\Providers;

use Excalibur\Router\Router;
use Excalibur\Container\Container;

class RouterServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(Router::class, function () {
            return new Router();
        });
    }
} 