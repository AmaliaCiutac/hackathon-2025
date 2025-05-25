<?php

namespace App\Domain\Service;

class BudgetManagementService
{
    private array $manageBudget;

    public function __construct()
    {//getting the budget limits from .env
        $json = $_ENV['MANAGE_BUDGETS'] ?? '{}';
        $this->manageBudget = json_decode($json, true);
    }

    public function getBudgets(): array
    {
        return $this->manageBudget;
    }}