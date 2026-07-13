<?php

declare(strict_types=1);

/**
 * Generator bootstrap. Loads the dev-only Composer autoloader (for
 * nikic/php-parser) and requires the gen/ classes explicitly. The corpus files
 * under testsets/ never load this — they are dependency-free.
 */

$vendor = __DIR__ . '/../vendor/autoload.php';
if (is_file($vendor)) {
    require $vendor;
} else {
    fwrite(STDERR, "[warn] vendor/autoload.php missing — run `composer install` for nikic/php-parser\n");
}

$files = [
    // lib
    __DIR__ . '/lib/Rng.php',
    __DIR__ . '/lib/PhpTokens.php',
    __DIR__ . '/lib/Payload.php',
    __DIR__ . '/lib/AstAnalyzer.php',
    __DIR__ . '/lib/JsonSchema.php',
    __DIR__ . '/lib/SetBuilder.php',
    // transform core
    __DIR__ . '/transforms/Transform.php',
    __DIR__ . '/transforms/TransformInput.php',
    __DIR__ . '/transforms/TransformResult.php',
    __DIR__ . '/transforms/Selector/VariantSelector.php',
    __DIR__ . '/transforms/Ws/BlankInside.php',
    __DIR__ . '/transforms/Ws/BlankBefore.php',
    __DIR__ . '/transforms/Ws/BlankAfter.php',
    __DIR__ . '/transforms/Ws/OperatorSpacing.php',
    __DIR__ . '/transforms/Ws/IndentWidth.php',
    __DIR__ . '/transforms/Ws/LineWrap.php',
    __DIR__ . '/transforms/Ws/LineJoin.php',
    __DIR__ . '/transforms/Ws/Tabs.php',
    __DIR__ . '/transforms/Ws/Trailing.php',
    __DIR__ . '/transforms/Ws/Newline.php',
    __DIR__ . '/transforms/Ws/BraceStyle.php',
    __DIR__ . '/transforms/Ws/Alignment.php',
    __DIR__ . '/transforms/Cm/Docblock.php',
    __DIR__ . '/transforms/Cm/LineAdded.php',
    __DIR__ . '/transforms/Cm/BlockAdded.php',
    __DIR__ . '/transforms/Cm/InlineTrailing.php',
    __DIR__ . '/transforms/Cm/Removed.php',
    __DIR__ . '/transforms/Cm/TextChanged.php',
    __DIR__ . '/transforms/Cm/CommentedCode.php',
    __DIR__ . '/transforms/Cm/MidStatement.php',
    __DIR__ . '/transforms/Cm/LicenseHeader.php',
    __DIR__ . '/transforms/Cm/Annotations.php',
    __DIR__ . '/transforms/Cm/Combined.php',
    __DIR__ . '/transforms/Rn/LocalVars.php',
    __DIR__ . '/transforms/Rn/Params.php',
    __DIR__ . '/transforms/Rn/Functions.php',
    __DIR__ . '/transforms/Rn/Classes.php',
    __DIR__ . '/transforms/Rn/CaseStyle.php',
    __DIR__ . '/transforms/Lt/Numeric.php',
    __DIR__ . '/transforms/Lt/Strings.php',
    __DIR__ . '/transforms/Lt/Arrays.php',
    __DIR__ . '/transforms/Lt/ConstIndirection.php',
    __DIR__ . '/transforms/Ty/TypeHints.php',
    __DIR__ . '/transforms/Ty/Nullable.php',
    __DIR__ . '/transforms/Ns/Imports.php',
    __DIR__ . '/transforms/Ns/NamespaceDepth.php',
    __DIR__ . '/transforms/St/InsertLogging.php',
    __DIR__ . '/transforms/St/InsertDead.php',
    __DIR__ . '/transforms/St/InsertFunctional.php',
    __DIR__ . '/transforms/St/DeleteStmt.php',
    __DIR__ . '/transforms/St/ReorderIndependent.php',
    __DIR__ . '/transforms/St/GapSplit.php',
    __DIR__ . '/transforms/St/PartialFragment.php',
    __DIR__ . '/transforms/St/ParamReorder.php',
    __DIR__ . '/transforms/St/ExprTweak.php',
    __DIR__ . '/transforms/Registry.php',
];
foreach ($files as $f) {
    require_once $f;
}
