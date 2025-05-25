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
        // Get the user's signup information
        $userData = $request->getParsedBody() ?? [];
        $username = trim($userData['username'] ?? '');
        $password = trim($userData['password'] ?? '');

        // Check if the information looks good
        $errors = $this->checkSignupInfo($username, $password);

        // If we found any problems, show them how to fix it
        if (!empty($errors)) {
            $this->logger->warning("Register failed. Try again!");
            return $this->showSignupPage($response, $username, $errors);
        }

        // Try to create their account
        try {
            $this->authService->register($username, $password);
            $this->logger->info("New member joined: Welcome {$username}!");

            // Send them to the login page to try their new account
            return $response->withHeader('Location', '/login')->withStatus(302);
        } catch (\RuntimeException $e) {
            $this->logger->warning("Signup failed for {$username}: " . $e->getMessage());
            $errors['general'] = "We couldn't create your account. Maybe that username is taken?";
            return $this->showSignupPage($response, $username, $errors);
        }
    }

    private function checkSignupInfo(string $username, string $password): array
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

    private function showSignupPage(Response $response, string $username, array $errors): Response
    {
        return $this->render($response, 'auth/register.twig', [
            'username' => $username,
            'errors' => $errors,
            'general'=> $errors
        ]);
    }



    public function showLogin(Request $request, Response $response): Response
    {
        // Just show the friendly login page
        return $this->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        // Get what the user typed in
        $loginAttempt = $request->getParsedBody() ?? [];
        $username = trim($loginAttempt['username'] ?? '');
        $password = trim($loginAttempt['password'] ?? '');

        // Check if their login information is correct
        if (!$this->authService->attempt($username, $password)) {
            $this->logger->warning("Login failed. Try again");

            return $this->render($response, 'auth/login.twig', [
                'username' => $username,
                'errors' => ['credentials' => "That didn't work. Check your username and password."]
            ]);
        }

        // Welcome them back!
        $this->logger->info("Welcome back {$username}!");
        return $response->withHeader('Location', '/')->withStatus(302);
    }



    public function logout(Request $request, Response $response): Response
    {
        // Clear their session and say goodbye
        $this->forgetUserSession();

        // Send them back to the login page
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    private function forgetUserSession(): void
    {
        // Clean up everything about their session
        $_SESSION = [];
        if (session_id()) {
            session_destroy();
        }
    }

}
