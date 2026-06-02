<?php
declare(strict_types=1);

namespace App\Database\Custom;

use PDO;
use PDOException;
use RuntimeException;

final class TransactionManager
{
    private PDO $pdo;
    private bool $inTransaction = false;
    private array $savepoints = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function beginTransaction(): bool
    {
        if ($this->inTransaction) {
            $savepointName = 'savepoint_' . count($this->savepoints);
            $this->savepoints[] = $savepointName;

            try {
                $this->pdo->exec("SAVEPOINT {$savepointName}");
                return true;
            } catch (PDOException $e) {
                throw new RuntimeException(
                    'Failed to create savepoint: ' . $e->getMessage(),
                    0,
                    $e
                );
            }
        }

        try {
            $this->inTransaction = $this->pdo->beginTransaction();
            return $this->inTransaction;
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Failed to begin transaction: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function commit(): bool
    {
        if (!empty($this->savepoints)) {
            array_pop($this->savepoints);
            return true;
        }

        if (!$this->inTransaction) {
            return false;
        }

        try {
            $this->inTransaction = !$this->pdo->commit();
            return !$this->inTransaction;
        } catch (PDOException $e) {
            $this->rollBack();
            throw new RuntimeException(
                'Failed to commit transaction: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function rollBack(): bool
    {
        if (!empty($this->savepoints)) {
            $savepointName = array_pop($this->savepoints);

            try {
                $this->pdo->exec("ROLLBACK TO SAVEPOINT {$savepointName}");
                return true;
            } catch (PDOException $e) {
                throw new RuntimeException(
                    'Failed to rollback to savepoint: ' . $e->getMessage(),
                    0,
                    $e
                );
            }
        }

        if (!$this->inTransaction) {
            return false;
        }

        try {
            $this->inTransaction = false;
            return $this->pdo->rollBack();
        } catch (PDOException $e) {
            $this->inTransaction = false;
            throw new RuntimeException(
                'Failed to rollback transaction: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function isActive(): bool
    {
        return $this->inTransaction || !empty($this->savepoints);
    }

    public function executeInTransaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this->pdo);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }
}
