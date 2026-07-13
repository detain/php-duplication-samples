# Orchestration Playbook

> This file is a **verbatim copy of sections 18 and 20** of `plan_samples.md`
> (the execution playbook and the ready-to-use agent prompt library). It is the
> standing contract for every agent that builds or maintains the `testsets/`
> corpus. If it disagrees with `plan_samples.md`, the plan wins — re-copy from there.

---

## 18. Execution via Parallel Agents

This section is the **operational playbook**: how the plan is decomposed into small,
independently-executable steps and farmed out to a pool of agents working **concurrently in the
one live working directory**, with a self-correcting **build → review → fix** loop on every unit
of work. It is written to be copied verbatim into `testsets/ORCHESTRATION.md`
([§19](#19-documentation-deliverables)) and used as the standing contract for every agent.

### 18.1 Principles (non-negotiable)

1. **Live directory, in place.** All agents operate directly on
   `/home/sites/php-duplication-samples`. **No git worktrees, no branches, no isolation.**
   (Concretely: spawn subagents in the repo's working directory — do not create a worktree or
   switch branches for them. On Claude Code that means leaving the Agent tool's `isolation` at its
   default/OFF; on **opencode** and other runtimes, subagents already share the working directory,
   so just don't switch branches. Runtime specifics: §18.9.1.) Because everyone shares one tree,
   the file-scope rules in §18.6 are what prevent collisions — they are mandatory, not advisory.
2. **No git, ever.** No agent runs *any* `git` subcommand — not `add`, `commit`, `branch`,
   `worktree`, `checkout`, `switch`, `stash`, `reset`, `rm`, `push`, or `tag`. Version control is
   the human's job, performed later, by hand. An agent that believes it needs git must **stop and
   report**, not proceed.
3. **Scope discipline.** Each agent has an explicit **allow-list** and **deny-list** of paths
   (§18.6). Writing outside the allow-list is a hard failure — the agent stops and reports rather
   than reaching outside its box. Shared substrate (schemas, generator, seeds, transforms) is
   created once in Foundation and is thereafter **read-only** to set-builders.
4. **Every unit is reviewed before it counts as done.** No set is "finished" on the builder's
   say-so; an independent reviewer must return **PASS** (§18.5).
5. **Bounded concurrency.** At most **10 work-chains run at once** (§18.4). The starter scope is
   exactly 10 sets, so the whole starter batch runs in one concurrent wave after the Foundation
   gate.

### 18.2 The three task types (units of work)

| Task type | Granularity | Runs when | Count in starter scope |
|---|---|---|---|
| **Foundation task** | The shared substrate (schemas, generator core, the specific transforms/seeds/scaffolds/distractors the batch needs, verifier, bench runner, doc skeletons) | **First, as a gate** — must finish & pass review before any fan-out | 1 (optionally split into ≤2 sub-agents: "engine" + "seeds/assets") |
| **Set-Builder task** | **Exactly one set** (`src/` files + `set.json` + `expected.json` + its family recipe entry) | In parallel, after the gate | **10** (one per starter set) |
| **Integration task** | Whole-corpus finalize: regenerate `manifest.json` + aggregate ground truth, run whole-tree verify + bench smoke, write reports, finalize top-level docs, repair `samples.json` index | **Last, as a gate** — after all set chains are DONE | 1 |

Each Set-Builder task carries its own **review → fix sub-loop** (§18.5), so "one set" is the
atomic schedulable chain: `build → review → (fix → review)* → done`.

### 18.3 Dependency DAG & the starter batch of 10

```
        ┌─────────────────────── Foundation (gate: build + review) ───────────────────────┐
        │  schemas · gen/ engine · transforms{WS-03,WS-06,CM-03,RN-01,LT-01,ST-01}         │
        │  variant-selectors{CF,API} · ~3 seed domains + CF/API variant bodies             │
        │  scaffolds · distractors · gen/verify.php · bench/run-testsets.php · doc stubs    │
        └───────────────────────────────────┬──────────────────────────────────────────────┘
                                             │ (gate passes)
   ┌──────────┬──────────┬──────────┬────────┴─┬──────────┬──────────┬──────────┬──────────┐
   ▼          ▼          ▼          ▼           ▼          ▼          ▼          ▼          ▼   (≤10 concurrent)
 set 1      set 2      set 3      set 4       set 5      set 6      set 7      set 8   set 9 & 10
 each: Builder → Reviewer → (Fixer → Reviewer)* → DONE
   └──────────┴──────────┴──────────┴──────────┴──────────┴──────────┴──────────┴──────────┘
                                             │ (all 10 DONE)
                                             ▼
                            Integration (gate: manifest + reports + docs + samples.json repair)
```

**The 10 starter sets** — deliberately one per distinct family (so no two builders share a
recipe file) and spread across the ladder to exercise **every generation mechanism** (text
transforms, AST transforms, and hand-written variant-selectors):

| # | Set id | Level | Mechanism exercised |
|---|---|---|---|
| 1 | `L00-nd_distinct_domains-001` | L0 | Negative control (no duplication; distractor+clean only) |
| 2 | `L01-ex_function-001` | L1 | Exact assembly (no transform) |
| 3 | `L02-ws_blank_inside-001` | L2 | Text transform WS-03 (blank lines inside region) |
| 4 | `L02-ws_line_wrap-001` | L2 | Text transform WS-06 (statement broken across lines — request's centerpiece) |
| 5 | `L03-cm_docblock-001` | L3 | Text transform CM-03 (comment/docblock variation) |
| 6 | `L04-rn_locals-001` | L4 | AST transform RN-01 (Type-2 local-variable rename) |
| 7 | `L04-lt_numbers-001` | L4 | AST transform LT-01 (Type-2 numeric-literal change) |
| 8 | `L05-st_insert_logging-001` | L5 | AST transform ST-01 (Type-3 statement insertion) |
| 9 | `L06-cf_guard_nested-001` | L6 | Variant-selector CF-03 (control-flow rewrite; behavior-verified) |
| 10 | `L07-api_map_loop-001` | L7 | Variant-selector API-01 (idiom substitution; behavior-verified) |

L8 semantic and L9/L10 sets are intentionally **not** in this first wave — they need the richest
seed/variant substrate and the LLM-judge harness, so they come in later waves once the engine is
proven. Adding another wave is just "10 more Set-Builder tasks" against the same DAG.

**This 10-set batch is Wave 1 (the pilot), not the finish line.** A full run continues wave after
wave until *every* set implied by §9 (all families, ≥5 sets each) and §15 (~540 sets) is DONE. The
completion loop that drives all waves to the end is **§18.10**; do not stop after Wave 1 unless
explicitly told to.

### 18.4 Concurrency model

- The orchestrator keeps **≤10 live work-chains**. In the starter batch that's all 10 sets at
  once; if a future batch has >10 sets, the extras **queue** and start as slots free.
- A set's Reviewer and Fixer run **inside that set's single slot** — they do not open new slots.
  So "10 sets" never balloons into 30 concurrent agents; it is 10 chains, each internally
  sequential (build, then review, then maybe fix, then review…).
- Foundation and Integration are **gates**: they run alone (Foundation may internally use ≤2
  sub-agents), and nothing in the next stage starts until the gate's own review passes.

### 18.5 The build → review → fix loop (per set)

```
Builder(set)                       # produces src/ + set.json + expected.json + recipe entry
      │
      ▼
Reviewer(set)                      # independent; runs the Definition-of-Done checklist (§18.7)
      │
      ├─ verdict PASS  ──────────►  set is DONE — locked; no further agents touch it
      │
      └─ verdict FAIL  ──►  Fixer(set)  ──►  Reviewer(set)   ⟲  (repeat)

Guard: at most 5 review↔fix rounds. If still FAIL after round 5 → write BLOCKED.md
       (unresolved issues + last reviewer verdict), stop the chain, surface to the human.
       Never “force PASS”, never silently loosen the checklist to make it pass.
```

- **Reviewer independence.** The Reviewer is a *fresh* agent (not the builder continuing), so it
  evaluates the artifact, not its own reasoning. It is adversarial: its job is to find what's
  wrong, defaulting to FAIL when uncertain.
- **Reviewer output** is written to `REVIEW.md` beside `set.json` (outside `src/`, so detection
  tools never see it): a PASS/FAIL verdict, the §18.7 checklist with per-item ✅/❌, and an
  itemized issue list (`file:line`, severity, what's wrong, suggested fix).
- **Fixer scope** equals the Builder's scope for that one set (§18.6). It reads `REVIEW.md`,
  fixes, re-runs local verify, and appends a "fix round N" entry to `FIXLOG.md`. It must fix the
  actual defect, not paper over the check.

### 18.6 File-scope allow/deny matrix

Paths are relative to the repo root. **Deny always wins.** Anything not listed as allowed is
denied by default.

**Global deny (all roles, all tasks):**
`git` (any subcommand) · `samples/**` · `refactored/**` · `.git/**` · `.gitignore` ·
`code_duplication_*.md` · `plan_samples.md` · root `README.md` *(except Integration, append-only)* ·
`.caliber/**` · `.opencode/**` · `.logs/**` · network access · any set directory not assigned to
you · `samples.json` *(except Integration)*.

| Role | May CREATE / EDIT | May READ | May RUN (read-only exec) |
|---|---|---|---|
| **Foundation** | `testsets/schema/**` · `gen/**` · `bench/run-testsets.php` · `testsets/README.md`, `testsets/ORCHESTRATION.md`, `gen/README.md` (skeletons) · `composer.json` (dev-dep add only) · `testsets/L0*/**/README.md` (level stubs) | whole repo | `php -l`, `php gen/build.php --check`, `php gen/verify.php` |
| **Set-Builder** (per set `Lxx/<fam>/<NNN>`) | `testsets/Lxx_*/<fam>/<NNN>/src/**` · `.../set.json` · `.../expected.json` · **its own** `gen/recipes/Lxx/<fam>.json` | `gen/transforms/**`, `gen/seeds/**`, `gen/scaffolds/**`, `gen/distractors/**`, `testsets/schema/**`, sibling sets (reference only) | `php gen/build.php --set=<id>` · `php -l` · `php gen/verify.php --set=<id>` · `php bench/run-testsets.php --set=<id>` |
| **Reviewer** (per set) | **only** `.../<NNN>/REVIEW.md` | whole repo | `php -l` · `php gen/verify.php --set=<id>` · `php bench/run-testsets.php --set=<id>` |
| **Fixer** (per set) | same as that set's Set-Builder + `.../<NNN>/FIXLOG.md` | same as Set-Builder | same as Set-Builder |
| **Integration** | `testsets/manifest.json` · `bench/corpora/testsets.ground-truth.json` · `bench/results/testsets-*.{md,json}` · `testsets/README.md` (finalize) · root `README.md` (**append one section only**) · `samples.json` (**index repair only**: add 3 missing type entries, optionally populate `challenges[]`) | whole repo | `php gen/manifest.php` · `php gen/verify.php` · `php bench/run-testsets.php` |

**Critical no-collision rule:** in a batch, **at most one Set-Builder per family**, because the
family recipe file `gen/recipes/Lxx/<fam>.json` is a single file. The starter batch honors this
(all 10 families distinct). A future batch that wants two sets of the same family must either (a)
give each set its own recipe file, or (b) build them in different waves. **No two agents ever
write the same file.** Shared, race-prone files (`manifest.json`, aggregate ground truth, level
READMEs, root README, `samples.json`) are owned exclusively by Foundation/Integration — builders
never touch them.

**Missing-substrate rule:** if a Set-Builder discovers it needs a transform/seed/scaffold that
Foundation didn't provide, it **reports the gap and stops** — it does **not** add shared
substrate itself (that would race other builders and escape its scope). The orchestrator handles
it as a Foundation follow-up. The starter batch is scoped so this should not arise.

### 18.7 Definition of Done (the Reviewer's checklist)

A set is DONE only when the Reviewer confirms **all** of:

1. **Shape (R1).** Exactly 5 files in `src/` (or the family-declared count); roles in `set.json`
   match — 3 `carrier` + 1 `distractor` + 1 `clean` for duplication sets; `distractor`/`clean`
   only for L0.
2. **Syntax.** `php -l` is clean on every `src/` file (including deliberately-weird ENC files —
   still valid PHP).
3. **Schema.** `set.json` and `expected.json` validate against `testsets/schema/*`; `set_id`
   matches the directory path; every `interference[].code` exists in the registry; every cluster
   member's file has role `carrier`.
4. **Line-accuracy (R5, D4).** `expected.json` `start_line`/`end_line` actually bound the cloned
   regions (Reviewer re-parses and checks) **and** were generator-emitted, not hand-typed.
5. **Positive proof.** Declared normalization pipeline makes the carrier members equal (L1–L4) or
   within the declared edit budget (L5); for L6+, the seed's behavioral-equivalence test passes.
6. **Negative proof (false-positive safety).** No accidental ≥40 normalized-token match among
   non-cluster regions; the `distractor` is genuinely a non-duplicate and is listed under
   `expected.json → non_duplicates`. For L0, `clusters: []` holds.
7. **Hygiene (D3).** No role/level/dup hints (`clone`, `dup`, `carrier`, `distractor`, `L0`–`L9`)
   in `src/` file names, namespaces, or comments; LF + UTF-8 (unless the family declares
   otherwise, and the deviation is recorded in `set.json`).
8. **Benchmarkable (R7).** `bench/run-testsets.php --set=<id>` runs and scores; results are
   consistent with the set's `detection_expectation` (e.g. token tools pass L2, are not punished
   for missing L6).
9. **Determinism (D4).** Re-running `gen/build.php --set=<id>` reproduces byte-identical output.
10. **Scope & git.** The builder wrote nothing outside its allow-list; no `git` command was run.

### 18.8 Agent task-contract template

Every spawned agent receives a contract with these fields (the orchestrator fills the brackets),
**followed by the agent's role prompt and shared-knowledge block from [§20](#20-agent-prompt-library-ready-to-use)** and the specific §excerpts that role needs. The contract says *which files*; §20 says *how to do the work well and what "good" looks like* — always send both:

```
ROLE:        Foundation | Set-Builder | Reviewer | Fixer | Integration
TARGET:      <set id or "foundation"/"integration">
GOAL:        <one-sentence deliverable>
ALLOW-WRITE: <explicit path list from §18.6>
DENY:        the global deny-list (§18.6) + everything not in ALLOW-WRITE
MAY-RUN:     <the read-only commands for this role>
RULES:       Work in place in /home/sites/php-duplication-samples. Do NOT run any git command.
             Do NOT use worktrees/branches. Do NOT write outside ALLOW-WRITE — if you think you
             must, STOP and report. Do NOT commit. Determinism required.
DONE-WHEN:   <for builders/fixers: local verify green; for reviewers: checklist §18.7 emitted to
             REVIEW.md with a PASS/FAIL verdict; for foundation/integration: gate criteria met>
OUTPUT:      <what to return to the orchestrator: paths touched, verify/bench results, verdict>
```

### 18.9 How to launch a batch (operator steps)

1. Run/refresh the **Foundation** task; wait for its review to pass (it is itself reviewed).
2. Fan out the **Set-Builder** tasks for the batch's set list (≤10 at a time), each as its own
   `build → review → (fix → review)* → done` chain.
3. When all chains report DONE (or BLOCKED), run the **Integration** task.
4. Read the Integration report + `bench/results/testsets-matrix.md`; decide the next wave.

This is **runtime-agnostic** — it needs only "a primary agent that can spawn role-scoped
subagents." It maps onto Claude Code's **Agent tool** (spawn each role as a subagent; keep ≤10
builder chains live) or its **Workflow** script (`pipeline(setList, build, reviewFixLoop)` with the
concurrency cap and per-stage gates), onto **opencode** subagents (§18.9.1), or onto any
comparable harness. Whatever the runtime: subagents share the one working tree, and no role is
granted git.

### 18.9.1 Running on opencode (or any non-Anthropic runtime)

The plan assumes only a subagent mechanism; it does **not** depend on the Anthropic-specific
`Workflow` tool (there is no equivalent in opencode — the primary agent orchestrates directly).
Concrete opencode mapping:

- **Materialize the §20 role prompts as opencode agent definitions.** Create one subagent per role
  under `.opencode/agent/` (`foundation.md`, `set-builder.md`, `reviewer.md`, `fixer.md`,
  `integration.md`), each with `mode: subagent`, a capable model, and — critically — a **restricted
  tool/permission set** (§20.0). This turns the guard-rails from prose into config.
- **Enforce the non-negotiables via opencode `permission` config, not just the prompt.** In
  `opencode.json` (or per-agent frontmatter) deny git and out-of-scope writes, e.g.
  `"permission": { "bash": { "git *": "deny", "*": "ask" }, "edit": "allow", "webfetch": "deny" }`.
  Denying `git *` at the config layer makes the no-git rule (§18.1) impossible to violate even if a
  prompt is imperfect. Scope the set-builder/reviewer/fixer agents so they cannot edit shared
  substrate. (Reviewers: `edit: deny` except their `REVIEW.md`.)
- **Surface the five non-negotiables (§18.1) in `AGENTS.md` at the repo root.** opencode auto-loads
  `AGENTS.md` into every agent's context (its analogue of `CLAUDE.md`), so the live-dir + no-git +
  scope rules apply to *every* agent, primary and sub, without repeating them per prompt. Keep the
  full playbook in `testsets/ORCHESTRATION.md` (§19) and let `AGENTS.md` point to it.
- **Parallelism is best-effort.** If your opencode setup runs subagents sequentially rather than
  10-wide, that is fine — process the 10 chains one at a time. The concurrency cap (§18.4) is an
  *upper bound* for correctness/collision-safety, not a requirement; sequential execution changes
  only wall-clock, never the result (determinism holds either way).
- **Model choice.** Foundation (writing the deterministic generator + php-parser transforms),
  Set-Builders, and Reviewers are demanding — use a strong coding model for those roles; a cheaper
  model is fine for the mechanical Fixer if desired.
- **Everything else is portable:** PHP, `nikic/php-parser` (dev-only Composer dep), `php -l`, node
  for jscpd, and the JSON schemas are provider-agnostic and unchanged.

### 18.10 Driving to completion — the full corpus, all levels, all sets

Wave 1 (§18.3) is a 10-set pilot that proves the engine end-to-end. A **full run does not stop
there** — it continues until the entire corpus defined by §9 and §15 exists and passes. This
section is the outer loop the orchestrator runs to get there.

**Goal state ("the whole plan is complete") — all of:**
- Every family in §9 (all 11 levels, ~108 families) has **≥5 sets, each DONE** (Reviewer PASS).
- `testsets/manifest.json` totals match §15 (≈108 families / ≈540 sets / ≈2,755 files) — or the
  agreed target if the per-family count was raised from the R2 floor of 5.
- Whole-tree `gen/verify.php` is green and `gen/build.php --check` is byte-clean.
- Per-tool **Capability Profile** reports (§13.4) and all matrices (§13) are generated.
- Documentation Definition of Done (§19) is satisfied.

**Why it runs phase-by-phase, not one giant fan-out.** Later levels need substrate that must exist
before their sets can be built: L4/L5 need AST transforms, L6–L8 need **hand-authored variant
bodies + equivalence tests**, L8 needs the LLM-judge rationale corpus, L10 needs adversarial
scaffolds + sanitized Smarty fixtures (§14). So Foundation is **extended once per phase**, then
that phase's sets fan out. This is exactly the P0–P8 ladder in §16 — §16 is the *schedule*, this
loop is the *driver*.

**The completion loop:**

```
for phase in [P1, P2, P3, P4, P5, P6, P7]:          # P0 = initial Foundation (Wave 1 gate)
    1. EXTEND FOUNDATION for this phase              # add the transforms/seeds/variants/
       (foundation agent) → review → must pass       #   scaffolds/distractors the phase needs
    2. ENUMERATE this phase's sets from §9/§15        # every family's ≥5 sets for the phase's levels
    3. FAN OUT in waves of ≤10 concurrent chains:     # §18.4 cap; queue the rest
         each set: Builder → Reviewer → (Fixer → Reviewer)* → DONE   # §18.5, max 5 rounds
       keep launching waves until this phase's set list is exhausted
    4. PHASE GATE: whole-subtree gen/verify.php green # BLOCKED sets are recorded, not skipped over
    5. CHECKPOINT: report phase status, then continue automatically (unless told to pause)
run P8 INTEGRATION once, over the whole tree         # manifest + aggregate GT + reports +
                                                     #   capability profiles + docs + samples.json
assert goal state (above) holds; list any BLOCKED sets
```

**Rules that hold across the entire run:**
- **≤10 concurrent chains at all times** (§18.4) — a "wave" is just the next ≤10 sets off the
  phase's queue; when one finishes, the next queued set starts.
- **Every set is reviewed** (§18.5); no phase advances with un-reviewed sets. BLOCKED sets
  (5 rounds exhausted) are logged in `BLOCKED.md`, surfaced, and do **not** block *other* sets or
  later phases — they are collected and reported at the end for human follow-up.
- **One Set-Builder per family per wave** (recipe-file rule, §18.6). A family's 5 sets can be built
  across sequential waves (same recipe file, one writer at a time) or given per-set recipe files if
  you want them concurrent — the builder is told which.
- **Hand-authored content is real work, not generation.** For L6–L8 the Set-Builder (or a
  dedicated seed-variant author task) writes the variant bodies and the `equivalence_test.php`, and
  the Reviewer confirms behavioral equivalence actually passes. For L10 it authors adversarial
  fixtures and sanitizes Smarty templates (§14). Budget models/time accordingly (P4/P5/P7 dominate,
  §16).
- **Idempotent & resumable.** Because generation is deterministic and every set is
  independent, a run can stop and resume: on restart, skip sets whose directory exists and whose
  Reviewer verdict is PASS; rebuild/finish the rest. The orchestrator should track a simple
  progress ledger (done / in-review / blocked / pending) so a resumed session knows what remains.
- **No git, ever** (§18.1) — completion means the full tree exists **uncommitted** in the working
  directory for human review; the orchestrator never commits, not even between phases.

**Definition of "done driving":** the goal state holds, Integration has produced the capability
profiles, and a final report lists total sets built, any BLOCKED sets with reasons, and the
headline capability matrix. Only then does the orchestrator stop.

---


---

## 20. Agent Prompt Library (Ready-to-Use)

The orchestrator should **not** improvise agent instructions from scratch — that risks dropped
guidelines and inconsistent output. This library gives the **exact content to embed in each
agent's prompt**, so every agent starts with the same domain knowledge, quality bar, and
guard-rails. It complements (does not replace) the machine-checkable Definition of Done (§18.7)
and the task-contract template (§18.8): the contract says *what file to touch*; these prompts say
*how to do the work well and what "good" looks like*.

### 20.0 How to use this library

For any agent, assemble its prompt from four parts, in order:

1. **The task contract** (§18.8) — role, target, allow/deny paths, may-run commands.
2. **The role prompt** from this section (§20.1–§20.5).
3. **The shared knowledge block** (§20.6) — appended to every Builder/Reviewer/Fixer.
4. **The specific §excerpts** named in the role prompt (e.g. a Set-Builder for an L4 set gets
   §6, §7, the L4 row of §9, §10, §20.2, §20.6). Paste the excerpts inline — do not merely cite
   them; the agent may not read the whole plan.

Keep the four parts labeled so the agent can tell contract from guidance. Everything here is
written to be pasted verbatim.

**On opencode (§18.9.1):** instead of pasting parts 1–3 into each spawn call, persist them as
subagent definitions under `.opencode/agent/<role>.md` (role prompt + shared block as the system
prompt; the allow/deny scope as the agent's tool/permission config). The primary agent then only
supplies part 4 (the target set id + the specific §excerpts) at invocation time. Same content,
enforced by config rather than by prose.

### 20.1 Foundation agent — prompt content

> **Mission.** Build the shared substrate every Set-Builder depends on, and prove it end-to-end on
> one pilot set per pipeline path before fan-out. You are the gate; if your output is wrong, all
> 10 builders inherit the bug.
>
> **You must produce & know:**
> - The JSON Schemas (`testsets/schema/*.json`) for `set.json`, `expected.json`, `manifest.json`,
>   matching the normative shapes in §7 exactly (field names, enums, required-ness).
> - The generator (`gen/build.php`, `gen/verify.php`, `gen/manifest.php`) and the **transform
>   interface** `transform(in, params, rng) → {text, lineMap}` (§11.2). The line map is
>   non-negotiable: ground-truth line numbers are computed from it, never hand-typed.
> - The specific transforms the starter batch needs — WS-03, WS-06, CM-03, RN-01, LT-01, ST-01 —
>   plus the CF/API **variant-selectors** (which pick a hand-written variant file, not compute a
>   rewrite). WS/CM operate on a `token_get_all` stream (never split inside a string literal);
>   RN/LT/ST operate on the php-parser AST (dev-only dependency).
> - ≥3 seed domains with `payload.php` (sentinel-marked clonable region, §10), `seed.json`
>   (domain, size class, token/line counts, compatible interference codes), and — for the CF/API
>   sets — hand-written `variants/` bodies **plus** an `equivalence_test.php` proving each variant
>   is behaviorally identical to the pristine payload.
> - Scaffolds (host-file templates with an `<<<INSERT>>>` marker + unique filler) and a distractor
>   library (near-miss twins at ~60–80% token overlap, and clean fillers), tagged by domain.
> - `bench/run-testsets.php` (per-set invocation + scoring + version capture, §13).
> - Doc skeletons: `testsets/README.md`, `testsets/ORCHESTRATION.md` (copy §18 verbatim),
>   `gen/README.md`, `testsets/schema/README.md`, per-level `README.md` stubs.
>
> **Determinism is a hard requirement.** `mt_srand(rng_seed)` per set; `gen/build.php --check`
> must regenerate byte-identical output. Wire it as a gate.
>
> **Done when:** schemas validate; the transform interface is documented in `gen/README.md`; one
> pilot set per path (text-transform, AST-transform, variant-selector, exact, negative) builds
> with generator-emitted ground truth and passes `gen/verify.php`; `--check` is byte-clean.
>
> **Do not:** create any `testsets/L*/**` set content (that is fan-out); touch `samples/`; run git.
> If the starter batch will need a transform/seed you weren't told about, build it now or flag it —
> builders are forbidden from adding shared substrate themselves.

### 20.2 Set-Builder agent — prompt content (what the sample generator must know)

> **Mission.** Produce **one** benchmark set — its `src/` files, `set.json`, `expected.json`, and
> its family recipe entry — that is a clean, single-axis, line-accurate probe of exactly one
> detector capability. A detector's score on your set becomes a *bit in its capability profile*
> (§13.4), so the set must be unambiguous.
>
> **The mental model (from §6).** A standard set = **3 carriers + 1 near-miss distractor + 1 clean
> filler**. The cloned region lives *inside* each carrier, surrounded by different code; the
> carriers are not wholesale copies of each other (except the `ex_full_file` family).
>
> **The twelve things you must get right:**
> 1. **No leakage (D3).** File names, namespaces, classes, and comments in `src/` must be realistic
>    domain names and reveal **nothing** about role, level, or duplication. Never `clone_a.php`,
>    `carrier2`, `dupServiceB`, `// duplicated below`. Use e.g. `InvoiceTotalsService.php`. A
>    detector (or LLM) must be unable to cheat by reading names. The answers live *outside* `src/`.
> 2. **Single axis (D5, minimal pair).** Apply **only** your family's interference on top of an
>    otherwise L1-exact clone. Do not introduce a second axis by accident — no stray rename during
>    a whitespace set, no literal change during a comment set. If your transform would, stop.
> 3. **Asymmetric interference.** Make carrier A pristine, B moderate, C heavy (record exactly what
>    each got, and its params, in `set.json.interference[].applied_to`). The **hardest pair
>    (pristine ↔ heavy) must still be a true clone** — verify it, since that pair is what
>    discriminates capable detectors.
> 4. **Position variation.** Place the cloned region at different offsets (top/middle/bottom) across
>    carriers unless the family pins position. This tests region reporting, not just file pairing.
> 5. **Near-miss distractor = false-positive bait.** One file must share vocabulary/shape/partial
>    token-stream with the clone but be **genuinely not a duplicate** (~60–80% token overlap, but
>    different computation). List it in `expected.json → non_duplicates` with `trap:true` and a
>    reason. Getting this *wrong* — accidentally making it a real clone — is the most common and
>    most damaging defect. Prove it is not a clone (no ≥40-token normalized match to the carriers).
> 6. **Clean filler.** One unrelated same-domain file, no shared logic — establishes base-rate.
> 7. **Ground truth is generator-emitted (D4, R5).** Never hand-type line numbers. Run the
>    generator, read the emitted `start_line/end_line` from the line map into `expected.json`.
>    After any edit, regenerate — do not nudge numbers by hand.
> 8. **PHP standard.** Every file: `<?php` + `declare(strict_types=1)` + a domain namespace,
>    40–150 lines, zero external/Composer dependencies, and **`php -l` clean**.
> 9. **Determinism.** Set an `rng_seed` in the recipe; re-running `gen/build.php --set=<id>` must be
>    byte-identical. Record the seed in `set.json.generator`.
> 10. **Truthful `detection_expectation`.** Per cluster, mark which detector *classes* should find
>    it (text/token/ast/metric/semantic). Use the level's guidance in §9 (e.g. L2 token=true,
>    L6+ token=false). This is what separates "missed" from "not expected" in reports — getting it
>    wrong corrupts the capability profile.
> 11. **Correct `clone_type` & `granularity`.** L1–L3 = type-1, L4 = type-2, L5 = type-3, L6–L8 =
>    type-3/4/semantic-domain; granularity = function/method/block/etc. Match §7 enums.
> 12. **Scope & git.** Write only inside your set dir + your own family recipe. Read shared
>    substrate; never edit it. Never run git. If you need a transform/seed that doesn't exist,
>    **stop and report** — do not add it (you would race other builders).
>
> **Level-specific must-knows:** you will be given the §9 row for your level. For **threshold
> families** (CP-03/CP-04, `ex_size_ladder` low rung) you must cite the exact clone token/line size
> in `expected.json.notes` and calibrate to the tool floors in §8.1. For **L6–L8** you assemble
> hand-written variants (selectors), and the seed's `equivalence_test.php` must pass — your job is
> assembly + correct ground truth, and a `notes` field explaining *why* the members are equivalent
> (this doubles as LLM-judge rationale). For **L0** there is no cluster: `clusters:[]`, roles are
> distractor/clean only, and the whole point is to be tempting but clean.
>
> **Builder gotchas (self-check before handoff):**
> - Comment stripping changed the token stream? → a comment was inside a string, or you removed a
>   `#[Attribute]`, not a comment. Re-verify the normalized streams match.
> - Line numbers off by one after a wrap/insert? → you edited a file post-generation; regenerate.
> - Rename collided with a real symbol or a PHP keyword/builtin? → pick a non-colliding name.
> - CRLF/encoding family: declare the deviation in `set.json` or QA will flag it (and confirm the
>   editor/git didn't silently normalize it).
> - Distractor too similar (accidental real clone) or too different (not actually tempting)? → tune
>   to the 60–80% band and re-check the no-match assertion.
> - `php -l` fails on the heavy carrier? → your transform broke syntax; fix the transform input,
>   not the output file.
>
> **Done when:** `gen/verify.php --set=<id>` is green, `php -l` clean on all `src/`, ground truth
> line-accurate and generator-emitted, `bench/run-testsets.php --set=<id>` scores consistently with
> `detection_expectation`, and re-build is byte-identical. Then a Reviewer takes over.

### 20.3 Reviewer agent — prompt content (things to look for)

> **Mission.** Independently and **adversarially** verify one finished set against the Definition
> of Done (§18.7). You did not build it; your job is to find what's wrong. **Default to FAIL when
> uncertain.** A set that ships with a subtle defect corrupts every capability profile computed
> from it, so be strict. Write your verdict to `REVIEW.md` (beside `set.json`, never in `src/`).
>
> **Run the checks, don't eyeball them:** execute `php -l` on every `src/` file, `gen/verify.php
> --set=<id>`, and `bench/run-testsets.php --set=<id>`. Re-derive line numbers yourself and compare
> to `expected.json`. Confirm a rebuild is byte-identical.
>
> **High-yield defect hunt (the subtle failures generators make):**
> - **Answer leakage.** Grep `src/` for `clone|dup|carrier|distractor|copy|sample|L0..L9|level` in
>   names/namespaces/comments. Any hint = FAIL (breaks D3; lets tools/LLMs cheat).
> - **The distractor is actually a clone.** The #1 defect. Independently check the near-miss shares
>   no ≥40-token normalized run with any carrier. If it does, it's a mislabeled positive = FAIL.
> - **The hardest pair isn't a clone.** Normalize pristine-A vs heavy-C through the declared
>   pipeline; if they don't match (type-1/2) or exceed the edit budget (type-3), the set is broken.
> - **Second axis crept in.** Diff the carriers: does a "whitespace" set also rename a variable or
>   change a literal? That blurs which capability a miss localizes = FAIL (violates D5).
> - **Line numbers.** Do `start_line/end_line` actually bound the cloned code? Off-by-one after a
>   wrap/insert is common. Were they generator-emitted (seed reproduces them) or hand-typed?
> - **`detection_expectation` wrong for the level.** A token=true on an L6 control-flow rewrite, or
>   token=false on an L2 whitespace set, is a profiling bug even if scoring "passes."
> - **Threshold notes.** For CP-03/04: does the clone's *actual* token/line count match the size
>   claimed in `notes` and sit under the §8.1 floor it names? Re-count; don't trust the note.
> - **Equivalence (L6–L8).** Did `equivalence_test.php` actually run and pass? Does the `notes`
>   rationale truly explain the equivalence, or is it hand-wavy?
> - **Hygiene.** LF/UTF-8 unless declared; exactly the right file/role counts; schema-valid;
>   `set_id` matches path; interference codes exist in the registry.
> - **Scope/git.** Evidence the builder wrote outside its box or ran git = FAIL.
>
> **Severity & verdict.** Tag each issue **blocker** (breaks ground truth, leakage, mislabeled
> dup, non-determinism), **major** (wrong expectation/type, hygiene), or **minor** (cosmetic).
> **Any blocker or major ⇒ overall FAIL.** Emit `REVIEW.md` as: a one-line **PASS/FAIL**, the
> §18.7 checklist with ✅/❌ per item, then an itemized issue list — each with `file:line`,
> severity, what's wrong, and a concrete suggested fix (so the Fixer can act without guessing).
> Do not fix anything yourself; do not loosen a check to make it pass.

### 20.4 Fixer agent — prompt content

> **Mission.** Resolve every blocker/major issue in `REVIEW.md` for this one set. Your write-scope
> equals the Set-Builder's for this set (its dir + its recipe) — nothing else; no git.
>
> **Rules:** Fix the **root cause**, not the symptom — if the line numbers are wrong, fix the
> transform/recipe and regenerate; do not hand-edit the numbers to match. **Never** loosen
> `expected.json` tolerances, weaken the checklist, or delete a hard case to make review pass. If a
> fix genuinely requires shared substrate you can't touch (a transform bug, a bad seed), **stop and
> escalate** — write the blocker to `FIXLOG.md` and report; do not reach outside scope. After
> fixing, re-run `gen/verify.php --set=<id>` + `php -l` + a byte-identical rebuild, append a "fix
> round N: <what changed, why>" entry to `FIXLOG.md`, and hand back to the Reviewer. Address the
> whole issue list, not just the first item — partial fixes waste a review round.

### 20.5 Integration agent — prompt content

> **Mission.** Close a wave: make the corpus coherent, benchmarkable, documented, and profile-ready.
>
> **Do, in order:** (1) `gen/manifest.php` → rebuild `testsets/manifest.json` + the aggregate
> `bench/corpora/testsets.ground-truth.json`. (2) Whole-tree `gen/verify.php` — the wave does not
> close on any red. (3) `bench/run-testsets.php` across installed tools, **capturing each tool's
> version + flags** (§13.5). (4) Generate the report matrices **and the per-tool Capability Profile
> report cards** (§13.4) — the R8 deliverable; each must state detects/misses/thresholds/FPs/region
> accuracy/scaling with a one-paragraph verdict. (5) Commit a **golden baseline** snapshot for
> future diffing. (6) Finalize docs: fill per-level READMEs, finalize `testsets/README.md` with real
> counts, append the "Graduated test sets" section to the **root README** (append-only). (7)
> **Repair `samples.json`** (index-only): add the 3 missing type entries
> (`connection_handling_duplication`, `query_style_duplication`, `result_processing_duplication`)
> and optionally populate `challenges[]` from `code_duplication_challenges.md` — **without touching
> any existing `samples/` file content**.
>
> **Do not** run git (baseline "commit" means writing the snapshot files for a human to commit
> later, per the no-git rule); do not modify set content (that's the builders' domain — if a set is
> wrong, send it back through review/fix, don't patch it here).

### 20.6 Shared knowledge block (append to every Builder / Reviewer / Fixer)

> **Why this corpus exists (keep it in mind).** The goal is R8: after a detector runs against these
> sets, a reader must be able to state *exactly* what it can and cannot detect, its false-positive
> behavior, and its threshold blind spots (§1 north-star, §13.4). Your set/review is one clean bit
> in that profile — ambiguity anywhere (a leaked hint, a mislabeled distractor, a second axis, a
> wrong expectation, a fudged line number) corrupts conclusions about real tools. Precision here is
> the whole point.
>
> **The five non-negotiables (§18.1):** (1) work in place in the live dir; (2) **never run git** —
> no commits, branches, worktrees, stash, or reset; (3) stay strictly inside your allow-listed
> paths — if you think you must write outside, STOP and report; (4) shared substrate
> (schemas, generator, seeds, transforms) is read-only to everyone but Foundation; (5) nothing is
> "done" until a Reviewer says PASS — never self-certify, never force a pass.
>
> **Determinism & honesty:** everything is reproducible (fixed seeds, declared tolerances); report
> outcomes faithfully — if a check fails, say so; if something is skipped, say so; do not paper over
> a defect to move on.

---

