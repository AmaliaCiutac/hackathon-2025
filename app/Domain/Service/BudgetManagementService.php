<?php

namespace App\Domain\Service;

class BudgetManagementService
{
    private array $manageBudget;

    public function __construct()
    {
        $json = getenv('MANAGE_BUDGETS') ?: '{}';
        $this->manageBudget = json_decode($json, true);
    }

    public function getBudgets(): array
    {
        return $this->manageBudget;
    }

    public function getBudgetForCategory(string $category): ?float
    {
        return $this->manageBudget[$category] ?? null;
    }
}