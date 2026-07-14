// ANTI-PATTERN: attempting to unify these would destroy distinct event processing semantics
// Carrier A: dispatch(event) -> void (sync, blocking)
// Carrier B: enqueue(event) -> string jobId (async, non-blocking)
// Carrier C: filter(event) -> Event modified (transforms payload)
// These MUST remain separate — each has fundamentally different timing and data flow contracts