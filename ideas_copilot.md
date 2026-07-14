## Overview

This is the master expansion strategy issue that outlines a comprehensive plan to grow the php-duplication-samples corpus from its current ~500 test sets to 1000+ sets covering vastly more combinations of code duplication patterns, variations, and interference types.

## Current Baseline
- **Old Samples**: 59 categories, ~600 files in `samples/`
- **New Testsets**: Foundation built (L00-L10 with 5 verified pilot sets)
- **Generator**: Deterministic build engine with transform registry (WS-03/WS-06/CM-03/RN-01/LT-01/ST-01 + CF/API variants implemented)
- **Coverage Gap**: Foundation handles single-axis interference well; missing are compound variations, threshold boundaries, and advanced domain scenarios

## Strategic Goals

1. **Expand Single-Axis Families**: Increase sets per family from 5 to 15-25+ across all L0-L8 levels
2. **Add Compound-Interference Sets (L9)**: Generate 200+ sets with 2-4 simultaneous interference types
3. **Implement Advanced Seed Domains**: Create 10-15 diverse seed payloads beyond current 3 (invoice_totals, csv_import, access_guard)
4. **Add Threshold Boundary Sets**: Test edge cases around tool min-token/min-line thresholds
5. **Coverage for Hidden Challenges**: Map all 60 "hidden duplication" types to test sets
6. **Real-World Scenarios**: Incorporate patterns from actual production code (Laravel, WordPress, Symfony examples)
7. **Tool-Specific Variations**: Create sets targeting specific tool blind spots identified in benchmarking

## Expansion Dimensions

### Dimension 1: Variation Combinations (Cartesian Product)
Instead of testing one variation per set, combine variations systematically:
- Base payload + 2 variations (A, B, C carriers)
- Example: `ws_blank_inside + rn_locals` on invoice_totals
- Example: `cm_docblock + lt_numeric` on csv_import
- 5 × 5 × 5 matrix per level = 125 combinations per seed domain
- 3 seeds × 125 = 375 compound sets alone

### Dimension 2: Distractor Sophistication
Enhance false-positive testing:
- **Type 1 Distractors**: Near-miss (high token overlap, &lt;40 contiguous normalized tokens)
- **Type 2 Distractors**: Confusingly-similar (same domain, similar variable names, different logic)
- **Type 3 Distractors**: Boilerplate-bait (acceptable duplication patterns that tools shouldn't flag)
- **Type 4 Distractors**: Partial-match (contains 50% of clone tokens but different structure)
- Per set: 1-4 distractors depending on level

### Dimension 3: Payload Diversity
Expand seed library from 3 to 15+ domains:
- **Invoice/Billing**: invoice_totals, quote_calculation, tax_handling, discount_application, payment_processing
- **Import/ETL**: csv_import, json_batch_load, xml_parsing, delimited_file_handler, stream_processor
- **Authorization**: access_guard, role_checker, permission_evaluator, capability_verifier, resource_acl
- **Data Structures**: array_operations, collection_manipulation, tree_traversal, graph_search, linked_list_ops
- **String Processing**: regex_matching, text_normalization, encoding_conversion, token_splitting, format_parsing
- **HTTP/API**: request_building, response_parsing, retry_handler, timeout_manager, auth_header_builder
- **Database**: query_builder, transaction_handler, migration_executor, connection_pool, cache_decorator
- **Validation**: email_validator, password_rules, phone_formatter, url_checker, credit_card_verifier
- **Caching**: cache_warmer, invalidation_policy, ttl_calculator, bloom_filter, lru_eviction
- **Logging**: structured_logging, event_tracking, metric_emission, trace_decorator, error_aggregator

### Dimension 4: Granularity Variations
Test clones at different structural levels:
- **File-level**: Entire file is duplicate (only scaffolds differ)
- **Class-level**: Multiple classes in same file, one duplicated
- **Method-level**: Single method/function duplicated
- **Block-level**: Loop/if/switch block duplicated
- **Statement-level**: Single statement or expression duplicated
- **Intra-file clusters**: Multiple disjoint clones within same file

### Dimension 5: Size Variation Ladder
Sets with exact same interference but different clone sizes:
- **Micro** (3-5 lines): Single expression or small statement
- **Small** (10-20 lines): Single method with ~100 tokens
- **Medium** (30-50 lines): Complex method with 300+ tokens
- **Large** (60-100+ lines): Class-level operation with 600+ tokens
- Each ladder tests tool's min-token/min-line thresholds

### Dimension 6: Clone Genealogy
Model realistic drift patterns (how clones diverge over time):
- **Genesis**: Exact clone at commit T0
- **Single Edit**: One carrier gets +5% changes at T1
- **Progressive Drift**: Each carrier gets +10% changes per generation, 3 generations total
- **Branch Divergence**: Clone split into 2 families, each drifts independently
- **Convergence**: Two independent implementations that become nearly identical

### Dimension 7: Hidden Duplication Challenges
Create sets for each of the 60 "hidden" duplication types:
- Off-by-one loops
- Floating-point comparison patterns
- String encoding variations (UTF-8, Latin-1, etc.)
- Timezone-aware operations
- Null-coalescing vs conditional checks
- Array key iteration variations
- Type juggling patterns
- Exception hierarchy duplication
- Mocking framework variations
- Dependency-injection binding duplicates

### Dimension 8: Tool-Specific Edge Cases
Generate sets targeting tool vulnerabilities:
- **Token Threshold Edges**: Sets with N-1, N, N+1 tokens (where N is tool's minimum)
- **Boundary Fencepost**: Clones that span exact line-count threshold
- **Whitespace Normalization**: Tabs vs spaces at tool's normalization boundary
- **Comment Preservation**: Sets where comment content matches but code differs
- **Unicode Handling**: Sets with non-ASCII identifiers, emoji in strings, mixed encodings
- **Code Generation**: PHP-compiled outputs (e.g., Twig, Smarty templates compiled to PHP)
- **HTML Mixing**: HTML-embedded PHP with duplicated PHP blocks
- **Heredoc/Nowdoc**: Multi-line string handling edge cases

### Dimension 9: Cluster Topology
Test advanced cluster detection:
- **Single Cluster**: 3 carriers, 1 instance (current standard)
- **Multi-Cluster**: 4+ carriers, 2+ independent clusters in same set
- **Overlapping Clusters**: 2 clusters that share 1 carrier (overlaps at line 45-50)
- **Partial Clusters**: Incomplete clusters (only 2 carriers, not 3)
- **Intra-File Clusters**: Multiple copies within same file
- **Nested Clones**: Clone inside a clone (hierarchical duplication)
- **Clustered Distractors**: Distractor shares tokens with both clusters but matches neither

### Dimension 10: Realistic Domain Scenarios
Multi-function/class interacting sets:
- **E-Commerce Order Flow**: Validate → Calculate Tax → Apply Discount → Generate Invoice (clones at calc/discount steps)
- **User Lifecycle**: Register → Verify → Activate → Suspend → Delete (clones at verify/activate state checks)
- **API Rate Limit**: Check quota → Increment counter → Decay history → Return headers (clones in check/decay)
- **Cache Invalidation**: Mark dirty → Rebuild → Verify → Publish (clones in mark/verify checks)
- **Permission Cascade**: Check own permission → Check group permission → Check organizational permission (nested checks duplicated)

## Phased Expansion Plan

### Phase 1: Foundation Audit & Tooling (Week 1)
- [ ] Complete coverage audit: map 60 "hidden challenges" to existing/proposed sets
- [ ] Identify gaps in current 5 pilot sets + remaining Wave 1 (5 sets)
- [ ] Create expansion recipe templates for Phase 2-3

### Phase 2: Seed Expansion (Week 2-3)
- [ ] Create 12 new seed domains (invoice → payment_processing, csv_import → json_batch_load, etc.)
- [ ] Write payload.php for each seed (≥100 tokens each)
- [ ] Add equivalence_test.php for semantic variants
- [ ] Add variants/{code_name}.php for CF/API/SEM families

### Phase 3: Compound-Interference Generation (Week 4-6)
- [ ] Generate 200+ L9 sets (mixed interference)
- [ ] Create recipes for 25 Level-9 families (ws+cm, ws+rn, ws+rn+cm, lt+st, etc.)
- [ ] Each family: 8-12 sets, 3+ carriers, 1-2 distractors
- [ ] Score: ~50 sets/week × 4 weeks = 200 new sets

### Phase 4: Threshold & Edge Cases (Week 7)
- [ ] Create 80+ threshold-boundary sets (L2/L4 edge cases)
- [ ] Size-ladder families: 5 rungs × 5 seed domains × 3 levels = 75 sets
- [ ] Tool-specific edge cases: 20-30 sets targeting phpcpd, jscpd blind spots

### Phase 5: Hidden Challenges Mapping (Week 8)
- [ ] Create 100+ sets covering all 60 hidden-challenge types
- [ ] Each challenge: 1-2 sets (one basic, one with compound interference)
- [ ] Examples: off-by-one loops, floating-point comparison, timezone handling, etc.

### Phase 6: Real-World Scenario Sets (Week 9-10)
- [ ] Create 50+ "realistic" multi-interaction sets
- [ ] Base on common frameworks: Laravel, Symfony, WordPress patterns
- [ ] Each: 4-6 PHP files, 2+ clusters, realistic refactoring paths

### Phase 7: L10 Adversarial Sets (Week 11)
- [ ] Create 100+ L10 edge cases:
  - Overlapping clusters (10 sets)
  - Intra-file clones (15 sets)
  - Encoding tricks (10 sets)
  - Generated code (15 sets)
  - HTML-PHP mixing (10 sets)
  - Clone genealogy (20 sets)
  - Nested clones (10 sets)

### Phase 8: Integration & Validation (Week 12)
- [ ] Run full generator: `gen/build.php --all`
- [ ] Run full verifier: `gen/verify.php --all` (8 checks per set)
- [ ] Benchmark all sets: `bench/run-testsets.php`
- [ ] Generate capability-profile reports for tools
- [ ] Commit to `testsets/` tree

## Detailed Expansion Ideas by Category

### A. Whitespace & Formatting (L2)
**Current**: WS-03, WS-06
**Proposed Expansions**:
1. **ws_tab_insertion** (5 sets): Replace all spaces with tabs in carrier B/C
2. **ws_mixed_tabs_spaces** (5 sets): Inconsistent tab/space mixing
3. **ws_newline_endings** (5 sets): LF vs CRLF vs CR endings
4. **ws_blank_line_counts** (8 sets): 0/1/2/4/8 blank lines between sections
5. **ws_indentation_levels** (5 sets): Off-by-1/2 indentation in carriers
6. **ws_line_length_wraps** (10 sets): 80-char, 120-char, 160-char wraps
7. **ws_function_spacing** (5 sets): spacing around function definitions
8. **ws_operator_spacing** (5 sets): tight vs loose operator spacing
9. **ws_array_formatting** (5 sets): single-line vs multi-line arrays
10. **ws_chained_calls** (8 sets): fluent interface chain wrapping patterns

### B. Comments & Documentation (L3)
**Current**: CM-03
**Proposed Expansions**:
1. **cm_single_line_density** (5 sets): 0, 10%, 25%, 50% single-line comments
2. **cm_block_comment_style** (5 sets): /* */ vs /** */ styles
3. **cm_docblock_tags** (8 sets): @param, @return, @throws variations
4. **cm_inline_comment_position** (5 sets): end-of-line vs above-line comments
5. **cm_comment_language** (3 sets): English, French, Japanese comments
6. **cm_todo_markers** (4 sets): TODO, FIXME, XXX, HACK marker duplication
7. **cm_copyright_headers** (3 sets): with/without file-level copyright
8. **cm_deprecated_markers** (3 sets): @deprecated vs deprecation comments
9. **cm_type_hints_as_comments** (4 sets): Older PHP type-hint comment style vs modern

### C. Identifier Renaming (L4)
**Current**: RN-01 (local vars)
**Proposed Expansions**:
1. **rn_parameter_names** (5 sets): $user, $u, $usr variations
2. **rn_method_names** (5 sets): getData, get_data, GetData patterns
3. **rn_class_names** (5 sets): UserService, IUserService, AbstractUserService
4. **rn_constant_names** (5 sets): MAX_RETRIES, MaxRetries, max_retries
5. **rn_loop_variables** (5 sets): $i, $idx, $index, $counter
6. **rn_abbreviations** (8 sets): $service vs $svc, $configuration vs $config
7. **rn_plural_vs_singular** (5 sets): $users vs $user in loops
8. **rn_hungarian_notation** (3 sets): $aItems vs $items, $bActive vs $active
9. **rn_prefix_suffix_only** (4 sets): get_userById vs user_GetById
10. **rn_magic_number_constants** (5 sets): 2592000 vs DAY_IN_SECONDS

### D. Numeric & String Literals (L4)
**Current**: LT-01
**Proposed Expansions**:
1. **lt_numeric_formatting** (8 sets): 1000 vs 1_000, 0x3E8, 1e3
2. **lt_float_precision** (6 sets): 1.0 vs 1, 0.5 vs .5
3. **lt_string_quotes** (8 sets): "double" vs 'single', mixed per carrier
4. **lt_escape_sequences** (5 sets): \n vs \\n, \\ vs /, etc.
5. **lt_unicode_escapes** (4 sets): \u00E9 vs é
6. **lt_array_constant_syntax** (5 sets): array() vs []
7. **lt_boolean_constants** (4 sets): true vs TRUE, false vs FALSE
8. **lt_null_variations** (3 sets): null vs NULL
9. **lt_magic_constant_forms** (3 sets): __FILE__ vs __FILE__, etc.
10. **lt_percentage_variance** (5 sets): 10%, 10.0%, 10.00% in strings

### E. Statements & Flow (L5)
**Current**: ST-01 (insert logging)
**Proposed Expansions**:
1. **st_null_check_styles** (8 sets): isset() vs !== null vs is_null()
2. **st_ternary_vs_if** (6 sets): ternary vs if-else (behaviorally equiv)
3. **st_loop_style** (8 sets): for vs foreach vs while variants
4. **st_early_return** (5 sets): if-throw vs early-return patterns
5. **st_defensive_copy** (5 sets): with/without intermediate $temp vars
6. **st_statement_reordering** (8 sets): $a=1; $b=2; vs $b=2; $a=1; (order-independent)
7. **st_compound_assignment** (5 sets): $x=$x+5 vs $x+=5
8. **st_list_vs_array** (4 sets): list($a,$b) = array vs [$a,$b] = array
9. **st_try_catch_finally** (6 sets): try-catch vs try-catch-finally vs try-finally
10. **st_variable_scope** (5 sets): local vs use() vs $GLOBALS, behaviorally same

### F. Control Flow & Semantics (L6-L8)
**Current**: CF/API variant selectors
**Proposed Expansions**:
1. **cf_guard_clause_inversion** (8 sets): if ($x) return vs if (!$x) { ... return }
2. **cf_loop_to_map** (5 sets): foreach with $result[] vs array_map variant
3. **cf_recursion_vs_iteration** (6 sets): recursive function vs iterative (same outcome)
4. **cf_strategy_pattern** (8 sets): switch statement vs strategy object (semantically equiv)
5. **cf_collector_vs_filter** (5 sets): manual filtering vs array_filter + array_map
6. **cf_validation_chains** (8 sets): nested ifs vs fluent validation vs guard clauses
7. **api_immutable_vs_mutable** (6 sets): with vs without object mutation
8. **api_fluent_vs_imperative** (8 sets): fluent interface vs chained method calls
9. **api_generator_vs_array** (5 sets): yield vs return array
10. **sem_equivalent_algorithms** (12 sets): bubble sort vs insertion sort, different but same semantics

### G. Hidden Challenges (All Levels)
**Map all 60 "hidden duplication" types**:
1. **Floating-point comparison** (3 sets): == vs abs() < epsilon
2. **Null coalescing** (3 sets): ?? vs ?: vs isset()
3. **Off-by-one logic** (3 sets): &lt; vs &lt;=, i++ vs ++i
4. **Exception hierarchy** (3 sets): catch(Exception) vs catch(RuntimeException)
5. **Type juggling** (3 sets): == vs ===, intval() vs (int)
6. **Timezone handling** (3 sets): different timezone initialization, same UTC result
7. **Array key iteration** (2 sets): foreach(array_keys($a) as $k) vs foreach($a as $k=>$v)
8. **String encoding** (3 sets): mb_strlen vs strlen, different but contextually same
9. **Mocking variants** (3 sets): PHPUnit mock vs Prophecy vs manual mock
10. ... and 50+ more

### H. Compound Interference (L9)
**200+ combinations of 2-4 simultaneous interferences**:

| Level 9 Family | Interference Codes | Example Transformations | Planned Sets |
|---|---|---|---|
| ws+cm | WS-06, CM-03 | Line wraps + docblock changes | 12 |
| ws+rn | WS-03, RN-01 | Blank lines + variable renames | 12 |
| ws+rn+cm | WS-03, RN-01, CM-03 | All three combined | 15 |
| ws+lt | WS-06, LT-01 | Wraps + numeric format changes | 10 |
| cm+rn | CM-03, RN-01 | Comments + renames | 12 |
| rn+lt | RN-01, LT-01 | Variable renames + literal changes | 12 |
| st+cf | ST-01, CF (guards) | Statement edits + guard inversions | 15 |
| lt+st+rn | LT-01, ST-01, RN-01 | Literals, statements, names | 12 |
| ... | ... | ... | ~150 more |

### I. Threshold & Edge Cases (L2/L4/L10)
**80+ sets testing tool sensitivity boundaries**:
1. **Token Boundary Sets** (30 sets): token counts at ±1 from common thresholds (20, 30, 50, 100 tokens)
2. **Line Boundary Sets** (20 sets): line counts at ±1 from common thresholds (5, 10, 20, 50 lines)
3. **Percentage Variance** (20 sets): 95%, 97%, 98%, 99%, 100% token overlap
4. **Identifier Count Variance** (10 sets): 1, 2, 5, 10, 20 identifiers renamed

### J. Payload Diversity (15 seeds)
Each with payload.php, seed.json, equivalence_test.php, variants/:
1. **invoice_totals** ✓ (exists)
2. **csv_import** ✓ (exists)
3. **access_guard** ✓ (exists)
4. **payment_processing** - credit card, transaction handling
5. **json_batch_load** - bulk insert, transform, validation
6. **xml_parsing** - DOM vs SimpleXML patterns
7. **array_manipulation** - sorting, filtering, transformation
8. **regex_matching** - email, phone, URL patterns
9. **http_request** - GET/POST, header building, auth
10. **database_query** - SELECT, JOIN, aggregate queries
11. **validation_chain** - multi-step field validation
12. **cache_management** - get/set/invalidate patterns
13. **logging_instrumentation** - structured logs at different levels
14. **state_machine** - order states, user lifecycle
15. **encryption_handling** - symmetric/asymmetric, encoding

### K. Real-World Scenarios (50+ sets)
Multi-file, multi-method interaction patterns:
1. **E-Commerce Order Processing**: Validate → Calculate → Apply Discount → Generate Invoice
2. **User Authentication Flow**: Register → Email Verify → OAuth Bridge → Session Setup
3. **API Rate Limiting**: Check Quota → Increment → Decay → Return Headers
4. **Cache Invalidation**: Mark Dirty → Rebuild → Verify Consistency → Publish
5. **Permission Cascade**: Own → Group → Organization → System checks
6. **Database Transaction**: Begin → Acquire Lock → Execute → Commit/Rollback
7. **Error Recovery**: Try Primary → Catch → Try Fallback → Log → Return Default
8. **Batch Processing**: Initialize → Process Chunk → Accumulate → Finalize
9. **File Upload**: Validate → Store → Generate Thumbnails → Update DB
10. **Search Indexing**: Parse → Tokenize → Normalize → Store → Retrieve

### L. Tool-Specific Edge Cases (30+ sets)
Targeting known tool limitations:
1. **phpcpd Whitespace Normalization**: 8 sets testing WS-03/WS-06 behavior
2. **jscpd Encoding Handling**: 5 sets with UTF-8, Latin-1, mixed encodings
3. **Simian Token Counting**: 8 sets at Simian's 6-token minimum edge
4. **PHPMD Comment Handling**: 5 sets testing comment preservation
5. **PMD-CPD Scope Blindness**: 6 sets with identifier scoping tricks

### M. Cluster Topology (40+ sets)
Advanced detection scenarios:
1. **Multi-Cluster Single File** (12 sets): 2-3 independent clusters in same file
2. **Overlapping Clusters** (8 sets): 2 clusters sharing 1 member
3. **Partial Clusters** (5 sets): Only 2 carriers instead of standard 3
4. **Nested Clones** (8 sets): Clone inside a clone (hierarchical)
5. **Distractor Confusion** (7 sets): Distractors match one or both clusters partially

### N. Clone Genealogy (25+ sets)
Modeling realistic divergence:
1. **Genesis + Single Edit** (5 sets): Exact copy, then 1 carrier edited
2. **Progressive Drift** (8 sets): 10%, 20%, 30% changes per generation
3. **Branch Divergence** (6 sets): Clone splits into 2 families, each drifts
4. **Convergence** (3 sets): 2 independent implementations become nearly identical
5. **Partial Revert** (3 sets): A drifted clone partially reverted to original

## Outcome Metrics

After Phase 1-8, target:
- **1,000+ Test Sets** (vs current ~500)
- **15 Seed Domains** (vs current 3)
- **60+ Set Families** per level (vs current 5-10)
- **100% Coverage** of 60 "hidden" challenge types
- **Comprehensive Capability Profiles** for each detection tool
- **Precise Tool Blind-Spot Identification**: "phpcpd fails at L5-st_early_return, L6-cf_recursion_vs_iteration"

## Success Criteria

✅ All sets pass `gen/verify.php --all`  
✅ All sets are byte-identical on regeneration (`gen/build.php --check`)  
✅ Benchmark scores align with `detection_expectation` fields  
✅ Tool capability matrix fully populated (Precision, Recall, F1 per family)  
✅ 100% of "hidden challenges" mapped to test sets  
✅ Corpus documentation complete (README, per-level guides, examples)  
✅ Contribution guide for adding new sets/families  

---

**Next Steps**: Create child issues for each phase and invite set-builder agents to begin Phase 2 seed expansion.
