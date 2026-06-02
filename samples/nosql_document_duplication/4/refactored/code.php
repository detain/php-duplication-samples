<?php
declare(strict_types=1);

namespace App\Database\NoSQL\Refactored;

/**
 * Cascade rule definition.
 */
final readonly class CascadeRule
{
    public function __construct(
        public string $collection,
        public string $field,
        public string $cascade,
        public ?string $key = null,
    ) {}

    public static function delete(string $collection, string $field, ?string $key = null): self
    {
        return new self($collection, $field, 'delete', $key);
    }

    public static function nullify(string $collection, string $field): self
    {
        return new self($collection, $field, 'nullify');
    }
}

/**
 * Interface for cascade delete repositories.
 */
interface CascadeDeleteRepositoryInterface
{
    /**
     * Delete document with cascade.
     *
     * @param string $id
     * @param array $cascadeRules
     * @return bool
     */
    public function deleteWithCascade(string $id, array $cascadeRules = []): bool;

    /**
     * Find document by ID.
     *
     * @param string $id
     * @return array|null
     */
    public function findById(string $id): ?array;
}

/**
 * Abstract base for cascade delete repositories.
 */
abstract class AbstractCascadeDeleteRepository implements CascadeDeleteRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function deleteWithCascade(string $id, array $cascadeRules = []): bool
    {
        $document = $this->findById($id);

        if ($document === null) {
            return false;
        }

        $this->executeCascade($document, $cascadeRules);

        return $this->executeDelete($id);
    }

    /**
     * Execute cascade operations.
     *
     * @param array $document
     * @param array $cascadeRules
     * @return void
     */
    protected function executeCascade(array $document, array $cascadeRules): void
    {
        foreach ($cascadeRules as $rule) {
            $rule = $rule instanceof CascadeRule ? $rule : CascadeRule::delete(
                $rule['collection'] ?? '',
                $rule['field'] ?? '',
                $rule['key'] ?? null
            );

            $targetValue = $document[$rule->field] ?? null;

            if ($targetValue !== null) {
                $this->executeCascadeAction($rule, $targetValue);
            }
        }
    }

    /**
     * Execute a single cascade action.
     *
     * @param CascadeRule $rule
     * @param mixed $targetValue
     * @return void
     */
    abstract protected function executeCascadeAction(CascadeRule $rule, mixed $targetValue): void;

    /**
     * Execute the actual delete.
     *
     * @param string $id
     * @return bool
     */
    abstract protected function executeDelete(string $id): bool;
}

/**
 * Factory for cascade delete repositories.
 */
final class CascadeDeleteRepositoryFactory
{
    public static function create(string $type, array $config = []): CascadeDeleteRepositoryInterface
    {
        return match ($type) {
            'mongodb' => new \App\Database\NoSQL\MongoCascadeDeleteRepository(
                $config['database']
            ),
            'elasticsearch' => new \App\Database\NoSQL\ElasticsearchCascadeDeleteRepository(
                $config['client']
            ),
            'couchbase' => new \App\Database\NoSQL\CouchbaseCascadeDeleteRepository(
                $config['client']
            ),
            'arangodb' => new \App\Database\NoSQL\ArangoDBCascadeDeleteRepository(
                $config['client']
            ),
            'dynamodb' => new \App\Database\NoSQL\DynamoDBCascadeDeleteRepository(
                $config['client']
            ),
            'firestore' => new \App\Database\NoSQL\FirestoreCascadeDeleteRepository(
                $config['client']
            ),
            'cosmosdb' => new \App\Database\NoSQL\CosmosDBCascadeDeleteRepository(
                $config['client']
            ),
            'marklogic' => new \App\Database\NoSQL\MarkLogicCascadeDeleteRepository(
                $config['client']
            ),
            default => throw new \RuntimeException("Unknown document type: {$type}"),
        };
    }
}
