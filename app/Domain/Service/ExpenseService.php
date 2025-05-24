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
        $criteria = [
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month
        ];

        $offset = ($pageNumber - 1) * $pageSize;
        return $this->expenses->findBy($criteria, $offset, $pageSize);
    }

    public function count(User $user, int $year, int $month): int
    {
        $criteria = [
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month
        ];

        return $this->expenses->countBy($criteria);
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

        if ($date > new \DateTimeImmutable('today')) {
            throw new \InvalidArgumentException('Date cannot be in the future.');
        }

        $categories = ['food', 'transport', 'housing', 'utilities', 'entertainment', 'other'];
        if (!in_array($category, $categories, true)) {
            throw new \InvalidArgumentException('Invalid category.');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }

        if (trim($description) === '') {
            throw new \InvalidArgumentException('Description cannot be empty.');
        }
        $amountCents = (int)round($amount * 100);

        $expense = new Expense(
            null,
            $user->id,
            $date,
            $category,
            $amountCents,
            $description
        );

        $this->expenses->save($expense);
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to update expense entity, perform validation, and persist
    }

    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails

        return 0; // number of imported rows
    }
}
