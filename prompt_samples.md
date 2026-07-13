You are the orchestrator/lead for building the ENTIRE graduated duplication-detection
test-set corpus TO COMPLETION. The authoritative spec is ./plan_samples.md in this repo
(/home/sites/php-duplication-samples). This message is your explicit go-ahead to execute
the whole plan — every level, every family, all ~540 sets — not just the pilot.

You will orchestrate by spawning role-scoped subagents and passing each one its instructions
INLINE at spawn time (no config files, no AGENTS.md, no .opencode/agent definitions).

STEP 1 — Read the plan. Read §0 first, then §1–§2, §4–§16, §18 (esp. §18.10 "Driving to
completion"), §19, §20. The plan contains the set specs (§9/§15), the file-scope allow/deny
matrix (§18.6), the build→review→fix loop (§18.5), the Definition of Done (§18.7), the
completion loop (§18.10), and ready-to-use per-role prompts (§20). Execute these faithfully;
do not re-invent them.

HOW TO SPAWN EACH SUBAGENT (inline assembly, per §20.0):
For every agent you spawn, build its prompt inline from four LABELED parts, in order:
  1. TASK CONTRACT (§18.8): role, target set id, ALLOW-WRITE paths (the exact §18.6 entry
     for that role/set), the DENY-LIST, and MAY-RUN commands.
  2. ROLE PROMPT: §20.1 foundation / §20.2 set-builder / §20.3 reviewer / §20.4 fixer /
     §20.5 integration — pasted verbatim.
  3. SHARED-KNOWLEDGE BLOCK (§20.6) — pasted verbatim (includes the five non-negotiables).
  4. SPECIFIC §EXCERPTS the role needs, pasted inline (e.g. an L4 builder gets §6, §7, the
     L4 row of §9, §10; a reviewer gets §6, §7, §12, §18.7). Paste them — do not just cite;
     the subagent has not read the plan.
Because there is NO config enforcement, the discipline lives entirely in these prompts:
every invocation MUST state "NEVER run any git command" and the agent's exact write-scope,
and you MUST treat any out-of-scope write or git call as an automatic set FAIL (Reviewer
checks this, §18.7 item 10).

STEP 2 — DRIVE THE WHOLE PLAN TO COMPLETION via the §18.10 loop:
  a. Build the initial Foundation (foundation agent) for Wave 1; review; must pass before
     any fan-out.
  b. WAVE 1 (pilot, §18.3): build the 10 starter sets, each a
     build → review → (fix → review)* → done chain (≤10 concurrent; §18.4). Proves the
     engine end-to-end. Report status.
  c. Then loop phases P1..P7 (§16 schedule, §18.10 driver): for each phase —
     (1) EXTEND Foundation with the transforms/seeds/variants/scaffolds/distractors that
         phase needs (L6–L8 need HAND-WRITTEN variant bodies + equivalence tests; L10 needs
         adversarial fixtures + sanitized Smarty per §14 — real authoring, not pure
         generation); review; must pass.
     (2) ENUMERATE that phase's sets from §9/§15 and FAN OUT in waves of ≤10 chains until
         the phase's set list is exhausted (one Set-Builder per family per wave, §18.6).
     (3) PHASE GATE: whole-subtree gen/verify.php green. Record any BLOCKED sets (5 rounds
         exhausted) in BLOCKED.md; they don't block other sets or later phases.
     (4) CHECKPOINT: report phase status, then continue automatically unless I say pause.
  d. P8 INTEGRATION over the whole tree (integration agent, §20.5): rebuild manifest.json +
     aggregate ground truth, whole-tree verify + bench run (capture tool versions), generate
     the matrices AND per-tool Capability Profile report cards (§13.4, the R8 deliverable),
     write the golden baseline, finalize docs (§19), and repair samples.json (add the 3
     missing type entries only; no samples/ content changes).

DONE when the §18.10 goal state holds: every family in §9 has ≥5 DONE sets, manifest totals
match §15, whole-tree verify is green, capability profiles + matrices exist, and docs meet
the §19 Definition of Done. Then STOP and give a final report: total sets built, any BLOCKED
sets with reasons, and the headline capability matrix.

RULES: Work in place in the repo dir; NEVER commit or run any git command (no branches/
worktrees either) — the finished tree stays uncommitted for me to review. Keep ≤10 concurrent
chains throughout. Every set is reviewed; never self-certify. Shared substrate (schemas,
gen/, seeds, transforms) is read-only to set-builders. The run is resumable: on restart, skip
sets whose dir exists with a PASS verdict and finish the rest (§18.10). Keep the R8 end-goal
in view — every set is a clean single-axis probe so a detector's pass/fail is an unambiguous
bit in its capability profile.

REPORTING: concise status at every gate (Foundation, Wave 1, each phase, Integration).
