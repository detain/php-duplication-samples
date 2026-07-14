// ANTI-PATTERN: attempting to unify these would hide critical semantic differences
// Carrier A: validates credentials -> bool
// Carrier B: authorizes action -> Permission object
// Carrier C: logs events -> void
// These MUST remain separate — each has fundamentally different semantics, side effects, and return types