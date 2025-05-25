<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Psr\Http\Message\UploadedFileInterface;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function list(User $user, int $year, int $month, int $pageNumber, int $pageSize): array
    {
        $searchCriteria = [
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month
        ];

        $offset = ($pageNumber - 1) * $pageSize;

        return $this->expenses->findBy($searchCriteria, $offset, $pageSize);
    }

    public function count(User $user, int $year, int $month): int
    {
        return $this->expenses->countBy([
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month
        ]);
    }

    public function listYears(User $user): array
    {
        return $this->expenses->listExpenditureYears($user);
    }
    public function create(
        User $user,
        float $amount,
        string $description,
        \DateTimeImmutable $date,
        string $category,
    ): void {
        $this->validateExpenseData($date, $category, $amount, $description);

        $expense = new Expense(
            null,
            $user->id,
            $date,
            $category,
            $this->convertToCents($amount),
            trim($description)
        );

        $this->expenses->save($expense);
    }

    private function validateExpenseData(
        \DateTimeImmutable $date,
        string $category,
        float $amount,
        string $description
    ): void {
        if ($date > new \DateTimeImmutable('today')) {
            throw new \InvalidArgumentException('Date cannot be in the future.');
        }

        $validCategories = ['food', 'transport', 'housing', 'utilities', 'entertainment', 'other'];
        if (!in_array($category, $validCategories, true)) {
            throw new \InvalidArgumentException('Invalid category.');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }

        if (empty(trim($description))) {
            throw new \InvalidArgumentException('Description cannot be empty.');
        }
    }

    private function convertToCents(float $amount): int
    {
        return (int)round($amount * 100);
    }

    public function find(int $id): ?Expense
    {
        return $this->expenses->find($id);
    }

    public function delete(int $id): void
    {
        $this->expenses->delete($id);
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        $this->validateExpenseData($date, $category, $amount, $description);

        $expense->date = $date;
        $expense->category = $category;
        $expense->amountCents = $this->convertToCents($amount);
        $expense->description = trim($description);

        $this->expenses->save($expense);
    }

    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails

        return 0; // number of imported rows
    }
}
