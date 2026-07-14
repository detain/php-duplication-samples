# Level 11: Deep Rename Stacks

**What it tests:** multi-level identifier renaming across method → class → namespace chains. A detector must canonicalize symbols across multiple rename axes simultaneously.

This level combines multiple rename transforms (RN-*) in stacked sequences, creating deep rename chains that require multi-pass symbol resolution. Each set exercises a different combination of rename scopes.

**Status:** Complete. `detection_expectation`: varies by family.

_This level is complete (15 sets)._
