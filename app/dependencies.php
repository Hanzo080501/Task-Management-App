<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        // Medoo Database
        'db' => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $db = $settings->get('db');
            return new \Medoo\Medoo([
                'type' => $db['type'],
                'database' => $db['database'],
                'host' => $db['host'],
                'username' => $db['username'],
                'password' => $db['password'],
                'charset' => $db['charset'],
            ]);
        },
        // Twig Templating
        'view' => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $twigSettings = $settings->get('twig');
            $loader = new \Twig\Loader\FilesystemLoader($twigSettings['template_path']);
            $twig = new \Twig\Environment($loader, [
                'cache' => $twigSettings['cache_path'],
                'debug' => $twigSettings['debug'],
            ]);
            return $twig;
        },
    ]);
};
