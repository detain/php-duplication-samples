# L09 — Mixed Interference (Compound)

**What it tests:** stacked axis interference — multiple transformation axes combined, creating
clones that individually would be easy to detect but together form a compound challenge.

This level mixes transformations across different axes (whitespace, comments, renames, statement
edits) to create realistic drift scenarios. Simple tools handle one axis at a time; these mixes
require integrated approaches that can handle whitespace AND comments AND renames simultaneously.
The `mix_realistic_drift` family simulates actual code evolution patterns, while `mix_4plus`
stacks 4+ transformation types for maximum interference.

Detection expectations vary by mix complexity: basic 2-axis mixes can be handled by advanced
token-based tools with multi-axis normalization, but 3+ axis mixes and realistic drift patterns
require semantic reasoning capabilities.

## Families

| Family | Interference | Description |
|--------|-------------|-------------|
| mix_ws_cm | whitespace+comments | Whitespace and comment changes combined |
| mix_rn_ws | renames+whitespace | Identifier renames with whitespace variation |
| mix_rn_cm_ws | renames+comments+whitespace | Three-axis interference |
| mix_st_rn | statement edits+renames | Statement modifications with renames |
| mix_cf_rn_ws | controlflow+renames+whitespace | Control flow changes with other axes |
| mix_noise_heavy | various | High noise — many small changes obscuring clone |
| mix_4plus | 4+ axes | Four or more transformation axes stacked |
| mix_realistic_drift | realistic evolution | Actual code evolution patterns over time |

## Difficulty

`requires`: union of stacked axes (whitespace_normalization, comment_stripping,
identifier_canonicalization, ast_canonicalization)
`difficulty_band`: varies by mix type

## Current status

_This level has 40 sets across 8 families (2026-07-13)._

_Pilot present: `mix_ws_cm/001-005`, `mix_rn_ws/001-005`, `mix_rn_cm_ws/001-005`,
`mix_st_rn/001-005`, `mix_cf_rn_ws/001-005`, `mix_noise_heavy/001-005`, `mix_4plus/001-005`,
`mix_realistic_drift/001-005`._
