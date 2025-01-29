<?php

declare(strict_types=1);

namespace Excalibur\HTTP\Facades;

/**
 * @method static mixed input(string $key, mixed $default = null)
 * @method static array all()
 * @method static bool has(string $key)
 * @method static string method()
 * @method static string uri()
 * @method static mixed header(string $key, mixed $default = null)
 */
class Request extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Excalibur\HTTP\Request::class;
    }
} 