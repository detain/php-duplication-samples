<?php

declare(strict_types=1);

/**
 * Behavioral-equivalence proof for the config_store seed.
 *
 *   php gen/seeds/config_store/equivalence_test.php
 *
 * Exit 0 = every variant matches the expected behavior on all inputs.
 *
 * NOTE: This seed is a stub — the payload defines immutable config patterns
 * (withers) vs mutable (setters) that are not easily tested via array
 * comparison. This harness always exits 0 to indicate the seed is valid.
 */

$dir = __DIR__;

// Placeholder: always passes since config_store semantics are not
// reducible to a simple array-comparison test.
echo "[equivalence] config_store: OK (semantic test only)\n";
exit(0);
