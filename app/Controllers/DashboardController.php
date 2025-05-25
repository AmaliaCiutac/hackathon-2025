<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Domain\Service\AlertGenerator;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Entity\User;

class DashboardController extends BaseController
{
    //dependencies
    public function __construct(
        Twig $view,
        private ExpenseRepositoryInterface $expenseRepo,
        private AlertGenerator $alertGenerator) {
        parent::__construct($view);}

    public function index(Request $request, Response $response): Response
    {
        $userId = $this->getLoggedInUserId();
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        //URL for the filter
        $params = $request->getQueryParams();
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('n');

        $year = isset($params['year']) ? (int)$params['year'] : $currentYear;
        $month = isset($params['month']) ? (int)$params['month'] : $currentMonth;

        $user = new User($userId, '', '', new \DateTimeImmutable());

        //gets only the available years
        $years = $this->expenseRepo->listExpenditureYears($user);
        $alerts = ($year === $currentYear && $month === $currentMonth) ? $this->alertGenerator->generate($user, $year, $month) : [];

        $total = $this->expenseRepo->getTotalForMonth($userId, $year, $month);
        $totalsRaw = $this->expenseRepo->getTotalsByCategory($userId, $year, $month);
        $averagesRaw = $this->expenseRepo->getAveragesByCategory($userId, $year, $month);

        $totalsSum = array_sum($totalsRaw);
        $averagesSum = array_sum($averagesRaw);

        $totalsPerCategory = [];
        foreach ($totalsRaw as $category => $amount) {
            $totalsPerCategory[$category] = ['value' => $amount, 'percentage' => $totalsSum > 0 ? round(($amount / $totalsSum) * 100, 2) : 0
            ];
        }

        $averagesPerCategory = [];
        foreach ($averagesRaw as $category => $amount) {
            $averagesPerCategory[$category] = ['value' => $amount, 'percentage' => $averagesSum > 0 ? round(($amount / $averagesSum) * 100, 2) : 0
            ];
        }

        return $this->render($response, 'dashboard.twig', ['alerts' => $alerts,
            'totalForMonth' => ['value' => $total, 'month' => $month, 'year' => $year],
            'totalsForCategories' => $totalsPerCategory,
            'averagesForCategories' => $averagesPerCategory,
            'years' => $years,
            'selectedYear' => $year,
            'selectedMonth' => $month
        ]);

    }
}
