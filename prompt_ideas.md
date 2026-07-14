You are the orchestrator/lead for building the ENTIRE corpus EXPANSION described in
./ideas.md TO COMPLETION. The authoritative specs are ./plan_ideas.md (this campaign's
build plan) and ./ideas.md (the idea catalog) in /home/sites/php-duplication-samples.
The execution MACHINERY is ./plan_samples.md §18 (parallel-agent orchestration, file-scope
matrix, build→review→fix loop, Definition of Done) and §20 (the agent prompt library +
shared-knowledge block) — reused wholesale; plan_ideas.md states only the deltas. This
message is your explicit go-ahead to execute the whole expansion — schema v2, the new
transforms/seeds/engine features, every ideas.md family, and the adopted tiers L11–L20.

IMPORTANT — THE L00–L10 CORPUS IS ALREADY BUILT (the prior plan_samples.md campaign: ≈540
verified sets, the gen/ engine — build.php/verify.php/manifest.php + transforms
WS/CM/RN/LT/ST + CF/API variant-selectors + registry.json — 3 seeds, scaffolds, distractors,
bench/run-testsets.php, and the v1 testsets/schema/*). Do NOT rebuild ANY of it. This
campaign EXTENDS that substrate: schema v2 (optional fields; v1 sets stay valid), ~50 new
transform codes (incl. real impls for the 26 declared-only NZ/ENC/CP), ~26+ new seeds,
~24 generator features, bench/profile.php, and the content families of ideas.md — plus the
new tiers L11–L20 (ladder becomes L00–L20). NEVER touch samples/**, refactored/**, or
plan_samples.md/code_duplication_*.md.

DECISIONS (ideas.md §0, already made — do not relitigate): D-A = DO BOTH (keep L00–L10 with
new families + 2-D difficulty.axes + basic→combined rungs, AND add tiers L11–L20). D-B = rich
metadata objects canonical + optional flat mirrors. D-C = partial-duplication is L05-extension
+ the `partiality` axis (L11 = Deep-Rename Stacks). D-D = keep [F]/[O]/[C] provenance tags.

HOW YOU ORCHESTRATE — DELEGATE EVERYTHING (plan_ideas.md §1):
- You (the orchestrator) read ONLY the plans: plan_ideas.md, plan_samples.md (§18/§20 + the
  §-excerpts you paste into subagents), this prompt, and ideas.md. You do NOT open gen/ code,
  testsets/ content, schemas, or registry.json, and you do NOT run php/verify/build/bench
  yourself. EVERY read, research question ("does transform X exist? how does SetBuilder emit
  line numbers?"), verification, build, and benchmark is done by a SPAWNED AGENT that reports
  back. Keep your context to the plans + agent reports.
- TRUST BUT VERIFY: act on a report only after an INDEPENDENT agent corroborates it. For sets
  that is the adversarial Reviewer step (§18.5/§20.3). For anything else you'd take on faith
  ("foundation is green", "this ground truth is line-accurate"), spawn a read-only Verifier to
  re-run/re-derive, or require two agents to agree. Default to FAIL/again on disagreement.
- NESTED AGENTS OK (opencode, plan_ideas.md §1.4): use multiple levels where they help — a
  Phase-Lead runs a phase; Foundation splits into parallel area sub-agents; a Set-Builder
  spawns its own Reviewer/Fixer and read-only Researcher/Verifier helpers. INVARIANT at every
  depth: ≤10 concurrent WRITERS, ONE writer per file/family-recipe, shared substrate read-only
  except Foundation, and NO GIT EVER.

SPAWNING SUBAGENTS (§20.0): assemble each prompt from four LABELED parts — (1) TASK CONTRACT
(§18.8: role, target, ALLOW-WRITE from §18.6, DENY-LIST, MAY-RUN); (2) ROLE PROMPT verbatim
(§20.1 foundation / §20.2 set-builder / §20.3 reviewer / §20.4 fixer / §20.5 integration);
(3) SHARED-KNOWLEDGE BLOCK (§20.6) verbatim; (4) the SPECIFIC §-EXCERPTS the role needs,
pasted inline (never just cited — the subagent has not read the plans), PLUS the ideas.md
section for the family (e.g. §3 for pd_*, §4 for rf_*, §2 for mix_*, §22 for a tier) and the
plan_ideas.md §5 DoD additions + §6 prompt deltas. On opencode you MAY instead materialize
the role prompts as .opencode/agent/<role>.md subagent definitions with permission configs
that DENY `git *` and out-of-scope writes (§18.9.1), then pass only part 4 per invocation.

STEP 1 — Read the plans (only): plan_ideas.md in full; plan_samples.md §18 + §20 (+ the
§-excerpts each role needs); ideas.md §0 (decisions), §1 (schema/metadata), §24 (build order),
and the section for whatever you're about to build. Do NOT read gen/ or testsets/ — delegate.

STEP 2 — SANITY-CHECK THE EXISTING CORPUS VIA AN AGENT (do not run it yourself): spawn a
read-only Verifier to run `php gen/verify.php --all`, `php gen/build.php --check`, and
`php bench/run-testsets.php --all` and report. All must be green / consistent. If anything is
NOT green, STOP and report — do not build on a broken base.

STEP 3 — FOUNDATION P0e (plan_ideas.md §3), as a GATE: spawn a Foundation campaign (may split
into parallel area sub-agents) to deliver schema v2 (E-1…E-18) + verifier checks (V-1…V-10) +
the broadly-needed engine features (multi-cluster, fragment/unique_segments emission, 2-D
difficulty, region accounting, intended_refactoring + refactor_proof.php, drift chains, variant
stacking) + housekeeping (H-1…H-12) + bench/profile.php (F-20) + progression.json scaffolding +
the L11–L20 level dirs/README stubs. GATE: an independent Verifier confirms verify --all green,
--check byte-clean, all 540 legacy sets still valid, and one pilot per new mechanism passes.
Nothing in STEP 4 starts until this passes.

STEP 4 — DRIVE THE PHASES TO COMPLETION via the plan_samples.md §18.10 loop over
plan_ideas.md phases P1e..P7e (in order: probes+progression → partial-dup+structure →
refactorability → combinatorial → seeds+semantic → new-axes+adversarial → big fan-out incl.
L11–L20/genealogy/breadth/flagship/hidden-challenges/negatives). For EACH phase —
  (1) FOUNDATION-EXTENSION: a Foundation agent adds exactly the transforms/seeds/scaffolds/
      distractors/features that phase's families need (real authoring for L6–L8 variants +
      equivalence tests, L10/adversarial fixtures). Review; must pass before fan-out.
  (2) ENUMERATE the phase's families (≥5 sets each) from ideas.md + §9/§15.
  (3) FAN OUT in waves of ≤10 writer chains (one Set-Builder per family per wave), each
      build → review → (fix → review)* → done, max 5 rounds; BLOCKED.md on exhaustion.
  (4) PHASE GATE: an independent Verifier confirms subtree verify green + a profile.php smoke.
  (5) CHECKPOINT: report phase status, then continue automatically unless I say pause.
Then run P8e INTEGRATION once over the whole tree (§20.5, extended): rebuild manifest.json +
aggregate ground truth (L00–L20, multi-cluster/fragments/drift/refactoring), whole-tree verify
+ bench (capture tool versions), generate the Tool Capability Profiles (F-20) + matrices +
progression.json, write the golden baseline (NO git), finalize docs (per-level READMEs incl.
L11–L20; append-only root README).

DONE when the plan_ideas.md §7 goal state holds: every ideas.md family AND every L11–L20 tier
has ≥5 DONE sets; manifest spans L00–L20 and totals reach the agreed target (~920 → 1000+);
whole-tree verify green (incl. V-1…V-10) and --check byte-clean; profiles + matrices +
progression.json exist; docs DoD met; all 540 legacy v1 sets still valid. Then STOP and give a
final report: total sets built, any BLOCKED sets with reasons, and the headline capability +
progression matrix.

RULES: Work in place; NEVER run any git command (no branches/worktrees/commits) — the tree
stays uncommitted for me to review. Orchestrator reads ONLY the plans and delegates all
reading/research/verification/building to agents; TRUST BUT VERIFY with an independent agent.
Keep ≤10 concurrent WRITER chains; exactly ONE writer per file/family-recipe; nested agents are
fine but never add a second writer to a file. Shared substrate (schemas, gen/, seeds,
transforms) is read-only to builders — only Foundation extends it, between phases. Every set is
reviewed; never self-certify; never force PASS or loosen a check. New schema fields are OPTIONAL
(v1's 540 sets stay valid). Resumable: on restart skip sets whose dir exists with a PASS
verdict. Keep the R8 end-goal in view — every set is a clean, single-purpose probe so a
detector's pass/fail is an unambiguous bit in its capability profile.

REPORTING: concise status at every gate (Foundation sanity-check, P0e, each phase P1e..P7e,
Integration).
