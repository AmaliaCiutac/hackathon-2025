<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class AuthController extends BaseController
{
    public function __construct(
        Twig $view,
        private AuthService $authService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function showRegister(Request $request, Response $response): Response
    {
        // TODO: you also have a logger service that you can inject and use anywhere; file is var/app.log
        $this->logger->info('Register page requested');

        return $this->render($response, 'auth/register.twig');
    }

    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');
        $errors = [];

        // Validare username
        if (strlen($username) < 4) {
            $errors['username'] = 'Username must be at least 4 characters long.';
        }

        // Validare parola
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/\d/', $password)) {
            $errors['password'] = 'Password must contain at least one number.';
        }


        if (!empty($errors)) {
            $this->logger->warning("Validation failed during registration for $username");
            return $this->render($response, 'auth/register.twig', [
                'errors' => $errors,
                'username' => $username
            ]);
        }

        try {
            $this->authService->register($username, $password);
            $this->logger->info("New user registered: $username");
            return $response->withHeader('Location', '/login')->withStatus(302);
        } catch (\RuntimeException $e) {
            $this->logger->warning("Registration failed: " . $e->getMessage());
            return $this->render($response, 'auth/register.twig', [
                $errors['password and username'] = 'Invalid credentials',
                'username' => $username
            ]);
        }
    }



    public function showLogin(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');
        $errors = [];

        if (!$this->authService->attempt($username, $password)) {
            $this->logger->warning("Login failed for user: $username");
            $errors['credentials'] = 'Username or password incorrect.';
            return $this->render($response, 'auth/login.twig', [
                'errors' => $errors,
                'username' => $username
            ]);
        }
        $this->logger->info("User logged in: $username");
        return $response->withHeader('Location', '/')->withStatus(302);
    }



    public function logout(Request $request, Response $response): Response
    {
        $_SESSION = [];
        session_destroy();

        return $response->withHeader('Location', '/login')->withStatus(302);
    }

}
