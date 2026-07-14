// CATASTROPHIC ANTI-PATTERN: attempting to unify these creates an IMPOSSIBLE type contract
// Carrier A (sendEmail): recipient = email address, validated via FILTER_VALIDATE_EMAIL
// Carrier B (sendSms): recipient = E.164 phone number, validated via phone regex
// Carrier C (sendPush): recipient = push token, validated via length >= 10
//
// These MUST remain separate because:
// 1. Validation logic is channel-specific with NO common interface
// 2. Error messages are channel-specific ('Invalid email' vs 'Invalid phone' vs 'Invalid token')
// 3. Sanitization requirements differ (email: no HTML, phone: digits/+ only, push: alphanumeric)
// 4. Unifying would require 'any string' validation which provides ZERO type safety — callers
//    would pass wrong recipient types and receive confusing errors at runtime
//
// This is STRONGER than rf_non_extractable — it shows that some duplicated validation
// patterns are INHERENTLY channel-specific and CANNOT be abstracted without destroying
// the type safety that makes the validation valuable.