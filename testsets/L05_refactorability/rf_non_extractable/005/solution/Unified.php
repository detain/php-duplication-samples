// ANTI-PATTERN: attempting to unify these would produce undefined behavior for unicode
// Carrier A: strips accents -> 'cafe' from 'café'
// Carrier B: preserves unicode -> 'café' stays 'café'
// Carrier C: transliterates -> 'cafe' from 'café' (using iconv)
// These MUST remain separate — each has fundamentally different unicode handling and output contracts