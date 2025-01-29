<?php

declare(strict_types=1);

use Excalibur\App;
use Excalibur\Foundation\Application;

if (!function_exists('app')) {
    function app(): App
    {
        return App::create();
    }
}

if (!function_exists('application')) {
    function application(): Application
    {
        return Application::getInstance();
    }
} 