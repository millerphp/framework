<?php

declare(strict_types=1);

namespace Excalibur\HTTP\Facades;

/**
 * @method static \Excalibur\HTTP\Response make(mixed $content = '', int $status = 200, array $headers = [])
 * @method static \Excalibur\HTTP\Response json(mixed $data, int $status = 200)
 * @method static never send()
 * @method static \Excalibur\HTTP\Response setContent(mixed $content)
 * @method static \Excalibur\HTTP\Response setStatusCode(int $code)
 * @method static \Excalibur\HTTP\Response header(string $key, string $value)
 * @method static \Excalibur\HTTP\Response withHeaders(array $headers)
 */
class Response extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Excalibur\HTTP\Response::class;
    }
} 