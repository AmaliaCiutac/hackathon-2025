<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

abstract class BaseController
{
    public function __construct(
        protected Twig $view,
    ) {}

    //renders a twig page
    protected function render(Response $response, string $template, array $data = []): Response
    {
        return $this->view->render($response, $template, $data);
    }

    //gets the user id from the current session
    protected function getLoggedInUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    // TODO: add here any common controller logic and use in concrete controllers
}
