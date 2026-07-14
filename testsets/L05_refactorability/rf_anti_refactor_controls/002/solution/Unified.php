// CATASTROPHIC ANTI-PATTERN: attempting to unify these violates State Machine semantics
// Carrier A (charge): CREATE — creates new 'charge' transaction with unique ID
// Carrier B (refund): UPDATE — modifies existing transaction state, enforces business rule
// Carrier C (handleWebhook): PURE ROUTING — no state change, dispatches to handlers
//
// These MUST remain separate because:
// 1. State transition types are incompatible (Create vs Update vs Query/Routing)
// 2. Business rule enforcement differs (refund has amount validation against original)
// 3. Idempotency guarantees differ (charge: not idempotent, refund: partially idempotent, webhook: must be idempotent)
// 4. Unifying would create an IMPOSSIBLE method contract — a single interface cannot express
//    'create OR update OR route depending on type' without becoming a confused abstraction
//
// This is STRONGER than rf_non_extractable — the state machine violation means call sites
// would have UNDEFINED BEHAVIOR depending on which code path executes.