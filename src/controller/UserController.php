<?php
declare(strict_types=1);
namespace App\controller;
use DateTime;
use Exception;
use App\model\User;
use App\services\JWTService;
use App\services\MailService;
use App\core\attribute\Route;
use App\repository\UserRepository;
use App\services\FileUploadService;

class UserController
{
     
    private UserRepository $userRepository;
    public function __construct() {
        $this->userRepository = new UserRepository;
    }

    public function verifyUniqueUserEntry(array $data, ?User $currentUser = null): void
{
    error_log("Validation data: " . json_encode($data));
    error_log("Current user: " . ($currentUser ? $currentUser->getUsername() . " / " . $currentUser->getEmail() : "null"));
    
    $usernameExists = false;
    $emailExists = false;

    // Check username only if it's provided and different from current user's username
    if (!empty($data['username'])) {
        error_log("Checking username: " . $data['username']);
        if ($currentUser === null || $data['username'] !== $currentUser->getUsername()) {
            error_log("Username is different from current, checking database...");
            $existingUser = $this->userRepository->findUserByUsername($data['username']);
            $usernameExists = $existingUser ? true : false;
            error_log("Username exists: " . ($usernameExists ? "yes" : "no"));
        } else {
            error_log("Username same as current user, skipping check");
        }
    }

    // Same for email...
    if (!empty($data['email'])) {
        error_log("Checking email: " . $data['email']);
        if ($currentUser === null || $data['email'] !== $currentUser->getEmail()) {
            error_log("Email is different from current, checking database...");
            $existingUser = $this->userRepository->findUserByEmail($data['email']);
            $emailExists = $existingUser ? true : false;
            error_log("Email exists: " . ($emailExists ? "yes" : "no"));
        } else {
            error_log("Email same as current user, skipping check");
        }
    }

    // Rest of validation...
}

    #[Route('/api/upload-avatar', 'POST')]
    public function uploadAvatar() 
    {
        if (!isset($_FILES['avatar'])) throw new Exception('Aucun fichier uploadé!');

        try {
            $filename = FileUploadService::handleAvatarUpload($_FILES['avatar'], __DIR__ . '/../../public/uploads/avatar/');

            // if ($user->getAvatar() !=== 'mon_avatar_par_defaut.jpg') {
            //     FileUploadService::deleteOldAvatar($user->getAvatar());
            // }

            echo json_encode([
                'success' => true,
                'message' => 'Avatar mis à jour avec succès',
                'filename' => $filename
            ]);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de l'upload: " . $e->getMessage());
        }
    }

    #[Route('/api/login', 'POST')]
    public function login() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) throw new Exception('Json invalide');
        $user = $this->userRepository->findUserByEmail($data['email']);
        if (!$user) throw new Exception('Email ou mot de passe incorrect !');
        if (!password_verify($data['password'], $user->getPassword())) throw new Exception
        ('Email ou mot de passe incorrect');
        if (!$user->getIsVerified()) throw new Exception('Veuillez vérifier votre email avant de vous connecter');

        // générer le token JWT
        $token = JWTService::generate([
            "id_user" => $user->getId(),
            "role" => $user->getRole(),
            "email" => $user->getEmail()
        ]);

        echo json_encode([
            'success' => true,
            'token' => $token,
            'user' => [
                'avatar' => $user->getAvatar(),
                'username' => $user->getUsername()
            ]
        ]);

    } catch (\Exception $e) {
        error_log('Erreur inscription: ' . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

    #[Route('/api/register', 'POST')]
    public function register() 
    {
        try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) throw new Exception('Json invalide');

        $emailToken = bin2hex(random_bytes(32));

        $userData = [
            'username' => $data['username'] ?? '',
            'email' => $data['email'] ?? '',
            'password' => password_hash($data['password'], PASSWORD_BCRYPT) ?? '',
            'avatar' => $data['avatar'] ?? 'default_avatar.jpg',
            'email_token' => $emailToken
        ];

        // création user 
        $user = new User($userData);
        $this->verifyUniqueUserEntry($data, $user);

        $user->setCreatedAt((new DateTime())->format('Y-m-d H:i:s'));

        $saved = $this->userRepository->save($user);

        if (!$saved) throw new Exception('Erreur lors de la sauvegarde');

        if (!$user->getEmailToken()) throw new Exception('Erreur lors de la génération du token de vérification');

        MailService::sendEmailVerification($user->getEmail(), $user->getEmailToken());

        echo json_encode([
            'success' => true,
            'message' => 'Inscription réussie ! Veuillez vérifier vos email.' . json_encode($data)
        ]);
    } catch (\Exception $e) {
        error_log('Erreur inscription: ' . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
  }

  #[Route('/api/verify-email')]
  public function verifyEmail() {
    try {
        $token = $_GET['token'] ?? null;
        
        if (!$token) throw new Exception('Token manquant!');


        $user = $this->userRepository->findUserByToken($token);

        if (!$user) throw new Exception('Utilisateur introuvable');

        $user->setEmailToken(null);
        $user->setIsVerified(true);

        $updated = $this->userRepository->update($user);
        if (!$updated) throw new Exception("Erreur lors de la mise à jour de l'utilisateur !");
        echo json_encode([
            'success' => true,
            'message' => 'Email vérifié avec succès!'
        ]);

    } catch (\Exception $e) {
        error_log('Erreur inscription: ' . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
  }

  #[Route('/api/user/update', 'POST')]
  public function updateProfil() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) throw new Exception('Json invalide');
        // Récupération token 
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        $token = str_replace('Bearer ', '', $authHeader);
        if (!$token) throw new \Exception('Not authorized');

        // Appel du service JWT pour vérifier le token
        $verifToken = JWTService::verify($token);
        if (!$verifToken) throw new Exception('Token invalide');
        $user = $this->userRepository->findUserById($verifToken['id_user']);
        if (!$user) throw new Exception('Utilisateur non trouvé');

        $this->verifyUniqueUserEntry($data, $user);

        // mettre à jour les infos utilisateurs
        if (isset($data['username'])) $user->setUsername($data['username']);
        if (isset($data['useremail'])) $user->setEmail($data['useremail']);
       // si autre champs à modifier
        // if (isset($data['firstname'])) $user->setFirstName($data['firstname']); // faut avoir le champ dans la bdd

        $update = $this->userRepository->update($user);

        if (!$update) throw new Exception('Problème update utilisateur BDD');

        echo json_encode([
            'success' => true,
            'message' => 'Profil mis à jour avec succès !'
        ]);

  } catch (\Exception $e) {
        error_log('Erreur inscription: ' . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
  }

   #[Route('/api/user/update-avatar', 'POST')]
    /**
     * Met à jour l'avatar de l'utilisateur
     * Route : POST /api/user/update-avatar
     */
    public function updateAvatar(): void
    {
        try {
            // Récupération token
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

            $token = str_replace('Bearer ', '', $authHeader);
            if (!$token) throw new \Exception('Not authorized');


            if (!$token) {
                throw new \Exception('Non autorisé');
            }

            $payload = JWTService::verify($token);
            if (!$payload) {
                throw new \Exception('Token invalide');
            }

            if (!isset($_FILES['avatar'])) {
                throw new \Exception('Aucun fichier envoyé');
            }

            $user = $this->userRepository->findUserById($payload['id_user']);

            if (!$user) {
                throw new \Exception('Utilisateur non trouvé');
            }

            // Gérer l'upload de l'avatar
            try {
                $upload_dir = __DIR__ . '/../../public/uploads/avatar/';
                $avatarFilename = FileUploadService::handleAvatarUpload($_FILES
                ['avatar'], $upload_dir);

                // Supprimer l'ancien avatar si il existe
                if ($user->getAvatar()&& $user->getAvatar() !== 'mon_avatar_par_defaut.jpg') {
                    FileUploadService::deleteOldAvatar($user->getAvatar(), $upload_dir);
                }

                $user->setAvatar($avatarFilename);
                $updated = $this->userRepository->update($user);

                if (!$updated) {
                    throw new \Exception('Erreur lors de la mise à jour');
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Avatar mis à jour avec succès',
                    'avatar' => $avatarFilename
                ]);
            } catch (\Exception $e) {
                throw new \Exception('Erreur lors de l\'upload: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    #[Route('/api/user/request-reset', 'POST')]
    /**
     * Envoie un email avec un lien de réinitialisation
     * Route : POST /api/user/request-reset
     */
    public function requestPasswordReset(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['email'])) {
                throw new \Exception('Email requis');
            }

            $user = $this->userRepository->findUserByEmail($data['email']);

            if (!$user) {
                throw new \Exception('Email non trouvé');
            }

            // Générer un token de réinitialisation
            $resetToken = bin2hex(random_bytes(32));
            $user->setResetToken($resetToken);
            $user->setResetAt((new DateTime())->format('Y-m-d H:i:s'));

            if (!$this->userRepository->update($user)) {
                throw new \Exception('Erreur lors de la génération du token');
            }

            // Envoyer l'email
            MailService::sendPasswordResetEmail($user->getEmail(), $resetToken);

            echo json_encode([
                'success' => true,
                'message' => 'Email de réinitialisation envoyé'
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    #[Route('/api/user/reset-password', 'POST')]
    /**
     * Réinitialise le mot de passe avec un token
     * Route : POST /api/user/reset-password
     */
    public function resetPassword(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['token']) || empty($data['password'])) {
                throw new \Exception('Token et mot de passe requis');
            }

            $user = $this->userRepository->findByResetToken($data['token']);

            if (!$user) {
                throw new \Exception('Token invalide ou expiré');
            }

            // Vérifier si le token n'est pas expiré (24h)
            $resetAt = $user->getResetAt();
            if (!$resetAt) {
                throw new \Exception('Date de réinitialisation invalide');
            }

            $resetAtDateTime = new \DateTime($resetAt);
            $now = new \DateTime();
            if ($resetAtDateTime->diff($now)->h >= 24) {
                throw new \Exception('Token expiré');
            }

            // Mettre à jour le mot de passe
            $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
            $user->setResetToken(null);
            $user->setResetAt(null);

            if (!$this->userRepository->update($user)) {
                throw new \Exception('Erreur lors de la mise à jour du mot de passe');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Mot de passe mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    } 
}