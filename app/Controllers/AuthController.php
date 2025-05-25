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
        $userData = $request->getParsedBody() ?? [];
        $username = trim($userData['username'] ?? '');
        $password = trim($userData['password'] ?? '');

        $errors = $this->checkSignupUsernamePassword($username, $password);

        if (!empty($errors)) {
            $this->logger->warning("Register failed. Try again!");
            return $this->showSignupPage($response, $username, $errors);
        }

        try {
            $this->authService->register($username, $password);
            $this->logger->info("New member joined: Welcome {$username}!");

            return $response->withHeader('Location', '/login')->withStatus(302);
        } catch (\RuntimeException $e) {
            $this->logger->warning("Signup failed for {$username}: " . $e->getMessage());
            $errors['general'] = "We couldn't create your account. Maybe that username is taken?";
            return $this->showSignupPage($response, $username, $errors);
        }
    }

    private function checkSignupUsernamePassword(string $username, string $password): array
    {
        $errors = [];

        if (strlen($username) < 4) {
            $errors['username'] = 'Choose a username with at least 4 letters';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'Make your password stronger (8+ characters)';
        } elseif (!preg_match('/\d/', $password)) {
            $errors['password'] = 'Add a numbe rto make your password more secure';
        }

        return $errors;
    }

    //if the registration doesn't work it re-renders the page
    private function showSignupPage(Response $response, string $username, array $errors): Response
    {
        return $this->render($response, 'auth/register.twig', ['username' => $username, 'errors' => $errors, 'general'=> $errors]);
    }
    public function showLogin(Request $request, Response $response): Response
    {

        return $this->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        $loginAttempt = $request->getParsedBody() ?? [];
        $username = trim($loginAttempt['username'] ?? '');
        $password = trim($loginAttempt['password'] ?? '');

        if (!$this->authService->attempt($username, $password)) {
            $this->logger->warning("Login failed. Try again");

            return $this->render($response, 'auth/login.twig', ['username' => $username, 'errors' => ['credentials' => "Login failed. Check your username and password."]]);
        }
        $this->logger->info("Welcome back {$username}!");
        return $response->withHeader('Location', '/')->withStatus(302);
    }
    public function logout(Request $request, Response $response): Response
    {
        $this->forgetUserSession();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
    private function forgetUserSession(): void
    {
        $_SESSION = [];
        if (session_id()) {session_destroy();}
    }}

