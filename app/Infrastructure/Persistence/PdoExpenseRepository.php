<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * @throws Exception
     */
    public function find(int $id): ?Expense
    {
        $query = 'SELECT * FROM expenses WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return $this->createExpenseFromData($data);
    }

    public function save(Expense $expense): void
    {
        if ($expense->id === null) {
            $sql = 'INSERT INTO expenses (user_id, date, category, amount_cents, description) VALUES (:user_id, :date, :category, :amount_cents, :description)';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'user_id' => $expense->userId,
                'date' => $expense->date->format('Y-m-d'),
                'category' => $expense->category,
                'amount_cents' => $expense->amountCents,
                'description' => $expense->description,
            ]);

        } else {
            $sql = 'UPDATE expenses SET user_id=:user_id, date=:date, category=:category, amount_cents=:amount_cents, description=:description WHERE id=:id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id' => $expense->id,
                'user_id' => $expense->userId,
                'date' => $expense->date->format('Y-m-d'),
                'category' => $expense->category,
                'amount_cents' => $expense->amountCents,
                'description' => $expense->description,
            ]);
        }
    }


    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM expenses WHERE id=?');
        $statement->execute([$id]);
    }

    public function findBy(array $criteria, int $from, int $limit): array
    {
        $sql = 'SELECT * FROM expenses WHERE user_id = :user_id AND strftime(\'%Y\', date) = :year AND strftime(\'%m\', date) = :month ORDER BY date DESC LIMIT :limit OFFSET :from';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $criteria['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':year', (string)$criteria['year'], PDO::PARAM_STR);
        $stmt->bindValue(':month', str_pad((string)$criteria['month'], 2, '0', STR_PAD_LEFT), PDO::PARAM_STR);
        $stmt->bindValue(':from', $from, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = $this->createExpenseFromData($row);
        }

        return $results;
    }

    public function countBy(array $criteria): int
    {
        $sql = 'SELECT COUNT(*) FROM expenses WHERE user_id = :user_id AND strftime(\'%Y\', date) = :year AND strftime(\'%m\', date) = :month';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $criteria['user_id'],
            'year' => (string)$criteria['year'],
            'month' => str_pad((string)$criteria['month'], 2, '0', STR_PAD_LEFT)
        ]);
        return (int)$stmt->fetchColumn();
    }

    public function listExpenditureYears(User $user): array
    {
        $sql = 'SELECT DISTINCT strftime(\'%Y\', date) as year FROM expenses WHERE user_id = :user_id ORDER BY year DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $user->id]);
        return array_map(fn($row) => (int)$row['year'], $stmt->fetchAll());
    }

    public function sumAmountsByCategory(array $criteria): array
    {
        // TODO: Implement sumAmountsByCategory() method.
        return [];
    }

    public function averageAmountsByCategory(array $criteria): array
    {
        // TODO: Implement averageAmountsByCategory() method.
        return [];
    }

    public function sumAmounts(array $criteria): float
    {
        // TODO: Implement sumAmounts() method.
        return 0;
    }

    /**
     * @throws Exception
     */
    private function createExpenseFromData(mixed $data): Expense
    {
        return new Expense(
            $data['id'],
            $data['user_id'],
            new DateTimeImmutable($data['date']),
            $data['category'],
            $data['amount_cents'],
            $data['description'],
        );
    }

}
