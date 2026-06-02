<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use mysqli_result;
use RuntimeException;

final class MysqliProceduralArticleRepository
{
    private mysqli $connection;

    public function __construct(string $host, string $username, string $password, string $database)
    {
        $this->connection = mysqli_connect($host, $username, $password, $database);
        if ($this->connection === false) {
            throw new RuntimeException('Connection failed: ' . mysqli_connect_error());
        }
        mysqli_set_charset($this->connection, 'utf8mb4');
    }

    public function search(string $query, int $limit = 20, int $offset = 0): array
    {
        $searchQuery = mysqli_real_escape_string($this->connection, $query);
        $limit = (int)$limit;
        $offset = (int)$offset;

        $sql = "SELECT id, title, slug, excerpt, content, author_id, published_at, status
                FROM articles
                WHERE (title LIKE '%{$searchQuery}%'
                   OR content LIKE '%{$searchQuery}%'
                   OR tags LIKE '%{$searchQuery}%')
                  AND status = 'published'
                ORDER BY published_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Search failed: ' . mysqli_error($this->connection));
        }

        $articles = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $articles[] = $row;
        }
        mysqli_free_result($result);

        return $articles;
    }

    public function searchByTag(string $tag, int $limit = 20): array
    {
        $tag = mysqli_real_escape_string($this->connection, $tag);

        $sql = "SELECT a.id, a.title, a.slug, a.excerpt, a.author_id, a.published_at
                FROM articles a
                WHERE a.tags LIKE '%{$tag}%'
                  AND a.status = 'published'
                ORDER BY a.published_at DESC
                LIMIT {$limit}";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Tag search failed: ' . mysqli_error($this->connection));
        }

        $articles = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $articles[] = $row;
        }
        mysqli_free_result($result);

        return $articles;
    }

    public function searchByAuthor(int $authorId, ?string $query = null): array
    {
        $authorId = (int)$authorId;
        $limit = 50;

        if ($query !== null && $query !== '') {
            $searchQuery = mysqli_real_escape_string($this->connection, $query);
            $sql = "SELECT id, title, slug, excerpt, published_at, status
                    FROM articles
                    WHERE author_id = {$authorId}
                      AND (title LIKE '%{$searchQuery}%' OR content LIKE '%{$searchQuery}%')
                    ORDER BY published_at DESC
                    LIMIT {$limit}";
        } else {
            $sql = "SELECT id, title, slug, excerpt, published_at, status
                    FROM articles
                    WHERE author_id = {$authorId}
                    ORDER BY published_at DESC
                    LIMIT {$limit}";
        }

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Author search failed: ' . mysqli_error($this->connection));
        }

        $articles = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $articles[] = $row;
        }
        mysqli_free_result($result);

        return $articles;
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            mysqli_close($this->connection);
        }
    }
}
