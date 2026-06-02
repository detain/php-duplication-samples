<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository abstraction layer.
 * Demonstrates how to abstract NoSQL document operations.
 */
interface DocumentRepositoryInterface
{
    /**
     * Find document by ID.
     *
     * @param string $id
     * @return DocumentDTO|null
     */
    public function findById(string $id): ?DocumentDTO;

    /**
     * Find documents by criteria.
     *
     * @param array $criteria
     * @param array $options
     * @return array<DocumentDTO>
     */
    public function find(array $criteria, array $options = []): array;

    /**
     * Insert a document.
     *
     * @param DocumentDTO $document
     * @return string Inserted ID
     */
    public function insert(DocumentDTO $document): string;

    /**
     * Update a document.
     *
     * @param string $id
     * @param DocumentDTO $document
     * @return bool
     */
    public function update(string $id, DocumentDTO $document): bool;

    /**
     * Delete a document.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;
}

/**
 * Document data transfer object.
 */
final readonly class DocumentDTO
{
    public function __construct(
        public ?string $id,
        public array $data,
        public ?string $rev = null,
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
    ) {}

    public static function fromArray(array $array): self
    {
        return new self(
            id: $array['id'] ?? $array['_id'] ?? null,
            data: $array['data'] ?? $array['_source'] ?? [],
            rev: $array['rev'] ?? $array['_rev'] ?? null,
            createdAt: isset($array['createdAt']) ? new \DateTime($array['createdAt']) : null,
            updatedAt: isset($array['updatedAt']) ? new \DateTime($array['updatedAt']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'data' => $this->data,
            'rev' => $this->rev,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}

/**
 * Abstract base document repository.
 */
abstract class AbstractDocumentRepository implements DocumentRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?DocumentDTO
    {
        $result = $this->doFindById($id);

        if ($result === null) {
            return null;
        }

        return DocumentDTO::fromArray($result);
    }

    /**
     * {@inheritdoc}
     */
    public function find(array $criteria, array $options = []): array
    {
        $results = $this->doFind($criteria, $options);

        return array_map(
            fn($result) => DocumentDTO::fromArray($result),
            $results
        );
    }

    /**
     * {@inheritdoc}
     */
    public function insert(DocumentDTO $document): string
    {
        $data = $document->data;
        $data['createdAt'] = date('Y-m-d H:i:s');
        $data['updatedAt'] = date('Y-m-d H:i:s');

        return $this->doInsert($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $id, DocumentDTO $document): bool
    {
        $data = $document->data;
        $data['updatedAt'] = date('Y-m-d H:i:s');

        return $this->doUpdate($id, $data, $document->rev);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $id): bool
    {
        return $this->doDelete($id);
    }

    /**
     * Perform find by ID operation.
     *
     * @param string $id
     * @return array|null
     */
    abstract protected function doFindById(string $id): ?array;

    /**
     * Perform find operation.
     *
     * @param array $criteria
     * @param array $options
     * @return array
     */
    abstract protected function doFind(array $criteria, array $options): array;

    /**
     * Perform insert operation.
     *
     * @param array $data
     * @return string
     */
    abstract protected function doInsert(array $data): string;

    /**
     * Perform update operation.
     *
     * @param string $id
     * @param array $data
     * @param string|null $rev
     * @return bool
     */
    abstract protected function doUpdate(string $id, array $data, ?string $rev = null): bool;

    /**
     * Perform delete operation.
     *
     * @param string $id
     * @return bool
     */
    abstract protected function doDelete(string $id): bool;
}

/**
 * Factory for creating document repositories.
 */
final class DocumentRepositoryFactory
{
    public static function create(string $type, array $config = []): DocumentRepositoryInterface
    {
        return match ($type) {
            'mongodb' => new \App\Database\NoSQL\MongoDocumentRepository(
                $config['database']
            ),
            'elasticsearch' => new \App\Database\NoSQL\ElasticsearchDocumentRepository(
                $config['client']
            ),
            'couchdb' => new \App\Database\NoSQL\CouchDBDocumentRepository(
                $config['client']
            ),
            default => throw new \RuntimeException("Unknown document type: {$type}"),
        };
    }
}
