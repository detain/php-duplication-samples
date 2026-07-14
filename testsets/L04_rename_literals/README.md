# L4 — Renames, Literals, Types, Namespaces (Type-2)

**What it tests:** identifier canonicalization and literal abstraction — structure and layout are
identical; identifiers/literals/types/namespaces differ.

Families (all 12 built): `rn_locals`, `rn_params`, `rn_functions`, `rn_classes`, `rn_case_style`,
`lt_numbers`, `lt_strings`, `lt_arrays`, `lt_const_indirection`, `ty_type_hints`, `ns_imports`,
`rn_combined`.

`requires`: `identifier_canonicalization`, `literal_abstraction`. `detection_expectation`:
parameterized-token (e.g. phpcpd `--fuzzy`)/AST/metric/semantic pass; plain token matchers fail.

_This level is complete: all 12 families built (60 sets)._
