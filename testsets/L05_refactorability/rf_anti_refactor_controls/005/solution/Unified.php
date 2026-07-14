// CATASTROPHIC ANTI-PATTERN: attempting to unify these creates AMBIGUOUS idempotency
// Carrier A (GET requests): IDEMPOTENT — read-only, safe to retry automatically
// Carrier B (POST requests): NOT IDEMPOTENT — creates data, retry may cause duplicates
// Carrier C (PUT/DELETE/PATCH): VARIABLE — PUT is idempotent, DELETE is idempotent, PATCH is not
//
// These MUST remain separate because:
// 1. Idempotency determines retry safety — unifying would make retry behavior UNDEFINED
// 2. HTTP semantics are method-specific and CANNOT be abstracted without losing semantics
// 3. Callers rely on idempotency guarantees for circuit breaker patterns, retry queues, etc.
// 4. Unifying would create a 'makeHttpCall' method where callers cannot predict if it's safe to retry
//    without knowing internal implementation details
//
// This is STRONGER than rf_non_extractable — the idempotency ambiguity would cause
// REAL PRODUCTION BUGS: callers would implement wrong retry logic and cause data duplication
// or missed operations depending on which code path executes.