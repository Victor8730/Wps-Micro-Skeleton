<?php

declare(strict_types=1);

use WpsMicro\Core\Env;
use WpsMicro\Core\Kernel;

$rootPath = dirname(__DIR__);

require_once $rootPath . '/vendor/autoload.php';

Env::load($rootPath . '/.env');

return Kernel::fromConfigFile($rootPath . '/config/app.php');
