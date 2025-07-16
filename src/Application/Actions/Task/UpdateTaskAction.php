<?php

declare(strict_types=1);

namespace App\Application\Actions\Task;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class UpdateTaskAction
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
        $taskId = $args['id'] ?? null;
        $contentType = $request->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $params = json_decode((string)$request->getBody(), true) ?? [];
        } else {
            $params = $request->getParsedBody() ?? [];
        }
        $title = $params['title'] ?? '';
        $description = $params['description'] ?? '';
        if ($taskId && $title) {
            $db->update('tasks', [
                'title' => $title,
                'description' => $description,
                'deadline' => $params['deadline'] ?? null,
                'priority' => $params['priority'] ?? 'Medium',
            ], ['id' => $taskId, 'user_id' => $userId]);
        }
        if (strpos($contentType, 'application/json') !== false) {
            $response->getBody()->write(json_encode(['success' => true]));
            return $response->withHeader('Content-Type', 'application/json');
        }
        return $response->withHeader('Location', '/tasks')->withStatus(302);
    }
} 