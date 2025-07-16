<?php

declare(strict_types=1);

namespace App\Application\Actions\Task;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class UpdateTaskStatusAction
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
        $taskId = $args['id'] ?? null;
        $data = json_decode((string)$request->getBody(), true);
        $status = $data['status'] ?? null;
        if ($userId && $taskId && $status) {
            $db->update('tasks', ['status' => $status], ['id' => $taskId, 'user_id' => $userId]);
        }
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
} 