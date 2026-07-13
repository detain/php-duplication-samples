# L7 — API & Idiom Substitution (Type-4)

**What it tests:** API abstraction — the same outcome achieved via different library usage
(hand-written variants, behavior-verified).

Planned families: `api_map_loop`, `api_strings`, `api_regex_string`, `api_recursion`,
`api_table_driven`, `api_builtins`, `api_data_shape`, `api_serialization`, `api_datetime`.

`requires`: `api_abstraction`, `ast_canonicalization`. `detection_expectation`: semantic / AI
detectors expected; token and most AST tools not.

_No sets built yet — the `API-01` variant selector, the `invoice_totals` `api_map_loop` variant,
and its `equivalence_test.php` are implemented and ready._
