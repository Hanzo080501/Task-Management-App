<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class ProfileAction
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
        $user = $db->get('users', '*', ['id' => $userId]);
        $error = null;
        $success = null;
        if ($request->getMethod() === 'POST') {
            $params = $request->getParsedBody() ?? [];
            $first_name = $params['first_name'] ?? '';
            $last_name = $params['last_name'] ?? '';
            $display_name = $params['display_name'] ?? '';
            $about = $params['about'] ?? '';
            $hide_profile = isset($params['hide_profile']) ? 1 : 0;
            // Validasi field wajib
            if (!$first_name || !$last_name || !$display_name) {
                $error = 'First name, Last name, dan Display name wajib diisi.';
            } else {
                // Handle upload avatar
                $avatarPath = $user['avatar'] ?? null;
                $uploadedFiles = $request->getUploadedFiles();
                if (isset($uploadedFiles['avatar']) && $uploadedFiles['avatar']->getError() === UPLOAD_ERR_OK) {
                    $avatarFile = $uploadedFiles['avatar'];
                    $ext = strtolower(pathinfo($avatarFile->getClientFilename(), PATHINFO_EXTENSION));
                    $allowed = ['jpg','jpeg','png','gif','webp'];
                    if (!in_array($ext, $allowed)) {
                        $error = 'Avatar harus berupa file gambar (jpg, jpeg, png, gif, webp).';
                    } elseif ($avatarFile->getSize() > 2*1024*1024) {
                        $error = 'Ukuran avatar maksimal 2MB.';
                    } else {
                        $uploadDir = __DIR__ . '/../../../../public/uploads/avatars/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
                        $avatarFile->moveTo($uploadDir . $filename);
                        $avatarPath = '/uploads/avatars/' . $filename;
                    }
                }
                if (!$error) {
                    $db->update('users', [
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'display_name' => $display_name,
                        'about' => $about,
                        'hide_profile' => $hide_profile,
                        'avatar' => $avatarPath,
                    ], ['id' => $userId]);
                    $success = 'Profil berhasil diperbarui';
                    $user = $db->get('users', '*', ['id' => $userId]);
                }
            }
        }
        $html = $view->render('profile.twig', ['user' => $user, 'error' => $error, 'success' => $success]);
        $response->getBody()->write($html);
        return $response;
    }
} 