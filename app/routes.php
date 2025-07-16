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

        // Riwayat (History/Completed Tasks)
    $app->map(['GET', 'POST'], '/riwayat', \App\Application\Actions\Task\RiwayatAction::class)->add(AuthMiddleware::class);

    // Task Management
    $app->group('/tasks', function (Group $group) {
        $group->get('', \App\Application\Actions\Task\ListTasksAction::class);
        $group->post('', \App\Application\Actions\Task\CreateTaskAction::class);
        $group->get('/{id}', \App\Application\Actions\Task\ViewTaskAction::class);
        $group->post('/{id}/update', \App\Application\Actions\Task\UpdateTaskAction::class);
        $group->post('/{id}/delete', \App\Application\Actions\Task\DeleteTaskAction::class);
        $group->post('/{id}/status', \App\Application\Actions\Task\UpdateTaskStatusAction::class);
    })->add(AuthMiddleware::class);
    
// Tim (Team Page)
    $container = $app->getContainer();
    $app->get('/tim', function ($request, $response, $args) use ($container) {
        $view = $container->get('view');
        $db = $container->get('db');
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
        $user = $db->get('users', '*', ['id' => $userId]);
        $html = $view->render('tim.twig', ['user' => $user]);
        $response->getBody()->write($html);
        return $response;
    })->add(AuthMiddleware::class);

     // User Profile
    $app->get('/profile', \App\Application\Actions\User\ProfileAction::class)->add(AuthMiddleware::class);
    $app->post('/profile', \App\Application\Actions\User\ProfileAction::class)->add(AuthMiddleware::class);
};
