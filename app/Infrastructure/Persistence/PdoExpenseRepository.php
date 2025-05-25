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
            $stmt->execute(['user_id' => $expense->userId,
                'date' => $expense->date->format('Y-m-d'),
                'category' => $expense->category,
                'amount_cents' => $expense->amountCents,
                'description' => $expense->description,
            ]);

        } else {
            $sql = 'UPDATE expenses SET user_id=:user_id, date=:date, category=:category, amount_cents=:amount_cents, description=:description WHERE id=:id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $expense->id,
                'user_id' => $expense->userId,
                'date' => $expense->date->format('Y-m-d'),
                'category' => $expense->category,
                'amount_cents' => $expense->amountCents,
                'description' => $expense->description,]);
        }
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM expenses WHERE id=?');
        $statement->execute([$id]);
    }

    public function findBy(array $criteria, int $from = 0, int $limit = 0): array
    {
        $where = [];
        $params = [];

        foreach ($criteria as $field => $value) {
            if ($field === 'date') {

                $where[] = 'date = :date';
                $params['date'] = $value;
            } elseif ($field === 'amount_cents') {

                $where[] = 'amount_cents = :amount_cents';
                $params['amount_cents'] = $value;
            } elseif ($field === 'year') {

                $where[] = 'strftime(\'%Y\', date) = :year';
                $params['year'] = (string)$value;
            } elseif ($field === 'month') {
                $where[] = 'strftime(\'%m\', date) = :month';
                $params['month'] = str_pad((string)$value, 2, '0', STR_PAD_LEFT);
            } else {

                $where[] = "$field = :$field";
                $params[$field] = $value;
            }
        }

        $sql = 'SELECT * FROM expenses';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY date DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT :limit OFFSET :from';
            $params['limit'] = $limit;
            $params['from'] = $from;
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $paramType);
        }

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

    public function getTotalForMonth(int $userId, int $year, int $month): float
    {
        $sql = 'SELECT SUM(amount_cents) FROM expenses WHERE user_id = :user_id AND strftime(\'%Y\', date) = :year AND strftime(\'%m\', date) = :month';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'year' => (string)$year,
            'month' => str_pad((string)$month, 2, '0', STR_PAD_LEFT)
        ]);
        return (float)($stmt->fetchColumn() / 100);
    }

    public function getTotalsByCategory(int $userId, int $year, int $month): array
    {
        $sql = 'SELECT category, SUM(amount_cents) as total FROM expenses WHERE user_id = :user_id AND strftime(\'%Y\', date) = :year AND strftime(\'%m\', date) = :month GROUP BY category';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'year' => (string)$year,
            'month' => str_pad((string)$month, 2, '0', STR_PAD_LEFT)
        ]);

        $result = [];
        while ($row = $stmt->fetch()) {
            $result[$row['category']] = (float)($row['total'] / 100);
        }
        return $result;
    }

    public function getAveragesByCategory(int $userId, int $year, int $month): array
    {
        $sql = 'SELECT category, AVG(amount_cents) as average FROM expenses WHERE user_id = :user_id AND strftime(\'%Y\', date) = :year AND strftime(\'%m\', date) = :month GROUP BY category';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId,
            'year' => (string)$year,
            'month' => str_pad((string)$month, 2, '0', STR_PAD_LEFT)]);

        $result = [];
        while ($row = $stmt->fetch()) {
            $result[$row['category']] = (float)($row['average'] / 100);
        }
        return $result;
    }
    /*** @throws Exception */
    private function createExpenseFromData(mixed $data): Expense
    {
        return new Expense($data['id'], $data['user_id'],
            new DateTimeImmutable($data['date']),
            $data['category'],
            $data['amount_cents'],
            $data['description'],);
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }
}
