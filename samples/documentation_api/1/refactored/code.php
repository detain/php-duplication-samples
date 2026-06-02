<?php

declare(strict_types=1);

namespace Api\Customers;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Attribute\AsController;

/**
 * Customer API unified controller
 *
 * Handles all customer-related operations including:
 * - Customer registration and profile management
 * - Email validation and duplicate detection
 * - Password strength verification
 * - Pagination and search capabilities
 * - Customer statistics
 *
 * @see Customer for the customer entity
 * @see CustomerRepository for database operations
 */
#[AsController]
final class CustomerController
{
    private const MAX_EMAIL_LENGTH = 254;
    private const MIN_PASSWORD_LENGTH = 8;
    private const MAX_PASSWORD_LENGTH = 128;

    public function __construct(
        private readonly EntityManager $entityManager
    ) {
    }

    /**
     * Create a new customer account
     *
     * @param Request $request HTTP request with customer data
     * @return JsonResponse Created customer data or validation errors
     */
    #[Route('/api/customers', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $errors = $this->validateCustomerData($data);
        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], 400);
        }

        $repository = $this->entityManager->getRepository(Customer::class);

        if ($repository->emailExists($data['email'])) {
            return new JsonResponse(['error' => 'Email already registered'], 409);
        }

        $customer = new Customer();
        $customer->setEmail($data['email']);
        $customer->setFirstName(trim($data['first_name']));
        $customer->setLastName(trim($data['last_name']));
        $customer->setPhone($data['phone'] ?? null);
        $customer->setPasswordHash(password_hash($data['password'], PASSWORD_ARGON2ID));
        $customer->setMarketingConsent($data['marketing_consent'] ?? false);
        $customer->setReferralCode($data['referral_code'] ?? null);
        $customer->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'first_name' => $customer->getFirstName(),
            'last_name' => $customer->getLastName(),
            'created_at' => $customer->getCreatedAt()->format('c')
        ], 201);
    }

    /**
     * List customers with pagination and filtering
     *
     * @param Request $request HTTP request with query parameters
     * @return JsonResponse Paginated customer list
     */
    #[Route('/api/customers', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = min(100, max(1, (int) $request->query->get('per_page', 20)));
        $search = $request->query->get('search');
        $sortBy = $request->query->get('sort_by', 'createdAt');
        $sortDirection = strtoupper($request->query->get('sort_direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }

        $repository = $this->entityManager->getRepository(Customer::class);
        $paginator = $repository->findPaginated($page, $perPage, $filters, $sortBy, $sortDirection);

        $customers = [];
        foreach ($paginator as $customer) {
            $customers[] = $customer->toArray();
        }

        return new JsonResponse([
            'data' => $customers,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => count($paginator),
                'total_pages' => ceil(count($paginator) / $perPage),
            ]
        ]);
    }

    /**
     * Get a specific customer by ID
     *
     * @param int $id Customer ID
     * @return JsonResponse Customer data or error
     */
    #[Route('/api/customers/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $customer = $this->entityManager->find(Customer::class, $id);

        if ($customer === null) {
            return new JsonResponse(['error' => 'Customer not found'], 404);
        }

        return new JsonResponse($customer->toArray());
    }

    /**
     * Update customer profile
     *
     * @param Request $request HTTP request with update data
     * @param int $id Customer ID
     * @return JsonResponse Updated customer data or errors
     */
    #[Route('/api/customers/{id}', methods: ['PATCH'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $customer = $this->entityManager->find(Customer::class, $id);

        if ($customer === null) {
            return new JsonResponse(['error' => 'Customer not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['first_name'])) {
            $firstName = trim($data['first_name']);
            if (!preg_match('/^[a-zA-Z\s\-\']{1,100}$/', $firstName)) {
                return new JsonResponse(['error' => 'Invalid first name format'], 400);
            }
            $customer->setFirstName($firstName);
        }

        if (isset($data['last_name'])) {
            $lastName = trim($data['last_name']);
            if (!preg_match('/^[a-zA-Z\s\-\']{1,100}$/', $lastName)) {
                return new JsonResponse(['error' => 'Invalid last name format'], 400);
            }
            $customer->setLastName($lastName);
        }

        if (isset($data['phone'])) {
            $phone = preg_replace('/\D/', '', $data['phone']);
            if (strlen($phone) < 10 || strlen($phone) > 15) {
                return new JsonResponse(['error' => 'Invalid phone number format'], 400);
            }
            $customer->setPhone('+' . ltrim($phone, '+'));
        }

        if (isset($data['marketing_consent'])) {
            $customer->setMarketingConsent((bool) $data['marketing_consent']);
        }

        $customer->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return new JsonResponse($customer->toArray());
    }

    /**
     * Delete a customer
     *
     * @param int $id Customer ID
     * @return JsonResponse Success or error
     */
    #[Route('/api/customers/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $customer = $this->entityManager->find(Customer::class, $id);

        if ($customer === null) {
            return new JsonResponse(['error' => 'Customer not found'], 404);
        }

        $this->entityManager->remove($customer);
        $this->entityManager->flush();

        return new JsonResponse(null, 204);
    }

    /**
     * Search customers by name or email
     *
     * @param Request $request HTTP request with query parameter 'q'
     * @return JsonResponse Matching customers
     */
    #[Route('/api/customers/search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return new JsonResponse(['error' => 'Search query must be at least 2 characters'], 400);
        }

        $repository = $this->entityManager->getRepository(Customer::class);
        $customers = $repository->search($query);

        return new JsonResponse([
            'data' => array_map(fn($c) => $c->toArray(), $customers)
        ]);
    }

    /**
     * Get customer statistics
     *
     * @return JsonResponse Statistics including total, new today, etc.
     */
    #[Route('/api/customers/stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $repository = $this->entityManager->getRepository(Customer::class);
        $stats = $repository->getStatistics();

        return new JsonResponse($stats);
    }

    /**
     * Check if email is available
     *
     * @param Request $request HTTP request with email parameter
     * @return JsonResponse Availability status
     */
    #[Route('/api/customers/check-email', methods: ['GET'])]
    public function checkEmail(Request $request): JsonResponse
    {
        $email = $request->query->get('email');

        if (empty($email)) {
            return new JsonResponse(['error' => 'Email parameter is required'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Invalid email format'], 400);
        }

        $repository = $this->entityManager->getRepository(Customer::class);
        $exists = $repository->emailExists($email);

        return new JsonResponse(['available' => !$exists]);
    }

    /**
     * Validate customer input data
     *
     * @param array $data Input data to validate
     * @return array Array of validation errors (empty if valid)
     */
    private function validateCustomerData(array $data): array
    {
        $errors = [];

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } else {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            } elseif (strlen($data['email']) > self::MAX_EMAIL_LENGTH) {
                $errors['email'] = 'Email exceeds maximum length of ' . self::MAX_EMAIL_LENGTH . ' characters';
            }
        }

        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required';
        } elseif (!preg_match('/^[a-zA-Z\s\-\']{1,100}$/', $data['first_name'])) {
            $errors['first_name'] = 'First name contains invalid characters';
        }

        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Last name is required';
        } elseif (!preg_match('/^[a-zA-Z\s\-\']{1,100}$/', $data['last_name'])) {
            $errors['last_name'] = 'Last name contains invalid characters';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } else {
            $passwordErrors = $this->validatePassword($data['password']);
            if (!empty($passwordErrors)) {
                $errors['password'] = $passwordErrors;
            }
        }

        return $errors;
    }

    /**
     * Validate password strength
     *
     * @param string $password Password to validate
     * @return array Array of validation error messages
     */
    private function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $errors[] = 'Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters';
        }

        if (strlen($password) > self::MAX_PASSWORD_LENGTH) {
            $errors[] = 'Password exceeds maximum length of ' . self::MAX_PASSWORD_LENGTH . ' characters';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return $errors;
    }
}
