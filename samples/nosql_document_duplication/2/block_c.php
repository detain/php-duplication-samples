<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using RavenDB.
 * Demonstrates "Insert with auto-generated ID" in RavenDB style.
 */
final class RavenDBDocumentRepository
{
    private \RavenDB\Client\Http\ServerClient $client;
    private string $database;

    public function __construct(\RavenDB\Client\Http\ServerClient $client, string $database = 'documents')
    {
        $this->client = $client;
        $this->database = $database;
    }

    /**
     * Insert document with auto-generated ID.
     *
     * @param array $data
     * @return string
     */
    public function insert(array $data): string
    {
        $command = new \RavenDB\Client\Commands\PutDocumentCommand(
            null,
            $data
        );

        $result = $this->client->execute($command, $this->database);

        return $result['Id'];
    }

    /**
     * Find document by ID.
     *
     * @param string $id
     * @return array|null
     */
    public function findById(string $id): ?array
    {
        $command = new \RavenDB\Client\Commands\GetDocumentCommand([$id]);

        try {
            $result = $this->client->execute($command, $this->database);

            if (empty($result['Results'])) {
                return null;
            }

            return $result['Results'][0];
        } catch (\RavenDB\Client\Exceptions\DocumentNotFoundException $e) {
            return null;
        }
    }

    /**
     * Store document (insert or update).
     *
     * @param string|null $id
     * @param array $data
     * @return string
     */
    public function store(?string $id, array $data): string
    {
        $command = new \RavenDB\Client\Commands\PutDocumentCommand(
            $id,
            $data
        );

        $result = $this->client->execute($command, $this->database);

        return $result['Id'];
    }

    /**
     * Delete document.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $command = new \RavenDB\Client\Commands\DeleteDocumentCommand($id);

        try {
            $this->client->execute($command, $this->database);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
