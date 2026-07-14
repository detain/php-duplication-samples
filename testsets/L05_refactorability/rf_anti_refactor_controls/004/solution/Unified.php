// CATASTROPHIC ANTI-PATTERN: attempting to unify these violates Command-Query Separation
// Carrier A (url): QUERY — returns signed URL, conceptually read-only (signature is just encoding)
// Carrier B (upload): COMMAND — creates/overwrites file in storage, changes system state
// Carrier C (download): COMMAND — copies file to local path, changes system state
//
// These MUST remain separate because:
// 1. Command vs Query distinction is FUNDAMENTAL — callers must know if method changes state
// 2. url() could conceptually be called before a file exists (pre-signed URL for future upload)
// 3. upload/download return success/failure AND change state, while url() returns URL with no state change
// 4. Unifying would create a method with SCHIZOPHRENIC contract — sometimes mutating, sometimes not,
//    based on hidden internal logic that callers cannot predict
//
// This is STRONGER than rf_non_extractable — the CQS violation here creates a method that
// VIOLATES a fundamental principle that most developers rely on for reasoning about code.