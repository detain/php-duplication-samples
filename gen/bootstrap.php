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
    // SY transforms (PHP-era modernization)
    __DIR__ . '/transforms/Sy/PositionalToNamed.php',
    __DIR__ . '/transforms/Sy/AnonymousToArrow.php',
    __DIR__ . '/transforms/Sy/MixedToUnion.php',
    __DIR__ . '/transforms/Sy/ArrayToShortArray.php',
    __DIR__ . '/transforms/Sy/ConcatToInterpolate.php',
    __DIR__ . '/transforms/Sy/YodaConditions.php',
    __DIR__ . '/transforms/Sy/IssetTernaryToCoalesceAssign.php',
    __DIR__ . '/transforms/Sy/PropertyExistsToIsset.php',
    __DIR__ . '/transforms/Sy/ListToArrayDestructure.php',
    __DIR__ . '/transforms/Sy/EvalSafePatterns.php',
    // LEG transforms (Legacy PHP syntax)
    __DIR__ . '/transforms/Leg/ListToArrayDestructure.php',
    __DIR__ . '/transforms/Leg/PowToExponentiation.php',
    __DIR__ . '/transforms/Leg/EregiToPregMatch.php',
    __DIR__ . '/transforms/Leg/SplitToPregSplit.php',
    __DIR__ . '/transforms/Leg/EachToForeach.php',
    __DIR__ . '/transforms/Leg/McryptToOpenSsl.php',
    __DIR__ . '/transforms/Leg/OldClassConstantAccess.php',
    __DIR__ . '/transforms/Leg/ConstructorToPromoted.php',
    __DIR__ . '/transforms/Leg/ErrorSuppressToMatch.php',
    __DIR__ . '/transforms/Leg/ExtractToExplicitVars.php',
    // UQ transforms (Unique-code injection)
    __DIR__ . '/transforms/Uq/PerCurrencyRounding.php',
    __DIR__ . '/transforms/Uq/AuditTimestamp.php',
    __DIR__ . '/transforms/Uq/BoundsClamp.php',
    __DIR__ . '/transforms/Uq/InputPreprocessing.php',
    __DIR__ . '/transforms/Uq/BehaviorAdjacentReplacement.php',
    __DIR__ . '/transforms/Uq/HeadInsertion.php',
    __DIR__ . '/transforms/Uq/TailInsertion.php',
    __DIR__ . '/transforms/Uq/NoiseWrapper.php',
    // NZ transforms (Noise injection)
    __DIR__ . '/transforms/Nz/Logging.php',
    __DIR__ . '/transforms/Nz/Metrics.php',
    __DIR__ . '/transforms/Nz/SecurityAssertions.php',
    __DIR__ . '/transforms/Nz/FrameworkAttributes.php',
    __DIR__ . '/transforms/Nz/I18nWrapper.php',
    __DIR__ . '/transforms/Nz/FeatureFlagGuard.php',
    __DIR__ . '/transforms/Nz/DebugDump.php',
    __DIR__ . '/transforms/Nz/EnvChecks.php',
    __DIR__ . '/transforms/Nz/TimingProfiling.php',
    __DIR__ . '/transforms/Nz/RequestContextThreading.php',
    // ENC transforms (Encoding normalization)
    __DIR__ . '/transforms/Enc/BomInsertion.php',
    __DIR__ . '/transforms/Enc/NbspIndentation.php',
    __DIR__ . '/transforms/Enc/UnicodeConfusables.php',
    __DIR__ . '/transforms/Enc/MixedEol.php',
    __DIR__ . '/transforms/Enc/StringEscapeVariation.php',
    __DIR__ . '/transforms/Enc/HeredocConversion.php',
    __DIR__ . '/transforms/Enc/TabInStrings.php',
    // Registry
    __DIR__ . '/transforms/Registry.php',
];
foreach ($files as $f) {
    require_once $f;
}
