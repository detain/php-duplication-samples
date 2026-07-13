# L1 — Exact Duplication (Type-1)

**What it tests:** byte-for-byte identical clones — the calibration tier every detector must get
perfect.

The cloned region is identical in all 3 carriers; only the surrounding scaffold and symbol names
differ. Planned families: `ex_function`, `ex_method`, `ex_block`, `ex_full_file`, `ex_position`,
`ex_size_ladder` (includes the deliberate sub-threshold rung), `ex_multi_cluster`.

`requires`: nothing beyond exact matching. `detection_expectation`: all classes true (except the
sub-threshold rung of `ex_size_ladder`).

_Pilot present: `ex_function/001`._
