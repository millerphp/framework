<?php

declare(strict_types=1);

namespace Tests\Fixtures\Controllers;

class TestController
{
    public function __invoke()
    {
        return 'invoked controller';
    }

    public function index()
    {
        return 'controller result';
    }
}
