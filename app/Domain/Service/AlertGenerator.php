<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;

class AlertGenerator
{
    public function __construct(
        private BudgetManagementService $budgetService,
        private ExpenseRepositoryInterface $expenseRepository,
    ) {}

    public function generate(User $user, int $year, int $month): array
    {
        $alerts = [];
        $categoryBudgets = $this->budgetService->getBudgets();
        $totals = $this->expenseRepository->getTotalsByCategory($user->getId(), $year, $month);

        /* var_dump($totals, $categoryBudgets);
        exit; */

        foreach ($categoryBudgets as $category => $budget) {
            $spent = $totals[$category] ?? 0;
            if ($spent > $budget) {
                $diff = number_format($spent - $budget, 2);
                $alerts[] = "⚠ {$category} budget exceeded by {$diff} €";
            }
        }

        return $alerts;
    }
}
