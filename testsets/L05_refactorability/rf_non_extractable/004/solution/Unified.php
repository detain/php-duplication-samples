// ANTI-PATTERN: attempting to unify these would create an impossible return contract
// Carrier A: validate(data) -> bool (true=valid, throws on internal error)
// Carrier B: validate(data) -> void (throws ValidationException on invalid)
// Carrier C: validate(data) -> string[] (returns all errors, never throws)
// These MUST remain separate — each has fundamentally different error handling strategies