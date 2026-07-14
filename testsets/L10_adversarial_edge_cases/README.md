# L10 — Adversarial Edge Cases (Threshold/Traps/Topology)

**What it tests:** adversarial challenges — clones designed to exploit detector weaknesses, hit
thresholds, or create topological confusion.

This level pushes detectors toward their failure modes. Near-miss cases sit just below clone
detection thresholds. Boundary cases cluster clones at exactly threshold values. Overlapping clones
create ambiguity about which regions constitute the clone. Same-file clones, HTML-mixed content,
generated code, encoding edge cases, and large/many-file scenarios each stress specific detector
capabilities. The `adv_genealogy` family explores ancestry — clones that share partial history.

No token-based detector is expected to handle these reliably. They require either sophisticated
semantic analysis or human judgment. These are the cases where detectors either produce false
negatives or generate confusing/incorrect clone reports.

## Families

| Family | Interference | Description |
|--------|-------------|-------------|
| adv_near_miss | token/AST | Just below detection threshold (size/quality) |
| adv_below_threshold | token/AST | Deliberately sub-threshold clone fragments |
| adv_boundary | token/AST | Exactly at threshold boundary values |
| adv_overlap | token/AST | Clones that overlap each other in complex ways |
| adv_same_file | token/AST | Clones within the same file (self-plagiarism) |
| adv_html_mix | token/AST | PHP mixed with HTML template content |
| adv_generated | semantic | Code that looks generated/boilerplate |
| adv_encoding | token/AST | Unusual character encodings or escape sequences |
| adv_large_files | performance+semantic | Very large files with embedded clones |
| adv_many_files | topology | Many files where clone topology is complex |
| adv_genealogy | semantic | Clone families with shared ancestry/drift |

## Difficulty

`requires`: varies by family (token-based for boundaries, semantic for genealogy)
`difficulty_band`: adversarial (token_based: false expected)

## Current status

_This level has 55 sets across 11 families (2026-07-13)._

_Pilot present: `adv_near_miss/001-005`, `adv_below_threshold/001-005`, `adv_boundary/001-005`,
`adv_overlap/001-005`, `adv_same_file/001-005`, `adv_html_mix/001-005`, `adv_generated/001-005`,
`adv_encoding/001-005`, `adv_large_files/001-005`, `adv_many_files/001-005`,
`adv_genealogy/001-005`._
