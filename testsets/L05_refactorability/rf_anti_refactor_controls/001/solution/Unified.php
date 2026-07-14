// CATASTROPHIC ANTI-PATTERN: attempting to unify these violates Command-Query Separation
// Carrier A (authenticate): COMMAND — changes state (recordLogin, token generation, user lookup)
// Carrier B (refresh): QUERY — pure token exchange, NO side effects, returns new token pair
// Carrier C (validate): QUERY — read-only token decode + expiry check, returns user_id/roles
//
// These MUST remain separate because:
// 1. Side-effect profiles are incompatible (write vs read-only)
// 2. Error handling strategies differ (authenticate: null return, refresh: null return, validate: null return)
// 3. Return type semantics differ (authenticate: auth context, refresh: new tokens, validate: authorization claims)
// 4. Unifying would make the method's contract UNDEFINED — callers cannot predict behavior
//
// This is STRONGER than rf_non_extractable — rf_non_extractable shows incompatible semantics,
// but THIS shows violation of a fundamental software design principle (CQS) that would
// cause UNDEFINED BEHAVIOR at call sites.