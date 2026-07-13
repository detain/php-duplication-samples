# L5 — Statement Edits (Type-3)

**What it tests:** gap/edit tolerance — copies have drifted: statements added, removed, moved, or
the clone is split by a foreign block.

Planned families: `st_insert_logging`, `st_insert_dead`, `st_insert_step`, `st_delete`,
`st_reorder`, `st_gap_split`, `st_partial`, `st_param_reorder`, `st_expr_tweak`, `st_combined`.

`requires`: `ast_canonicalization` (+ gap tolerance for `st_gap_split`). `detection_expectation`:
AST (gap-tolerant)/metric/semantic pass; strict token matchers fail.

_No sets built yet (transform `ST-01` is implemented and ready)._
