<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\ExpenseService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 20;

    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // Check if user is logged in
        $userId = $this->getLoggedInUserId();
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $query = $request->getQueryParams();
        $page = max((int)($query['page'] ?? 1), 1);  // Ensure page is at least 1
        $currentYear = (int)($query['year'] ?? date('Y'));
        $currentMonth = (int)($query['month'] ?? date('n'));

        $user = new \App\Domain\Entity\User($userId, '', '', new \DateTimeImmutable());

        $expenses = $this->expenseService->list($user, $currentYear, $currentMonth, $page, self::PAGE_SIZE);
        $totalExpenses = $this->expenseService->count($user, $currentYear, $currentMonth);
        $availableYears = $this->expenseService->listYears($user);

        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];


        return $this->render($response, 'expenses/index.twig', [
            'expenses' => $expenses,
            'page' => $page,
            'pageSize' => self::PAGE_SIZE,
            'total' => $totalExpenses,
            'year' => $currentYear,
            'month' => $currentMonth,
            'availableYears' => $availableYears,
            'months' => $months
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        if (!$userId = $this->getLoggedInUserId()) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $categories = [
            'food',
            'transport',
            'housing',
            'utilities',
            'entertainment',
            'other'
        ];
        $defaultValues = [
            'date' => date('Y-m-d'),
            'category' => '',
            'amount' => '',
            'description' => ''
        ];

        $formValues = array_merge($defaultValues, $request->getQueryParams());

        $errors = $request->getQueryParams()['errors'] ?? [];

        return $this->render($response, 'expenses/create.twig', [
            'categories' => $categories,
            'values' => $formValues,
            'errors' => $errors
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        if (!$userId = $this->getLoggedInUserId()) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $data = $request->getParsedBody() ?? [];
        $date = trim($data['date'] ?? '');
        $category = trim($data['category'] ?? '');
        $amount = trim($data['amount'] ?? '');
        $description = trim($data['description'] ?? '');


        $errors = $this->validateExpenseData($date, $category, $amount, $description);

        if (!empty($errors)) {
            return $this->redirectBackWithErrors($response, $date, $category, $amount, $description, $errors);
        }

        try {
            $user = new \App\Domain\Entity\User($userId, '', '', new \DateTimeImmutable());
            $this->expenseService->create(
                $user,
                (float)$amount,
                $description,
                new \DateTimeImmutable($date),
                $category
            );
        } catch (\Throwable $e) {
            $errors = ['general' => 'Failed to save expense. Please try again.'];
            return $this->redirectBackWithErrors($response, $date, $category, $amount, $description, $errors);
        }

        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }

    private function validateExpenseData(string $date, string $category, string $amount, string $description): array
    {
        $errors = [];
        $validCategories = ['food', 'transport', 'housing', 'utilities', 'entertainment', 'other'];

        if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $errors['date'] = 'Invalid date format. Use YYYY-MM-DD.';
        } elseif (strtotime($date) > time()) {
            $errors['date'] = 'Date cannot be in the future.';
        }

        if (empty($category) || !in_array($category, $validCategories, true)) {
            $errors['category'] = 'Please select a valid category.';
        }

        if (!is_numeric($amount) || (float)$amount <= 0) {
            $errors['amount'] = 'Amount must be a positive number.';
        }

        if (empty($description)) {
            $errors['description'] = 'Description cannot be empty.';
        }

        return $errors;
    }

    private function redirectBackWithErrors(
        Response $response,
        string $date,
        string $category,
        string $amount,
        string $description,
        array $errors
    ): Response {
        $query = http_build_query([
            'date' => $date,
            'category' => $category,
            'amount' => $amount,
            'description' => $description,
            'errors' => json_encode($errors)
        ]);

        return $response
            ->withHeader('Location', '/expenses/create?' . $query)
            ->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {

        $userId = $this->getLoggedInUserId();
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $expenseId = (int)($routeParams['id'] ?? 0);
        if ($expenseId <= 0) {
            return $response->withStatus(404);
        }
        $expense = $this->expenseService->find($expenseId);
        if (!$expense) {
            return $response->withStatus(404);
        }

        if ($expense->userId !== $userId) {
            return $response->withStatus(403);
        }

        $categories = [
            'food',
            'transport',
            'housing',
            'utilities',
            'entertainment',
            'other'
        ];

        return $this->render($response, 'expenses/edit.twig', [
            'expense' => $expense,
            'categories' => $categories,
            'errors' => $request->getQueryParams()['errors'] ?? []
        ]);
    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {

        $userId = $this->getLoggedInUserId();
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $expenseId = (int)($routeParams['id'] ?? 0);
        if ($expenseId <= 0) {
            return $response->withStatus(404);
        }
        $expense = $this->expenseService->find($expenseId);
        if (!$expense) {
            return $response->withStatus(404);
        }


        if ($expense->userId !== $userId) {
            return $response->withStatus(403);
        }


        $data = $request->getParsedBody() ?? [];
        $date = trim($data['date'] ?? '');
        $category = trim($data['category'] ?? '');
        $amount = trim($data['amount'] ?? '');
        $description = trim($data['description'] ?? '');


        $errors = $this->validateExpenseData($date, $category, $amount, $description);

        if (!empty($errors)) {
            $query = http_build_query([
                'errors' => json_encode($errors),
                'date' => $date,
                'category' => $category,
                'amount' => $amount,
                'description' => $description
            ]);
            return $response
                ->withHeader('Location', '/expenses/' . $expenseId . '/edit?' . $query)
                ->withStatus(302);
        }

        try {
            $this->expenseService->update(
                $expense,
                (float)$amount,
                $description,
                new \DateTimeImmutable($date),
                $category
            );

            return $response->withHeader('Location', '/expenses')->withStatus(302);
        } catch (\Throwable $e) {
            $errors = ['general' => 'Failed to update expense. Please try again.'];
            $query = http_build_query(['errors' => json_encode($errors)]);
            return $response
                ->withHeader('Location', '/expenses/' . $expenseId . '/edit?' . $query)
                ->withStatus(302);
        }
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        $userId = $this->getLoggedInUserId();

        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $expenseId = (int)($routeParams['id'] ?? 0);
        if ($expenseId <= 0) {
            return $response->withStatus(404);
        }

        $expense = $this->expenseService->find($expenseId);
        if (!$expense) {
            return $response->withStatus(404);
        }

        // Check ownership
        if ($expense->userId !== $userId) {
            return $response->withStatus(403);
        }

        try {

            $this->expenseService->delete($expenseId);
            return $response->withHeader('Location', '/expenses')->withStatus(302);
        } catch (\Throwable $e) {
            return $response->withHeader('Location', '/expenses')->withStatus(302);
        }
    }


}
