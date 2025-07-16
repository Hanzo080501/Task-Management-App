<?php

declare(strict_types=1);

namespace App\Application\Actions\Task;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class RiwayatAction
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $db = $this->container->get('db');
        $view = $this->container->get('view');
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
        if ($request->getMethod() === 'POST') {
            // Hapus semua tugas selesai milik user
            $db->delete('tasks', [
                'user_id' => $userId,
                'status' => 'done',
            ]);
            return $response->withHeader('Location', '/riwayat')->withStatus(302);
        }
        $user = $db->get('users', '*', ['id' => $userId]);
        $completedTasks = $db->select('tasks', '*', [
            'user_id' => $userId,
            'status' => 'done',
            'ORDER' => ['deadline' => 'DESC'],
            'LIMIT' => 50,
        ]);
        $html = $view->render('riwayat.twig', [
            'user' => $user,
            'completedTasks' => $completedTasks,
        ]);
        $response->getBody()->write($html);
        return $response;
    }
} 