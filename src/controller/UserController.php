<?php

declare(strict_types=1);

namespace App\controller;

use App\core\attribute\Route;
use App\model\User;
use App\repository\UserRepository;
use App\services\FileUploadService;
use DateTime;
use Exception;

class UserController
{

    #[Route('/api/upload-avatar', 'POST')]
    public function uploadAvatar() 
    {
        if (!isset($_FILES['avatar'])) throw new Exception('Aucun fichier uploadé!');

        try {
            $filename = FileUploadService::handleAvatarUpload($_FILES['avatar'], __DIR__ . '/../../public/uploads/avatar/');

            // if ($user->getAvatar() !=== 'mon_avatar_par_defaut.jpg') {
            //     FileUploadService::deleteOldAvatar($user->getAvatar());
            // }

            $userRepository = new UserRepository();
            $saved = $userRepository->saveAvatar($filename);
            
            if (!$saved) throw new Exception('Erreur lors de la sauvegarde');

            echo json_encode([
                'success' => true,
                'message' => 'Avatar mis à jour avec succès',
                'filename' => $filename
            ]);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de l'upload: " . $e->getMessage());
        }
    }

    #[Route('/api/register', 'POST')]
    public function register() 
    {

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) throw new Exception('Json invalide');

        $userData = [
            'username' => $data['username'] ?? '',
            'email' => $data['email'] ?? '',
            'password' => password_hash($data['password'], PASSWORD_BCRYPT) ?? '',
            // 'avatar' => $data['avatar'] ?? 'default_avatar.png',
            // 'role' => $data['role'] ?? ['ROLE_USER'],
            // 'created_at' => new \DateTime(),
        ];

        // création user 
        $user = new User($userData);
        $user->setCreatedAt((new DateTime())->format('Y-m-d H:i:s'));
        $userRepository = new UserRepository();
        $saved = $userRepository->save($user);

        if (!$saved) throw new Exception('Erreur lors de la sauvegarde');

        echo json_encode([
            'success' => true,
            'message' => 'Inscription réussie ! Veuillez vérifier vos email.' . json_encode($data)
        ]);
    }
}
