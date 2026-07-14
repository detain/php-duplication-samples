# L1 — Exact Duplication (Type-1)

**What it tests:** byte-for-byte identical clones — the calibration tier every detector must get
perfect.

The cloned region is identical in all 3 carriers; only the surrounding scaffold and symbol names
differ. Families (all 7 built): `ex_function`, `ex_method`, `ex_block`, `ex_full_file`, `ex_position`,
`ex_size_ladder` (includes the deliberate sub-threshold rung), `ex_multi_cluster`.

`requires`: nothing beyond exact matching. `detection_expectation`: all classes true (except the
sub-threshold rung of `ex_size_ladder`).

_This level is complete: all 7 families built (35 sets, 2026-07-13)._
