<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using MongoDB.
 * Demonstrates "Delete with cascade" in MongoDB style.
 */
final class MongoCascadeDeleteRepository
{
    private \MongoDB\Database $database;
    private string $collectionName;

    public function __construct(\MongoDB\Database $database, string $collectionName = 'documents')
    {
        $this->database = $database;
        $this->collectionName = $collectionName;
    }

    /**
     * Delete document with cascade to related documents.
     *
     * @param string $id
     * @param array $cascadeRules Rules for cascade deletion
     * @return bool
     */
    public function deleteWithCascade(string $id, array $cascadeRules = []): bool
    {
        $session = $this->database->client()->startSession();

        try {
            $session->startTransaction();

            $collection = $this->database->selectCollection($this->collectionName);

            $document = $collection->findOne(
                ['_id' => new \MongoDB\BSON\ObjectId($id)],
                ['session' => $session]
            );

            if ($document === null) {
                return false;
            }

            foreach ($cascadeRules as $rule) {
                $targetCollection = $this->database->selectCollection($rule['collection']);
                $targetField = $rule['field'];
                $targetValue = is_array($document) ? ($document[$targetField] ?? null) : null;

                if ($targetValue !== null) {
                    if ($rule['cascade'] === 'delete') {
                        $targetCollection->deleteMany(
                            [$targetField => $targetValue],
                            ['session' => $session]
                        );
                    } elseif ($rule['cascade'] === 'nullify') {
                        $targetCollection->updateMany(
                            [$targetField => $targetValue],
                            ['$set' => [$targetField => null]],
                            ['session' => $session]
                        );
                    }
                }
            }

            $collection->deleteOne(
                ['_id' => new \MongoDB\BSON\ObjectId($id)],
                ['session' => $session]
            );

            $session->commitTransaction();

            return true;
        } catch (\Exception $e) {
            if ($session->isInTransaction()) {
                $session->abortTransaction();
            }
            throw $e;
        } finally {
            $session->endSession();
        }
    }

    /**
     * Find document by ID.
     */
    public function findById(string $id): ?array
    {
        $collection = $this->database->selectCollection($this->collectionName);
        $result = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);

        if ($result === null) {
            return null;
        }

        return (array) $result;
    }
}
