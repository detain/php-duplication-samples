# Recipe Files (`gen/recipes/`)

JSON manifests that describe how to assemble a complete test set — which seed to use, which transforms to apply to each carrier, which distractors and clean files to include, and what the expected ground truth is.

## Recipe Directory Structure

```
gen/recipes/
├── L00/                    # Level-specific subdirectories
├── L01/
├── L01_exact/
├── L02_whitespace/
├── L04_partial_duplication/
└── ...
```

Each `L{NN}_<name>/` directory contains one recipe JSON per family. Recipes are grouped under their level directory for navigation.

## Recipe Schema

```json
{
    "level": 1,
    "level_name": "exact",
    "level_dir": "L01_exact",
    "family": "ex_function",
    "sets": [
        {
            "set_id": "L01-ex_function-002",
            "title": "Byte-identical CSV row parser function in three utility files",
            "description": "A CSV row parsing function is duplicated...",
            "seed": "csv_import",
            "mount": "function",
            "clone_symbol": "parseRow",
            "granularity": "function",
            "clone_type": "type-1",
            "normalized_by": ["whitespace", "comments"],
            "rng_seed": 101002,
            "notes": "Byte-for-byte identical...",
            "detection_expectation": {
                "text_based": true,
                "token_based": true,
                "ast_based": true,
                "metric_based": true,
                "semantic": true
            },
            "carriers": [
                {
                    "file": "DataMapper.php",
                    "namespace": "Acme\\Data\\Import",
                    "class": "DataMapper",
                    "scaffold": "fn_alpha",
                    "group": "A",
                    "pristine": true,
                    "transforms": []
                }
            ],
            "distractors": [
                {
                    "file": "XmlConverter.php",
                    "namespace": "Acme\\Data\\Transforms",
                    "class": "XmlConverter",
                    "source": "nearmiss_csv",
                    "reason": "near-miss: similar parsing vocabulary but processes XML..."
                }
            ],
            "cleans": [
                {
                    "file": "ConfigLoader.php",
                    "namespace": "Acme\\Data\\Config",
                    "class": "ConfigLoader",
                    "source": "clean_parsing"
                }
            ]
        }
    ]
}
```

## Top-Level Fields

| Field | Type | Description |
|-------|------|-------------|
| `level` | int | Difficulty level (0–20) |
| `level_name` | string | Level name slug (e.g., "exact", "rename_literals") |
| `level_dir` | string | Target directory name (e.g., "L01_exact") |
| `family` | string | Family slug (e.g., "ex_function") |
| `sets` | array | Array of set specifications |

## Set-Level Fields

| Field | Type | Description |
|-------|------|-------------|
| `set_id` | string | Unique ID (e.g., "L01-ex_function-002") |
| `title` | string | Human-readable title |
| `description` | string | What this set tests |
| `seed` | string | Seed name from `gen/seeds/<name>/` |
| `mount` | string | "method" or "function" — how to mount the seed |
| `clone_symbol` | string | Function/method name of the clone |
| `granularity` | string | "function", "method", "block", or "class" |
| `clone_type` | string | "type-1", "type-2", "type-3", "type-4" |
| `normalized_by` | array | What normalization is needed to match |
| `rng_seed` | int | RNG seed for deterministic generation |
| `detection_expectation` | object | Which detector types should pass |
| `carriers` | array | The files containing actual clones |
| `distractors` | array | Near-miss false-positive bait |
| `cleans` | array | Unrelated filler files |

## Carrier Specification

```json
{
    "file": "DataMapper.php",
    "namespace": "Acme\\Data\\Import",
    "class": "DataMapper",
    "scaffold": "fn_alpha",
    "group": "A",
    "pristine": true,
    "transforms": []
}
```

| Field | Description |
|-------|-------------|
| `file` | Output filename in `src/` |
| `namespace` | PHP namespace for the class |
| `class` | Class name (used with `__CLASS__` placeholder) |
| `scaffold` | Scaffold name from `gen/scaffolds/<name>.php` |
| `group` | Clone group ID (all carriers with same group are clones) |
| `pristine` | If true, no transforms applied |
| `transforms` | Array of `{code, params}` or `{code, variant}` to apply |
| `variant` | For selector transforms: which variant to pick |

## Transform Chains

Transforms are applied in order:

```json
"transforms": [
    {"code": "WS-03", "params": {}},
    {"code": "RN-01", "params": {"exclude": ["$db"]}}
]
```

For selector transforms (CF/API/BL/SEM), use `variant` instead:

```json
"transforms": [
    {"code": "CF-01", "variant": "early_return"}
]
```

## How Recipes Generate Sets

The builder (`gen/build.php`) processes a recipe as follows:

```
recipe JSON
  → for each set spec:
       mt_srand(rng_seed)              # determinism
       load seed payload (sentinels stripped)
       for each carrier:
          mount → apply transform chain → re-indent → insert at scaffold marker
          record payload start/end line during render  # ground truth
       copy distractor + clean assets
       emit src/ files, set.json, expected.json
```

## Running the Builder

```bash
php gen/build.php --set=L01-ex_function-002   # Build one set
php gen/build.php --family=ex_function          # Build entire family
php gen/build.php --level=1                     # Build all L01 sets
php gen/build.php --all                         # Build everything
php gen/build.php --check                       # Byte-diff CI gate
```

## Current Recipe Coverage

| Level | Families | Sets |
|-------|----------|------|
| L00 | 1 | 5 |
| L01 | 6 | 30 |
| L02 | 1 | 5 |
| L03 | 5 | 25 |
| L04 | 15+ | 75+ |
| L05 | 20+ | 100+ |
| L06–L10 | various | various |
| L11–L20 | various | various |

See `testsets/README.md` for full level descriptions.
