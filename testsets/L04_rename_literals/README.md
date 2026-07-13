# L4 — Renames, Literals, Types, Namespaces (Type-2)

**What it tests:** identifier canonicalization and literal abstraction — structure and layout are
identical; identifiers/literals/types/namespaces differ.

Planned families: `rn_locals`, `rn_params`, `rn_functions`, `rn_classes`, `rn_case_style`,
`lt_numbers`, `lt_strings`, `lt_arrays`, `lt_const_indirection`, `ty_hints`, `ns_imports`,
`rn_combined`.

`requires`: `identifier_canonicalization`, `literal_abstraction`. `detection_expectation`:
parameterized-token (e.g. phpcpd `--fuzzy`)/AST/metric/semantic pass; plain token matchers fail.

_Pilot present: `rn_locals/001`._
