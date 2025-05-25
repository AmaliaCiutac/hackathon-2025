<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function register(string $username, string $password): User
    {$existingUser = $this->users->findByUsername($username);
        if ($existingUser !== null) { throw new \RuntimeException("User already exists");}

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $user = new User(null, $username, $hashedPassword, new \DateTimeImmutable());
        $this->users->save($user);
        return $user;
    }

    public function attempt(string $username, string $password): bool
    {
        $user = $this->users->findByUsername($username);
        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user->passwordHash)) {
            return false;
        }
        $_SESSION['user_id'] = $user->id;
        return true;
    }
}
