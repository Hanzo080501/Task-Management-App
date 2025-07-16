<?php

declare(strict_types=1);

namespace App\Application\Actions\Auth;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;

class RegisterAction
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
        $success = null;

        if ($method === 'POST') {
            $username = $params['username'] ?? '';
            $password = $params['password'] ?? '';
            $confirm = $params['confirm'] ?? '';
            if ($password !== $confirm) {
                $error = 'Password tidak cocok';
            } elseif ($db->has('users', ['username' => $username])) {
                $error = 'Username sudah terdaftar';
            } else {
                $db->insert('users', [
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                $success = 'Registrasi berhasil, silakan login.';
            }
        }
        $html = $view->render('register.twig', ['error' => $error, 'success' => $success]);
        $response->getBody()->write($html);
        return $response;
    }
} 