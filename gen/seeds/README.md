# Seed Payloads (`gen/seeds/`)

Hand-written PHP source files that serve as the **source material** for generating test sets. Each seed contains a clonable region marked by sentinels — the generator extracts this region and applies transforms to create the actual test cases.

## Seed Format

Each seed lives in `gen/seeds/<name>/` with:

```
<name>/
├── payload.php      # PHP source with clonable region marked by sentinels
├── seed.json        # Metadata (domain, size, symbol, compatibility)
├── variants/        # (optional) Hand-written behavioral variants for CF/API families
└── equivalence_test.php  # (optional) Proves payload↔variant behavioral equivalence
```

### Sentinel Markers

The clonable region is delimited by sentinels inside `payload.php`:

```php
// <<<PAYLOAD:csv_export>>>
public function exportToCsv(array $records, array $headers, string $delimiter): string
{
    // ... the clonable code ...
}
// <<<END-PAYLOAD>>>
```

**Key rules:**
- Store regions in **method form** (`public function …`). The builder strips visibility when mounting into `function` scaffolds.
- Region must be ≥ ~70 significant tokens (clears common detector floors).
- Sentinel comments use `<<<PAYLOAD:…>>>` and `<<<END-PAYLOAD>>>` exactly as shown.

### seed.json Schema

```json
{
    "domain": "csv_export",
    "summary": "Export data array to CSV format with headers.",
    "size_class": "M",
    "symbol": "exportToCsv",
    "line_count": 25,
    "token_count": 180,
    "required_symbols": ["exportToCsv"],
    "mount_modes": ["method", "function"],
    "compatible_interference": ["WS-03", "WS-06", "CM-03", "RN-01", "RN-02", "LT-02", "ST-01"],
    "variants": []
}
```

| Field | Purpose |
|-------|---------|
| `domain` | Functional area (e.g., "csv_export", "auth_handler") |
| `size_class` | XS/S/M/L/XL for size bucketing |
| `symbol` | Primary function/method name in the payload |
| `mount_modes` | How this seed can be mounted: `method` (class method) or `function` (standalone) |
| `compatible_interference` | Transform codes this seed works well with |
| `variants` | List of variant names from `variants/<name>.php` for selector transforms |

## Using Seeds in Recipes

Recipes reference seeds by name via the `seed` field:

```json
{
    "seed": "csv_export",
    "mount": "function",
    "clone_symbol": "parseRow"
}
```

The builder loads `gen/seeds/csv_export/payload.php`, extracts the `csv_export` region, applies the transform chain, and mounts the result into the carrier scaffold.

## Equivalence Testing

For L6+ (semantic) families, seeds may include `variants/<name>.php` files with behavioral rewrites. An `equivalence_test.php` proves they produce identical output:

```bash
php gen/seeds/csv_export/equivalence_test.php   # Must exit 0
```

## Current Seeds (62)

| Domain | Symbol | Size |
|--------|--------|------|
| access_guard | authorize | M |
| address_formatter | formatAddress | S |
| auth_handler | authenticate | M |
| basket_pricing | calculatePrice | M |
| cache_manager | getCached | M |
| config_loader | loadConfig | S |
| config_store | getValue | S |
| counter_increment | increment | XS |
| csv_export | exportToCsv | M |
| csv_import | parseRow | M |
| csv_processor | processCsv | M |
| date_calculator | addDays | S |
| diff_engine | computeDiff | L |
| discount_tiers | applyDiscount | M |
| duration_formatter | formatDuration | S |
| event_dispatcher | dispatch | M |
| file_storage | saveFile | M |
| file_upload_guard | validateUpload | M |
| form_builder | buildForm | L |
| form_validator | validate | M |
| helper_ten_lines | processItems | S |
| http_client | request | M |
| ini_size_parser | parseSize | S |
| input_validation | validate | M |
| inventory_reservation | reserve | M |
| invoice_totals | computeTotals | M |
| item_collector | collectItems | S |
| item_filter | filterItems | S |
| json_pointer | getValue | M |
| legacy_order_export | exportOrders | L |
| logger | log | S |
| money_math | addMoney | M |
| notification_service | notify | M |
| number_gen | generate | S |
| pagination | paginate | M |
| pagination_links | buildLinks | M |
| password_policy | validatePassword | S |
| payment_processor | processPayment | L |
| permission_checker | hasPermission | M |
| query_builder | buildQuery | M |
| rate_limiter | isAllowed | S |
| retry_backoff | backoff | S |
| serializer | serialize | M |
| serializer | unserialize | M |
| slug_generator | generateSlug | S |
| sorting_engine | sort | M |
| state_machine | transition | M |
| stopwatch_metrics | recordElapsed | S |
| tax_bracket | calculateTax | M |
| template_renderer | render | M |
| tree_walker | walk | M |
| user_repository | findById | M |
| validation_rule | validate | M |
| variant_rich_guard | authorize | M |
| webhook_verifier | verifySignature | M |
| weekday_scheduler | isWeekday | S |
| xml_config_reader | readConfig | M |

## Adding a New Seed

1. Create `gen/seeds/<name>/payload.php` with the sentinel-wrapped clonable region.
2. Create `gen/seeds/<name>/seed.json` with metadata.
3. (Optional) Add `variants/<variant>.php` and `equivalence_test.php` for semantic families.
4. The seed is auto-discovered by the builder — no registration required.
