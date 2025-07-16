<?php

declare(strict_types=1);

namespace App\Application\Actions\Auth;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;

class LoginAction
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $method = $request->getMethod();
        $view = $this->container->get('view');
        $db = $this->container->get('db');
        $params = $request->getParsedBody() ?? [];
        $error = null;

        if ($method === 'POST') {
            $username = $params['username'] ?? '';
            $password = $params['password'] ?? '';
            $user = $db->get('users', '*', ['username' => $username]);
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                return $response->withHeader('Location', '/dashboard')->withStatus(302);
            } else {
                $error = 'Username atau password salah';
            }
        }
        $html = $view->render('login.twig', ['error' => $error]);
        $response->getBody()->write($html);
        return $response;
    }
} 