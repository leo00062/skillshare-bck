<?php

declare(strict_types=1);

namespace App\repository;

use App\core\Database;
use App\model\User;
use PDO;

class UserRepository
{
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getConnexion();
    }

    public function save(User $user): bool {
        // requête préparée obligatoire!!!
        $stmt = $this->pdo->prepare("INSERT INTO `user`(username, avatar, email, `role`, password_hash, created_at)
        VALUES(?, ?, ?, ?, ?, ?);");


        return $stmt->execute([
            $user->getUsername(),
            $user->getAvatar(),
            $user->getEmail(),
            json_encode($user->getRole()),
            $user->getPassword(),
            $user->getCreatedAt()
        ]);
    }

        public function saveAvatar(string $avatarFileName): bool {
        // requête préparée obligatoire!!!
        $stmt = $this->pdo->prepare("UPDATE `user` SET avatar VALUES = ?;");


        return $stmt->execute([
            $avatarFileName
        ]);
    }
}
