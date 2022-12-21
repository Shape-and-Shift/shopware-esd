<?php declare(strict_types=1);

use Composer\Autoload\ClassLoader;

include_once __DIR__ . '/vendor/autoload.php';

$classLoader = new ClassLoader();
$classLoader->addPsr4('Sas\\Esd\\', __DIR__ . '/src', true);
$classLoader->addPsr4('Sas\\Esd\\Tests\\', __DIR__ . '/tests/PHPUnit', true);
$classLoader->register();
