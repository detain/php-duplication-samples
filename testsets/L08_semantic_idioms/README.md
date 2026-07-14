# L08 — Semantic Idioms (Type-4/domain semantic)

**What it tests:** domain semantic equivalence — the same algorithmic outcome expressed through
different idiomatic patterns, requiring deep understanding of intent rather than surface form.

This level is where structural detectors hit their ceiling. The code has been rewritten to achieve
identical outcomes using different domain patterns: alternate rule implementations, inline code
extractions, split object-oriented structures, varying error handling styles, dependency injection
patterns, event-driven architectures, ORM query constructions, scattered vs. centralized state,
state machine representations, and configuration-driven logic. These require semantic reasoning about
what the code is actually accomplishing, not just how it looks.

Semantic detectors (equivalence checking, AI-based analysis) are expected to handle these. Most
token-based and many AST-based tools will fail because the syntax trees differ substantially despite
functional equivalence.

## Families

| Family | Interference | Description |
|--------|-------------|-------------|
| sem_rule | semantic | Same validation/decision rule expressed via different idioms |
| sem_inline_extract | semantic | Inlined code vs. extracted helper function doing same thing |
| sem_oo_split | semantic | Single procedure vs. split across objects/classes |
| sem_error_style | semantic | Different error handling patterns (exceptions vs. returns) |
| sem_di_style | semantic | Dependency injection done differently (constructor vs. setter) |
| sem_event | semantic | Event-driven vs. direct method call for same flow |
| sem_orm_sql | semantic | Raw SQL vs. ORM query for same data operation |
| sem_scatter | semantic | Scattered local variables vs. centralized state object |
| sem_state_machine | semantic | State machine vs. if-else chain for same logic |
| sem_config_driven | semantic | Hardcoded values vs. config-driven logic for same outcome |
| sem_normalization_order | semantic+whitespace | Operation order normalized differently but equivalent |

## Difficulty

`requires`: semantic_reasoning (various), cfg_comparison, inline_expansion
`difficulty_band`: semantic/AI-only

## Current status

_This level has 55 sets across 11 families (2026-07-13)._

_Pilot present: `sem_rule/001-005`, `sem_inline_extract/001-005`, `sem_oo_split/001-005`,
`sem_error_style/001-005`, `sem_di_style/001-005`, `sem_event/001-005`, `sem_orm_sql/001-005`,
`sem_scatter/001-005`, `sem_state_machine/001-005`, `sem_config_driven/001-005`,
`sem_normalization_order/001-005`._
