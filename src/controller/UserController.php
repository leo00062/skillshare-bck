<?php

declare(strict_types=1);

namespace App\controller;

use App\core\attribute\Route;
use Exception;

class UserController
{
    #[Route('/api/register', 'POST')]
    public function register() 
    {

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) throw new Exception('Json invalide');

        echo json_encode([
            'success' => true,
            'message' => 'Inscription réussie ! Veuillez vérifier vos email.' . 
            $data
        ]);
    }
}
