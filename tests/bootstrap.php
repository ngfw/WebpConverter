<?php

use Illuminate\Container\Container;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;
use Dotenv\Dotenv;

if (!function_exists('storage_path')) {
    function storage_path($path = '')
    {
        return __DIR__ . '/storage/' . $path;
    }
}

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

$app = new Container();

$app->instance('config', new ConfigRepository([
    'filesystems.default' => 'local',
    'filesystems.disks.local' => [
        'driver' => 'local',
        'root' => storage_path(),
    ],
    'webp_converter' => require __DIR__ . '/../config/webp_converter.php',
]));

(new FilesystemServiceProvider($app))->register();
Facade::setFacadeApplication($app);
Config::setFacadeApplication($app);
Storage::setFacadeApplication($app);

