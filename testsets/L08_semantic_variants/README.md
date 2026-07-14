# L08v — Semantic Variants (Type-4/domain semantic)

**What it tests:** supplementary semantic variants — additional sets for 5 L08 families where
the base idioms needed extra structural variation beyond what the main L08 sets captured.

This is a small supplementary directory containing 5 families from L08 (sem_config_driven,
sem_normalization_order, sem_orm_sql, sem_scatter, sem_state_machine), each with one additional
variant set that explores a different structural angle within the same semantic equivalence class.
These complement the main L08_semantic_idioms sets by providing edge case coverage within the
semantic reasoning domain.

## Families

| Family | Interference | Description |
|--------|-------------|-------------|
| sem_config_driven | semantic | Config-driven vs. hardcoded variant |
| sem_normalization_order | semantic+whitespace | Operation normalization order variant |
| sem_orm_sql | semantic | ORM vs. raw SQL variant |
| sem_scatter | semantic | Scattered vs. centralized state variant |
| sem_state_machine | semantic | State machine vs. conditional variant |

## Difficulty

`requires`: semantic_reasoning (various)
`difficulty_band`: semantic/AI-only

## Current status

_This level has 5 sets across 5 families (2026-07-13)._

_Pilot present: `sem_config_driven/001`, `sem_normalization_order/001`, `sem_orm_sql/001`,
`sem_scatter/001`, `sem_state_machine/001`._
