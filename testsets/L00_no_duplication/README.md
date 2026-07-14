# L0 — No Duplication (Negative Controls)

**What it tests:** detector *restraint* — a perfect tool reports **zero** clusters here; this
measures the false-positive floor.

Every set has roles `distractor`/`clean` only, `duplication.present = false`, and an empty
`clusters` array. Families (all 6 built, ≥5 sets each): `nd_distinct_domains`, `nd_same_domain`,
`nd_boilerplate`, `nd_structural_echo`, `nd_shared_vocab`, `nd_size_spread`.

`requires`: none. `detection_expectation`: nothing should fire.

_This level is complete: all 6 families built (30 sets, 2026-07-13)._
