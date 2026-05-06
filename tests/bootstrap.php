<?php
declare(strict_types=1);

// Reuse the monorepo's installed vendor (symfony/console, phpunit) and add
// PSR-4 mappings for the lib + tests so a `composer install` inside the lib
// itself isn't required to run the suite.
$loader = require __DIR__ . '/../../../vendor/autoload.php';

if (!is_object($loader)) {
    $loaders = \Composer\Autoload\ClassLoader::getRegisteredLoaders();
    $loader = reset($loaders);
}

$loader->addPsr4('Survos\\CommandSchema\\', __DIR__ . '/../src/');
$loader->addPsr4('Survos\\CommandSchema\\Tests\\', __DIR__ . '/');
