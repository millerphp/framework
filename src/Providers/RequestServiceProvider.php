<?php

declare(strict_types=1);

namespace Excalibur\Providers;

use Excalibur\HTTP\Request;
use Excalibur\Container\Container;

class RequestServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(Request::class, function () {
            return Request::capture();
        });
    }
} 