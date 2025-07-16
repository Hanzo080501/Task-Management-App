<?php

declare(strict_types=1);

namespace App\Application\Actions\Task;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class ViewTaskAction
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $view = $this->container->get('view');
        $db = $this->container->get('db');
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
        $taskId = $args['id'] ?? null;
        $task = $db->get('tasks', '*', ['id' => $taskId, 'user_id' => $userId]);
        if (!$task) {
            return $response->withHeader('Location', '/tasks')->withStatus(302);
        }
        $html = $view->render('task_view.twig', ['task' => $task]);
        $response->getBody()->write($html);
        return $response;
    }
} 