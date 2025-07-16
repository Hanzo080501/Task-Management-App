<?php

declare(strict_types=1);

namespace App\Application\Actions\Dashboard;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class DashboardAction
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $view = $this->container->get('view');
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
        $db = $this->container->get('db');
        $user = $db->get('users', '*', ['id' => $userId]);
        // Statistik utama
        $totalTasks = $db->count('tasks', ['user_id' => $userId]);
        $doneTasks = $db->count('tasks', ['user_id' => $userId, 'status' => 'done']);
        $ongoingTasks = $db->count('tasks', ['user_id' => $userId, 'status' => 'ongoing']);
        $lateTasks = $db->count('tasks', [
            'user_id' => $userId,
            'deadline[<]' => date('Y-m-d'),
            'status[!]' => 'done',
        ]);
        $upcomingTasks = $db->count('tasks', [
            'user_id' => $userId,
            'deadline[>]' => date('Y-m-d'),
            'status[!]' => 'done',
        ]);
        // Ambil tugas deadline hari ini
        $todayTasks = $db->select('tasks', ['id', 'title', 'description', 'deadline'], [
            'user_id' => $userId,
            'deadline' => date('Y-m-d'),
            'status[!]' => 'done',
        ]);
        // Ambil tugas yang sudah selesai
        $completedTasks = $db->select('tasks', ['id', 'title', 'description', 'deadline'], [
            'user_id' => $userId,
            'status' => 'done',
            'ORDER' => ['deadline' => 'DESC'],
            'LIMIT' => 10,
        ]);
        // Data untuk chart progress tugas (diagram lingkaran)
        $chartData = [
            'done' => $doneTasks,
            'ongoing' => $ongoingTasks,
            'new' => $db->count('tasks', ['user_id' => $userId, 'status' => 'new']),
            'late' => $lateTasks,
        ];
        // Data untuk chart tasks left by week (stacked bar)
        $startDate = date('Y-m-d', strtotime('-3 weeks', strtotime('monday this week')));
        $endDate = date('Y-m-d', strtotime('sunday this week'));
        $tasksRaw = $db->select('tasks', ['status', 'deadline'], [
            'user_id' => $userId,
            'deadline[>=]' => $startDate,
            'deadline[<=]' => $endDate,
        ]);
        $statuses = ['done', 'ongoing', 'new'];
        $statusLabels = [
            'done' => 'Done',
            'ongoing' => 'In progress',
            'new' => 'Open',
        ];
        $statusColors = [
            'done' => '#22c55e',
            'ongoing' => '#3b82f6',
            'new' => '#fde047',
        ];
        $weeks = [];
        for ($i = 0; $i < 4; $i++) {
            $monday = date('Y-m-d', strtotime("-" . (3-$i) . " weeks monday this week"));
            $sunday = date('Y-m-d', strtotime($monday . ' +6 days'));
            $weeks[] = [
                'label' => date('d M', strtotime($monday)) . ' - ' . date('d M', strtotime($sunday)),
                'start' => $monday,
                'end' => $sunday,
            ];
        }
        $chartDataByWeek = [];
        foreach ($statuses as $status) {
            $chartDataByWeek[$status] = array_fill(0, 4, 0);
        }
        foreach ($tasksRaw as $task) {
            foreach ($weeks as $i => $week) {
                if ($task['deadline'] >= $week['start'] && $task['deadline'] <= $week['end']) {
                    $chartDataByWeek[$task['status']][$i]++;
                }
            }
        }
        // Hitung jumlah tugas hari ini untuk in progress minggu berjalan
        $todayCount = count($todayTasks);
        foreach ($weeks as $i => $week) {
            if ($week['start'] <= date('Y-m-d') && $week['end'] >= date('Y-m-d')) {
                $chartDataByWeek['ongoing'][$i] = $todayCount;
            }
        }
        $tasksByWeekChartData = [
            'labels' => array_column($weeks, 'label'),
            'datasets' => [],
        ];
        foreach ($statuses as $status) {
            $tasksByWeekChartData['datasets'][] = [
                'label' => $statusLabels[$status],
                'backgroundColor' => $statusColors[$status],
                'data' => $chartDataByWeek[$status],
            ];
        }
        $html = $view->render('dashboard.twig', [
            'user' => $user,
            'totalTasks' => $totalTasks,
            'doneTasks' => $doneTasks,
            'ongoingTasks' => $ongoingTasks,
            'lateTasks' => $lateTasks,
            'upcomingTasks' => $upcomingTasks,
            'chartData' => $chartData,
            'todayTasks' => $todayTasks,
            'completedTasks' => $completedTasks,
            'tasksByWeekChartData' => $tasksByWeekChartData,
        ]);
        $response->getBody()->write($html);
        return $response;
    }
} 