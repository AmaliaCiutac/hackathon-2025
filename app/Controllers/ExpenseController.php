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
        $userId = $this->getLoggedInUserId();
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $query = $request->getQueryParams();
        $page = max((int)($query['page'] ?? 1), 1);
        $pageSize = self::PAGE_SIZE;

        $year = (int)($query['year'] ?? date('Y'));
        $month = (int)($query['month'] ?? date('n'));

        $user = new \App\Domain\Entity\User($userId, '', '', new \DateTimeImmutable());

        $expenses = $this->expenseService->list($user, $year, $month, $page, $pageSize);
        $totalCount = $this->expenseService->count($user, $year, $month);
        $years = $this->expenseService->listYears($user);
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        return $this->render($response, 'expenses/index.twig', [
            'expenses' => $expenses,
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $totalCount,
            'year' => $year,
            'month' => $month,
            'availableYears' => $years,
            'months' => $months
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $userId = $this->getLoggedInUserId();
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $categories = ['food', 'transport', 'housing', 'utilities', 'entertainment', 'other'];

        $queryParams = $request->getQueryParams();
        $values = [
            'date' => $queryParams['date'] ?? date('Y-m-d'),
            'category' => $queryParams['category'] ?? '',
            'amount' => $queryParams['amount'] ?? '',
            'description' => $queryParams['description'] ?? '',
        ];

        $errors = $queryParams['errors'] ?? [];

        return $this->render($response, 'expenses/create.twig', [
            'categories' => $categories,
            'values' => $values,
            'errors' => $errors,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $userId = $this->getLoggedInUserId();
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $postData = (array)$request->getParsedBody();

        $date = trim($postData['date'] ?? '');
        $category = trim($postData['category'] ?? '');
        $amount = trim($postData['amount'] ?? '');
        $description = trim($postData['description'] ?? '');

        $errors = [];

        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) > strtotime(date('Y-m-d'))) {
            $errors['date'] = 'Date must be today or earlier.';
        }

        $categories = ['food', 'transport', 'housing', 'utilities', 'entertainment', 'other'];
        if (!$category || !in_array($category, $categories, true)) {
            $errors['category'] = 'Please select a valid category.';
        }

        if (!is_numeric($amount) || (float)$amount <= 0) {
            $errors['amount'] = 'Amount must be a positive number.';
        }

        if (!$description) {
            $errors['description'] = 'Description cannot be empty.';
        }

        if ($errors) {
            $query = http_build_query([
                'date' => $date,
                'category' => $category,
                'amount' => $amount,
                'description' => $description,
                'errors' => json_encode($errors),
            ]);
            return $response
                ->withHeader('Location', '/expenses/create?' . $query)
                ->withStatus(302);
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
            $query = http_build_query([
                'date' => $date,
                'category' => $category,
                'amount' => $amount,
                'description' => $description,
                'errors' => json_encode(['general' => 'Failed to save expense. Please try again.']),
            ]);
            return $response
                ->withHeader('Location', '/expenses/create?' . $query)
                ->withStatus(302);
        }
        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to display the edit expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not

        $expense = ['id' => 1];

        return $this->render($response, 'expenses/edit.twig', ['expense' => $expense, 'categories' => []]);
    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to update an existing expense

        // Hints:
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - get the new values from the request and prepare for update
        // - update the expense entity with the new values
        // - rerender the "expenses.edit" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        return $response;
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to delete an existing expense

        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - call the repository method to delete the expense
        // - redirect to the "expenses.index" page

        return $response;
    }
}
