<?php
declare(strict_types=1);

namespace App\Repository\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;

final class DqlUserRepository
{
    private EntityManager $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function findById(int $id): ?array
    {
        $dql = 'SELECT u.id, u.username, u.email, u.firstName, u.lastName, u.createdAt, u.status
                FROM App\\Entities\\User u
                WHERE u.id = :id AND u.active = true';

        try {
            $query = $this->entityManager->createQuery($dql);
            $query->setParameter('id', $id);
            $query->setMaxResults(1);

            $result = $query->getResult();

            if (empty($result)) {
                return null;
            }

            $user = $result[0];

            if (is_object($user)) {
                return [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'first_name' => $user->getFirstName(),
                    'last_name' => $user->getLastName(),
                    'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                    'status' => $user->getStatus(),
                ];
            }

            return $user;
        } catch (ORMException $e) {
            throw new RuntimeException('DQL query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder();
    }
}
