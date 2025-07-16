<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            return new Settings([
                'displayErrorDetails' => true, // Should be set to false in production
                'logError'            => false,
                'logErrorDetails'     => false,
                'logger' => [
                    'name' => 'slim-app',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],
                // Medoo Database settings
                'db' => [
                    'type' => 'mysql',
                    'database' => 'task_management',
                    'host' => 'localhost',
                    'username' => 'root',
                    'password' => '',
                    'charset' => 'utf8mb4',
                ],
                // Twig settings
                'twig' => [
                    'template_path' => __DIR__ . '/../templates',
                    'cache_path' => __DIR__ . '/../var/cache/twig',
                    'debug' => true,
                ],
            ]);
        }
    ]);
};
