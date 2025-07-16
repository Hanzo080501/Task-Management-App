<?php

declare(strict_types=1);

namespace App\Application\Actions\Task;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class ListTasksAction
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
        $user = $db->get('users', '*', ['id' => $userId]);
        $params = $request->getQueryParams();
        $where = ['user_id' => $userId];
        // Filter status
        if (!empty($params['status']) && $params['status'] !== 'all') {
            $where['status'] = $params['status'];
        }
        // Filter priority
        if (!empty($params['priority']) && $params['priority'] !== 'all') {
            $where['priority'] = $params['priority'];
        }
        // Filter overdue
        if (!empty($params['overdue']) && $params['overdue'] === '1') {
            $where['deadline[<]'] = date('Y-m-d');
            $where['status[!]'] = 'done';
        }
        // Search
        if (!empty($params['q'])) {
            $where['OR'] = [
                'title[~]' => $params['q'],
                'description[~]' => $params['q'],
            ];
        }
        // Sort
        $order = null;
        if (!empty($params['sort'])) {
            if ($params['sort'] === 'deadline') {
                $order = ['deadline' => 'ASC'];
            } elseif ($params['sort'] === 'priority') {
                $order = ['priority' => 'DESC'];
            }
        }
        $tasks = $db->select('tasks', '*', $order ? array_merge(['ORDER' => $order], ['AND' => $where]) : $where);
        $view = $this->container->get('view');
        $html = $view->render('tasks.twig', [
            'user' => $user,
            'tasks' => $tasks,
            'filter' => $params,
        ]);
        $response->getBody()->write($html);
        return $response;
    }
} 