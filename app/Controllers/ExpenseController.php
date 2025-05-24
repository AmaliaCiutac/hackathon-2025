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

        return $this->render($response, 'expenses/index.twig', [
            'expenses' => $expenses,
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $totalCount,
            'year' => $year,
            'month' => $month,
            'availableYears' => $years
        ]);
    }


    public function create(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the create expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view

        return $this->render($response, 'expenses/create.twig', ['categories' => []]);
    }

    public function store(Request $request, Response $response): Response
    {
        // TODO: implement this action method to create a new expense

        // Hints:
        // - use the session to get the current user ID
        // - use the expense service to create and persist the expense entity
        // - rerender the "expenses.create" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        return $response;
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
