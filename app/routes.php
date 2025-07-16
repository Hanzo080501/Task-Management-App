<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Application\Middleware\AuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    // Auth routes
    $app->get('/login', \App\Application\Actions\Auth\LoginAction::class);
    $app->post('/login', \App\Application\Actions\Auth\LoginAction::class);
    $app->get('/register', \App\Application\Actions\Auth\RegisterAction::class);
    $app->post('/register', \App\Application\Actions\Auth\RegisterAction::class);
    $app->get('/logout', \App\Application\Actions\Auth\LogoutAction::class);

    // Dashboard
    $app->get('/dashboard', \App\Application\Actions\Dashboard\DashboardAction::class)->add(AuthMiddleware::class);


     // User Profile
    $app->get('/profile', \App\Application\Actions\User\ProfileAction::class)->add(AuthMiddleware::class);
    $app->post('/profile', \App\Application\Actions\User\ProfileAction::class)->add(AuthMiddleware::class);
};
