<?php

declare(strict_types=1);

namespace Excalibur\Providers;

use Excalibur\HTTP\Response;
use Excalibur\Container\Container;

class ResponseServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(Response::class, function () {
            return new Response();
        });
    }
} 