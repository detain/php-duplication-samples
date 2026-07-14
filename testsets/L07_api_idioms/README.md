# L7 — API & Idiom Substitution (Type-4)

**What it tests:** API abstraction — the same outcome achieved via different library usage
(hand-written variants, behavior-verified).

Families (all 9 built): `api_map_loop`, `api_strings`, `api_regex_string`, `api_recursion`,
`api_table_driven`, `api_builtins`, `api_data_shape`, `api_serialization`, `api_datetime`.

`requires`: `api_abstraction`, `ast_canonicalization`. `detection_expectation`: semantic / AI
detectors expected; token and most AST tools not.

_This level is complete: **45 sets across 9 families** (2026-07-13)._
