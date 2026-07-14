// ANTI-PATTERN: attempting to unify these would conflate incompatible operations
// Carrier A: cache->set(key, data, ttl) -> void
// Carrier B: cache->get(key, loader) -> mixed (with fallback)
// Carrier C: cache->invalidate(key) -> bool
// These MUST remain separate — each has fundamentally different contracts and side effects