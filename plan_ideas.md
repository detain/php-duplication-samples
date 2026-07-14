# Plan: Corpus Expansion Build — Executing `ideas.md` to Completion

**Status:** BUILDABLE PLAN — not yet started.
**Date:** 2026-07-13
**Spec it executes:** `ideas.md` (the combined catalog — fable §1–§11 verbatim + folded-in opencode/copilot §12–§27; ≈225 fable ideas plus the merged additions).
**Builds on:** the corpus `plan_samples.md` already produced — ≈540 sets across L00–L10, the deterministic `gen/` engine (schemas, transforms, 3 seeds, scaffolds, distractors, `verify.php`, `manifest.php`), and `bench/`. **This is the next campaign**, expanding that corpus per `ideas.md`.
**Reuses wholesale:** the execution machinery of `plan_samples.md` **§18** (parallel-agent orchestration, file-scope matrix, build→review→fix loop, Definition of Done) and **§20** (the agent prompt library + shared-knowledge block). This plan states only the **deltas** and the **new phase/family schedule**; the orchestrator applies `plan_samples.md` §18/§20 as the standing playbook.
**Decisions already made** (`ideas.md` §0): **D-A = do both** — keep L00–L10 with new families + 2-D `difficulty.axes` + basic→combined *rungs*, **and** add tiers **L11–L20** ⇒ the ladder is now **L00–L20**. **D-B = rich metadata objects canonical + optional flat mirrors** (`ideas.md` §1/§26). **D-C** partial-duplication lives at **L05-extension + the `partiality` axis** (L11 is Deep-Rename Stacks). **D-D** provenance tags kept.

---

## 0. Orientation — read this, then delegate everything else

- **What exists (do NOT rebuild):** the L00–L10 tree (~540 DONE sets), `gen/` (build/verify/manifest + transforms WS/CM/RN/LT/ST + CF/API variant-selectors + `registry.json`), 3 seeds (`invoice_totals`, `csv_import`, `access_guard`), scaffolds, distractors, `bench/run-testsets.php`, and the `testsets/schema/*` (v1, `additionalProperties:false`). This campaign **extends** that substrate; it never edits `samples/**` or `refactored/**`.
- **What this campaign adds:** everything in `ideas.md` — schema v2 + line-accounting (§1), ~50 new transform codes (§8) incl. real impls for the 26 declared-only NZ/ENC/CP codes, ~26+ new seeds (§7/§16.8), ~24 generator features + 12 housekeeping fixes (§9), and the content families across §2–§6 and §12–§25, plus the adopted **L11–L20** tiers (§22) and `bench/profile.php` (§6 K-34, the R8 Tool Capability Profile).
- **Target scope:** ≈**920 (fable) → 1000+ (copilot)** total sets (`ideas.md` §24). Every family carries ≥5 sets unless the family declares otherwise. Exact per-family counts are the orchestrator's to schedule from §9/§15 of `plan_samples.md` and the `ideas.md` family list.
- **How to read this plan:** §1 is the orchestration doctrine (the deltas that matter most for this campaign). §3 is the enabling Foundation gate. §4 is the phase schedule that places **every** `ideas.md` family. §5–§7 are DoD/prompt/goal deltas. §8 is the full family→phase index (completeness audit).

---

## 1. Orchestration doctrine (deltas from `plan_samples.md` §18)

The machinery is `plan_samples.md` §18/§20. **Five deltas** govern THIS campaign; everything else is inherited unchanged.

### 1.1 The orchestrator reads only the plans — it delegates all else to agents
The orchestrator (top-level/lead) reads only: this file, `plan_samples.md` (§18/§20 + the §-excerpts it must paste into subagents), `prompt_ideas.md`, and `ideas.md`. It does **NOT** itself open `gen/` code, `testsets/` content, `registry.json`, or schemas, and does **NOT** itself run `php`, `verify`, `build`, or `bench`. **Every** act of reading source, researching ("does transform X exist? how does `SetBuilder` emit line numbers? what fields does `set.schema.json` allow?"), verifying, building, and benchmarking is done by a **spawned agent** that reports back a concise result. The orchestrator's context stays limited to the plans + agent reports — this keeps it small and prevents it from drifting into hands-on work.

### 1.2 Trust but verify — with agents
No agent report is acted on as fact until an **independent** agent has corroborated it. For builds this is the existing adversarial **Reviewer** step (§18.5/§20.3). For everything else the orchestrator would otherwise take on trust — "the foundation is green", "transform T-UQ exists and is deterministic", "this set's ground truth is line-accurate" — the orchestrator spawns a **read-only Verifier** to re-run/re-derive and confirm, or requires two agents to agree. Default to FAIL/again on disagreement. Never self-certify; never let a builder's own claim close a set.

### 1.3 Concurrency & the one-writer rule
- **≤ 10 concurrent WRITER chains** at any instant (queue the rest; start as slots free) — the §18.4 cap. The cap counts **writers**; read-only Researchers/Verifiers/Reviewers may run alongside them (subject to runtime limits).
- **Exactly one writer per file/section at a time.** No two agents ever write the same file. As §18.6: **one Set-Builder per family** (the family recipe `gen/recipes/Lxx/<fam>.json` is a single file); a family that needs two concurrent sets either splits its recipe or builds them in different waves. Shared substrate (schemas, `gen/**`, seeds, transforms, scaffolds, distractors, `bench/profile.php`) is **read-only to builders**; only **Foundation** creates/extends it. Race-prone aggregates (`manifest.json`, aggregate ground truth, level READMEs, root README, `samples.json`) are owned only by **Integration**.
- **Missing-substrate rule (unchanged):** a builder that needs a transform/seed/scaffold Foundation didn't provide **stops and reports** — it never adds shared substrate itself (that races other builders). The orchestrator handles it as a Foundation follow-up.

### 1.4 Nested agents (opencode) — use where they help
This campaign runs on **opencode**, which permits **multiple levels of nested agents** (unlike the single-level model in `plan_samples.md`). Use nesting where it sharpens delegation and keeps any one context small:
- **L0 Orchestrator** → spawns L1 leads/gates.
- **L1**: a **Phase-Lead** runs one phase's fan-out + gate; **Foundation** may spawn parallel sub-agents by area (schema · generator-features · transforms · seeds · profile); **Integration** likewise.
- **L2**: **Set-Builders**, Foundation area sub-agents, and — spawned *by* a Set-Builder inside its own slot — its **Reviewer** and (on FAIL) **Fixer**; plus read-only **Researcher/Verifier** helpers any writer may spawn to read code or corroborate a fact it needs.
- **Invariant at every depth:** ≤10 concurrent **writers** total; **one writer per file**; shared substrate read-only except its owner; **no git, ever**. Nesting adds delegation, never new writers to the same file. (If the runtime runs subagents sequentially, that is fine — the cap is an upper bound for collision-safety; determinism holds either way, §18.9.1.)

### 1.5 No git, ever (unchanged — `plan_samples.md` §18.1 / §I)
No agent at **any** depth runs any `git` subcommand (`add`/`commit`/`branch`/`worktree`/`checkout`/`switch`/`stash`/`reset`/`push`/`tag`). Work in place in `/home/sites/php-duplication-samples`; no worktrees/branches. The expanded tree stays **uncommitted** for the human to review and commit by hand. An agent that thinks it needs git stops and reports.

---

## 2. Roles & task types (the same five — extended scopes)

Reuse the five roles and three task types (`plan_samples.md` §18.2, §20). Scope deltas for this campaign only:

| Role | Delta for this campaign |
|---|---|
| **Foundation** (gate) | Also owns: **schema v2** (E-1…E-18) and the new verifier checks (V-1…V-10); generator features (F-1…F-24) + housekeeping (H-1…H-12); the new transform classes (T-SY/T-LEG/T-UQ/T-RF + NU/BL/CF/ST/LT/API additions + real NZ/ENC impls + CP topology directives); the new seeds (S-1…S-26 + the §16.8 union); `bench/profile.php` (F-20); and the new level dirs `testsets/L11_*…L20_*` + README stubs. Extends the substrate **between phases** (§4). Still a gate; may split into ≤N parallel sub-agents by area (§1.4). |
| **Set-Builder** (one set) | Also emits the **v2 metadata** its family requires: `region_sloc`/`duplication.uniqueness_budget`/`members[].unique_segments` for `pd_*`; `intended_refactoring` + a `solution/Unified.php`+holes for `rf_*`; `interference_profile` (+`groups_applied/withheld`) for `mix_*`; `members[].drift_generation`+`pairwise_expectation` for genealogy; multi-cluster `expected.json` for topology. Still **one writer per family recipe**. |
| **Reviewer / Fixer** (per set) | As §20.3/§20.4, plus the DoD additions in §5. Reviewer stays independent + adversarial → `REVIEW.md`; Fixer scope = that set's Builder scope + `FIXLOG.md`. |
| **Integration** (gate) | As §20.5, extended to L00–L20, multi-cluster/fragment/drift/refactoring ground truth, and generating the **Tool Capability Profiles** + `progression.json`. |
| **Researcher / Verifier** (NEW, read-only, no write scope) | Exist to satisfy §1.1–§1.2: answer "does X exist / how does Y work" by reading code, and re-run checks to corroborate reports, so the orchestrator and builders don't read/verify themselves. Never write (except a Verifier may write its own scratch note if asked); never touch git. |

---

## 3. Foundation P0e — the enabling gate (schema v2 + engine + profile)

One Foundation campaign (parallel sub-agents by area per §1.4) delivers and **proves on pilots** before any expansion content fans out. Deliverables (all from `ideas.md`):

1. **Schema v2** (`ideas.md` §1, E-1…E-18): add the new **optional** fields to `set.schema.json`/`expected.schema.json`; bump `schema_version` to `enum [1,2]`; keep `additionalProperties:false` but declare each new prop (so v1's 540 sets stay valid — they simply omit v2 fields). Fields: `files[].region_sloc`, `duplication.uniqueness_budget`, `duplication.duplication_ratio`, `members[].unique_segments`, cluster rollups, `intended_refactoring`, `interference_profile`, 2-D `difficulty.axes`, `progression`, multi-cluster topology, `fragments[]`/`drift_generation`/`pairwise_expectation` activation, `capability_probe`, scoring extensions, `difficulty.requires` additions, `files[].role: support`, registry/generator/provenance additions. **D-B:** rich objects canonical; flat mirrors (§26) optional-derived only.
2. **Verifier checks V-1…V-10** (`ideas.md` §1) — one per new field-group, so v2 metadata is as trustworthy as v1 ground truth.
3. **Broadly-needed generator features** (`ideas.md` §9): multi-cluster path (F-2, kills hardcoded `c1`), fragment/`unique_segments` emission off the existing `lineMap` (F-3), 2-D difficulty + `score_raw` (E-8, fixes score saturation), `interference_profile` accounting (E-7), region_sloc accounting (E-1…E-5), `intended_refactoring` emission + `refactor_proof.php` harness (E-6/F-12), drift-chain emission (E-11), selector-variant stacking (F-6) + transform-order contract (F-7), size-inflation for big-file/tiny-clone (F-*). Housekeeping H-1…H-12.
4. **`bench/profile.php`** (F-20 / §6 K-34) — the R8 Tool Capability Profile generator (detects/misses/thresholds/FPs/region-accuracy/scaling, one card per tool), and the `progression.json` + `bench --walk` scaffolding (§5 G-8/G-9).
5. **New level dirs** `testsets/L11_*…L20_*` with README stubs (per **D-A do-both**); the L00–L10 families/axes/rungs machinery applies inside them identically.
6. **Retrofit policy:** existing 540 sets **stay v1** (valid under `schema_version enum[1,2]`); new sets emit v2. A one-shot retrofit to v2 is an optional later Foundation task, not a gate.

**GATE (proved, not asserted — §1.2):** an independent Verifier confirms `gen/verify.php --all` green, `gen/build.php --check` byte-clean, all 540 existing sets still valid, and one **pilot set per new mechanism** (a `pd_*`, an `rf_*`, a multi-cluster set, a 10-axis `mix_*`, an L11 tier set) builds with generator-emitted v2 ground truth and passes verify + `refactor_proof`/budget checks. Nothing in §4 starts until this passes.

---

## 4. Phase plan — every `ideas.md` family, scheduled

Execution = the `plan_samples.md` **§18.10 completion loop**, now over the expansion phases below. **Each phase:** (1) **Foundation-extension** — a Foundation agent adds exactly the transforms/seeds/scaffolds/distractors/features this phase's families need, reviewed, must pass; (2) **enumerate** the phase's families (≥5 sets each unless noted); (3) **fan out** ≤10 writer chains, each `build → review → (fix → review)* → done`, max 5 rounds, `BLOCKED.md` on exhaustion; (4) **phase gate** — an independent Verifier confirms `gen/verify.php --level=/--subtree` green + a `profile.php` smoke run; (5) **checkpoint report**, then continue automatically unless told to pause.

| Phase | `ideas.md` coverage | New substrate the Foundation-extension adds | Key v2 metadata exercised |
|---|---|---|---|
| **P1e — Probes & progression** *(measurable-first)* | §6 K-1…K-34 · §5 G-1…G-9 · §25 FP probes · §20-I threshold ladders | `capability_probe` (E-12), `difficulty.requires` additions (E-14), `profile.php` (from P0e), `progression.json`/`--walk` | `capability_probe`, `detection_expectation` isolation |
| **P2e — Partial-duplication & structure** | §3 P-1…P-14 · §12 cluster-topology · §13 granularity ladder · §14 size-class matrix | T-UQ (unique-code injection), fragment engine (F-3), multi-cluster (F-2), sub-method granularities, size-inflation | `region_sloc`, `uniqueness_budget` (min/max), `unique_segments`, `duplication_ratio`, multi-cluster |
| **P3e — Refactorability** | §4 R-1…R-20 · §23.2 | T-RF emitters, `refactor_proof.php` (F-12), refactor-aware scoring (E-13) | `intended_refactoring` (kind/holes/unified_signature/collapses/advisability), `solution/` |
| **P4e — Combinatorial** | §2 C-1…C-18 · §23.1 · §20-H compound table | variant stacking (F-6) + order contract (F-7), legality-at-depth verify (V-10) | `interference_profile` (axis_count, per-carrier), `groups_applied/withheld`, 2-D `difficulty.axes` |
| **P5e — Seeds & semantic equivalence** | §7 S-1…S-26 · §16 (+§16.8 union) · §20-K real-world scenarios | new seeds (`payload.php`+`seed.json`+`equivalence_test.php`+`variants/`), framework-shaped dependency-free seed stubs | `normalized_by:[semantic]`, `difficulty.requires:[semantic_reasoning]` |
| **P6e — New axes & adversarial** | §8 T-SY/T-LEG/T-* + real **NZ/ENC/CP** (26 declared-only) · §17 interference/distractor tiers · §18 edge/adversarial | the new transform classes; `non_duplicates[].bait_class` | real interference codes cited in `interference[]`; encoding/topology probes |
| **P7e — Big content fan-out** | §22 **L11–L20** tiers · §15 genealogy · §20 copilot **A–N** breadth · §21 named flagship sets · §19 hidden-challenge map (60) · §25 negative controls | mostly assembly on prior machinery; per-tier seeds/variants as needed | `drift_generation`/`pairwise_expectation`; all v2 fields in combination |
| **P8e — Integration gate** | finalize | — | — |

Notes:
- **P1e is deliberately first** so every later wave lands measurable against `profile.php` (`ideas.md` §24).
- **Low-dependency breadth families** (§20 copilot **A–F**: `ws_*`/`cm_*`/`rn_*`/`lt_*`/`st_*`/`cf_*` using existing transforms) need no new substrate and **may be pulled earlier** to fill idle concurrency slots in any wave.
- **L11–L20 tiers (P7e)** reuse P0e–P6e machinery; each tier cross-references its fable equivalent (`ideas.md` §22). Partial-duplication stays L05-extension + `partiality` axis, **not** a tier (D-C).
- **P8e Integration:** rebuild `manifest.json` + aggregate ground truth (now spanning L00–L20, multi-cluster, fragments, drift, refactoring), whole-tree verify + bench (capture tool versions), generate the **Tool Capability Profiles** (F-20) + all matrices + `progression.json`, write the golden baseline (no git), finalize docs (per-level READMEs incl. L11–L20; append-only root README). `samples.json` index repair was done in the prior campaign; only touch if still pending.

---

## 5. Definition of Done — additions for v2 families

On top of the §18.7 Reviewer checklist and the §12 `gen/verify.php` checks (both inherited verbatim), a v2 set is DONE only when **also**:

- **Schema v2.** `schema_version:2`; every new field validates; any emitted **flat mirror** equals its rich source (D-B).
- **Partial-duplication (`pd_*`, §3).** `region_sloc` sums to file SLOC; `unique_segments` are generator-emitted (from `lineMap`), fall within the declared `uniqueness_budget` min/max, and `varied_segment_count` matches; each **shared** fragment carries its own positive proof, each **unique** segment provably does not match across carriers (no ≥40-token normalized run).
- **Refactorability (`rf_*`, §4).** `refactor_proof.php` is green — the `solution/Unified.php` with its declared holes reproduces every member's behavior against shared inputs; `intended_refactoring.kind` is in the enum; holes/collapses reference real members. Anti-refactor controls (R-17) prove they must **not** be unified.
- **Combinatorial (`mix_*`, §2).** Legality-at-depth (V-10): after all N stacked axes the members are still a true clone of the declared type, and no accidental ≥40-token cross-run with traps; `interference_profile.axis_count` matches the applied chain; `groups_withheld` (if declared) is genuinely absent.
- **Topology/genealogy.** Each cluster in a multi-cluster `expected.json` independently validates; `drift_generation` + `pairwise_expectation` present and consistent with degrading Jaccard.
- **Probes (§6).** `capability_probe` isolates exactly one `difficulty.requires` capability; the paired control set differs only by that capability.

Every one of these is confirmed by an **independent Verifier/Reviewer** (§1.2), never by the builder.

---

## 6. Agent-prompt deltas (append to the `plan_samples.md` §20 blurbs)

Assemble each subagent prompt exactly as §20.0 (contract + role prompt + shared block + pasted §-excerpts). Append these campaign deltas:

- **Every agent (shared block add):** "You run under `plan_ideas.md`. Reads/verification/research are delegated — if you need to know something outside your inputs, spawn a read-only helper or report the gap; do not assume. ≤10 concurrent writers; you are the *only* writer of your file(s); shared substrate is read-only unless you are Foundation; **never run git**; nesting is allowed but never adds a second writer to any file."
- **Foundation delta:** paste `ideas.md` §1 (E/V), §8 (T), §7+§16.8 (S), §9 (F/H), and the D-A/D-B decisions. Build **optional** v2 fields (keep v1 valid); prove determinism; deliver `profile.php`. Split by area into parallel sub-agents if helpful (§1.4). Do not build set content.
- **Set-Builder delta (by family type):** paste the relevant `ideas.md` section (e.g. §3 for `pd_*`, §4 for `rf_*`, §2 for `mix_*`, §22 for a tier) + the §5 DoD additions. Emit the v2 metadata for that family; for `rf_*` author the `solution/` first and let `refactor_proof.php` verify the label; never hand-type budgets/line numbers — regenerate.
- **Reviewer delta:** run the §5 additional checks (budget conformance, `refactor_proof`, legality-at-depth, multi-cluster validation) in addition to §18.7; default to FAIL on any unverified v2 claim.

---

## 7. Completion loop & goal state

Drive via the `plan_samples.md` §18.10 loop over P1e→P7e, then P8e once. **Idempotent & resumable:** on restart, skip any set whose dir exists with a Reviewer PASS; keep a progress ledger (done / in-review / blocked / pending). ≤10 concurrent writers throughout; every set reviewed; BLOCKED sets logged and surfaced, never skipped silently, never blocking others.

**Goal state (campaign complete):**
- Every `ideas.md` family (§2–§6, §12–§25) and every adopted L11–L20 tier (§22) has **≥5 DONE sets** (Reviewer PASS).
- `testsets/manifest.json` spans **L00–L20** and totals reach the agreed target (**≈920 → 1000+** sets, `ideas.md` §24).
- Whole-tree `gen/verify.php` green (incl. V-1…V-10); `gen/build.php --check` byte-clean; all 540 legacy v1 sets still valid.
- **Tool Capability Profiles** (F-20), all `bench` matrices, and `progression.json` generated.
- Documentation DoD (`plan_samples.md` §19) satisfied for the new material (per-level READMEs incl. L11–L20; root README append-only).
- Tree left **uncommitted** for human review (§1.5). Final report: total sets, BLOCKED list w/ reasons, headline capability matrix + progression walk.

---

## 8. Appendix — full family → phase index (completeness audit)

Every `ideas.md` idea-group, and the phase that builds it. If it's in `ideas.md`, it's here.

| `ideas.md` group | IDs / content | Phase |
|---|---|---|
| §1 Enabling metadata & schema | E-1…E-18, V-1…V-10 | **P0e** |
| §8 Transform codes | T-SY, T-LEG, T-UQ, T-RF, NU/BL/CF/ST/LT/API additions | P0e (core) + **P6e** (new axes) |
| §9 Generator features / housekeeping | F-1…F-24, H-1…H-12 | **P0e** (+ per-phase extensions) |
| §6 Capability probes | K-1…K-34 (+ `profile.php` K-34) | **P1e** |
| §5 Progression machinery | G-1…G-9 (rungs, arcs, grid, `progression.json`, `--walk`) | **P1e** |
| §25 Negative controls / FP probes | clean + self-contained near-miss families | **P1e** (probes) + **P7e** (L0 extension) |
| §20-I Threshold ladders | token/line ±1, %-overlap, id-count | **P1e** (feeds probes) |
| §3 Partial-duplication | P-1…P-14 | **P2e** |
| §12 Cluster-topology & instance-count | 12.1–12.9 (multi-cluster, instance ladder, intra-file, ratio, nested, overlap, subset, twin, confusable) | **P2e** |
| §13 Granularity ladder | expression → statement → block → class → file → dir-tree | **P2e** |
| §14 Size-class matrix | XS–XXL, homogeneous/heterogeneous/mixed | **P2e** |
| §4 Refactorability | R-1…R-20 | **P3e** |
| §23.2 Refactorability additions | parametric spectrum, exploded-vs-composed (new kind), business-extraction | **P3e** |
| §2 Combinatorial | C-1…C-18 | **P4e** |
| §23.1 Combinatorial additions | combo templates A/B/C, "groups-withheld" partial-normalization | **P4e** |
| §20-H Compound-interference table | ws+cm, ws+rn, … (2–4 axis breadth) | **P4e** |
| §7 Seeds | S-1…S-26 + seed-format extensions | **P5e** |
| §16 Domain & cross-semantic equivalence | 16.1–16.7 + §16.8 seed/domain union | **P5e** |
| §20-K Real-world scenario sets | order flow, auth flow, rate-limit, … | **P5e** |
| §17 Interference / noise / distractor tiers | 17.1 (4 bait classes)–17.6 | **P6e** |
| §18 Edge-case & adversarial | 18.1–18.8 (homoglyph, line-split, heredoc, minified, generated, boilerplate…) | **P6e** |
| §22 L11–L20 tiers | Deep-rename … Kitchen-sink (a/b/c) | **P7e** |
| §15 Clone genealogy & drift | genesis→single-edit→progressive→branch→convergence→partial-revert | **P7e** |
| §20 A–N per-category families | A ws · B cm · C rn · D lt · E st · F cf · K real-world · L tool-blind-spot | **P7e** (A–F may pull earlier) |
| §21 Named flagship sets | Tax Zoo, Seven Samurai, Matryoshka, Clone Marriage, Evolutionary, Twin Paradox, Mass-Dup, DI Zoo, Inclusion, Framework-Xlate | **P7e** |
| §19 Hidden-challenge coverage | the 60 challenges → sets | **P7e** |
| §26 Metadata reconciliation (flat mirrors) | flat fields as optional derived mirrors | **P0e** (schema) |
| §27 / §24 Integration, profiles, docs | manifest, profiles, matrices, docs | **P8e** |

*Nothing in `ideas.md` is unscheduled. Where a family depends on a not-yet-built transform/seed/feature, its phase's Foundation-extension builds that first (§4 step 1); a builder that still finds a gap stops and reports (§1.3).*
