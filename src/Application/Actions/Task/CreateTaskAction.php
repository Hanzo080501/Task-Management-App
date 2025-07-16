<?php

declare(strict_types=1);

namespace App\Application\Actions\Task;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class CreateTaskAction
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $db = $this->container->get('db');
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
        $params = $request->getParsedBody() ?? [];
        $title = $params['title'] ?? '';
        $description = $params['description'] ?? '';
        $status = $params['status'] ?? 'new';
        $deadline = $params['deadline'] ?? null;
        $priority = $params['priority'] ?? 'Medium';
        if ($title) {
            $db->insert('tasks', [
                'user_id' => $userId,
                'title' => $title,
                'description' => $description,
                'status' => $status,
                'deadline' => $deadline,
                'priority' => $priority,
            ]);
        }
        return $response->withHeader('Location', '/tasks')->withStatus(302);
    }
} 