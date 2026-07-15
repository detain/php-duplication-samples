# PHP Duplication Samples

A comprehensive reference corpus and benchmark suite for PHP code duplication patterns. This project provides a structured collection of duplicated code examples across 54 distinct categories, along with refactored solutions and tooling to compare duplicate detection tools.

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Corpus Explorer (Web UI)](#corpus-explorer-web-ui)
- [Directory Structure](#directory-structure)
- [Duplication Categories](#duplication-categories)
- [Benchmark Tools](#benchmark-tools)
- [Getting Started](#getting-started)
- [Adding New Samples](#adding-new-samples)
- [Contributing](#contributing)
- [License](#license)
- [References](#references)

---

## Overview

Code duplication is one of the most common sources of technical debt in software projects. This project serves as:

1. **Educational Resource** - Understanding the many forms code duplication can take
2. **Benchmark Corpus** - Testing and comparing duplicate detection tools
3. **Refactoring Reference** - Seeing how duplicated code can be deduplicated

The corpus contains **54 distinct categories** of duplication patterns, ranging from obvious copy-paste clones to subtle semantic duplication that requires deep analysis to detect.

---

## Features

- **54 Duplication Categories** - Comprehensive coverage of duplication types from exact clones to semantic equivalents
- **Refactored Solutions** - Each category includes deduplicated implementations showing how to eliminate duplication
- **Benchmark Harness** - End-to-end comparison framework for duplicate detection tools
- **Multiple Detection Tools** - Support for phpcpd, jscpd, PMD-CPD, Simian, and phpdup
- **Ground Truth Scoring** - Synthetic corpus with known duplication topology for precise measurement
- **PHP 8.1+ Ready** - Modern PHP patterns and strict typing throughout
- **Corpus Explorer** - Browser-based UI for browsing and filtering the test corpus

---

## Corpus Explorer (Web UI)

A browser-based explorer for viewing and filtering the test corpus.

### Quick Start
```bash
# Open directly in browser (no server needed)
open public_html/index.html

# Or serve locally
python3 -m http.server 8000 -d public_html
# Then visit http://localhost:8000
```

### Features
- Filter by level, family, clone type, difficulty
- Full-text search across titles, descriptions, seeds
- Syntax-highlighted code snippets
- Deep linking via URL (shareable filter views)

### Rebuilding Data
If you modify test sets, regenerate the data file:
```bash
php scripts/generate-corpus-data.php
php scripts/extract-snippets.php
```

---

## Directory Structure

```
php-duplication-samples/
├── samples/                    # 54 categories of duplicated code examples
│   ├── algorithm/              # Algorithm duplication (20 samples)
│   ├── architectural/          # Architectural patterns (10 samples)
│   ├── behavioral/             # Behavioral duplication (20 samples)
│   ├── build/                  # Build/deployment duplication (9 samples)
│   ├── caching/                # Caching patterns (10 samples)
│   ├── clone_type_1/           # Type-1 clones (exact copies) (20 samples)
│   ├── clone_type_2/           # Type-2 clones (renamed) (10 samples)
│   ├── clone_type_3/           # Type-3 clones (modified) (15 samples)
│   ├── clone_type_4/           # Type-4 clones (semantic) (10 samples)
│   ├── configuration/          # Configuration duplication (20 samples)
│   ├── copy_paste/             # Direct copy-paste examples (60 samples)
│   ├── cross_service/          # Cross-service duplication (10 samples)
│   ├── data/                   # Data/constant duplication (20 samples)
│   ├── dependency/             # Dependency injection duplication (10 samples)
│   ├── documentation/         # Documentation duplication (10 samples)
│   ├── error_handling/         # Error handling patterns (various)
│   ├── event/                  # Event handling duplication (10 samples)
│   ├── functional/            # Functional duplication (10 samples)
│   ├── knowledge/             # Business knowledge duplication (20 samples)
│   ├── lexical/                # Lexical patterns (10 samples)
│   ├── localization/          # i18n duplication (10 samples)
│   ├── logic/                  # Business logic duplication (10 samples)
│   ├── mapping/                # Data mapping duplication (10 samples)
│   ├── monitoring/             # Monitoring instrumentation (10 samples)
│   ├── orm_query_duplication/ # ORM query patterns (10 samples)
│   ├── permission/             # Authorization patterns (10 samples)
│   ├── process/                # Process/workflow duplication (10 samples)
│   ├── protocol/               # Protocol handling (50 samples)
│   ├── query/                  # Database query duplication (30 samples)
│   ├── representation/         # Model duplication (20 samples)
│   ├── schema/                 # Schema duplication (10 samples)
│   ├── semantic/               # Semantic duplication (20 samples)
│   ├── serialization/          # Serialization patterns (10 samples)
│   ├── structural/             # Structural duplication (20 samples)
│   ├── syntactic/              # Syntactic patterns (10 samples)
│   ├── temporal/               # Temporal patterns (20 samples)
│   ├── test/                   # Test duplication (20 samples)
│   ├── textual/                # Textual duplication (10 samples)
│   ├── type/                   # Type-specific duplication (20 samples)
│   ├── ui/                     # UI duplication (20 samples)
│   ├── validation_*/            # Validation patterns (email, password, phone)
│   ├── workflow/               # Workflow patterns (10 samples)
│   └── ...                     # Additional categories
│
├── refactored/                # Deduplicated refactorings
│   ├── algorithm/              # Refactored algorithm solutions
│   ├── behavioral/            # Refactored behavioral patterns
│   ├── permission/             # Refactored authorization
│   └── ...                     # One refactored solution per sample
│
├── bench/                     # Benchmark harness
│   ├── run.php                 # Execute all tools on all corpora
│   ├── run-samples.php         # Run detection on sample categories
│   ├── score.php               # Compute precision/recall/F1
│   ├── corpora.php             # Download/manage test corpora
│   ├── comparative.php         # Quick one-shot comparison
│   ├── run-all.sh              # Full benchmark automation
│   ├── feature-matrix.md       # Tool capability comparison
│   ├── tools/                  # Auto-downloaded tools
│   └── results/                # Benchmark results
│
├── samples.json               # Master index of all categories and samples
├── code_duplication_types.md  # Type taxonomy with examples
├── code_duplication_challenges.md  # "Hidden" duplication patterns
└── code_duplication_alternatives.md # Alternative tools reference
```

### Sample File Structure

Each sample directory follows this pattern:

```
samples/<category>/<id>/
├── block_a.php      # First duplicated code block
├── block_b.php      # Second duplicated code block
├── block_c.php      # Third duplicated code block (optional)
└── refactored/
    └── code.php     # Deduplicated refactored solution
```

---

## Duplication Categories

The project defines **54 distinct categories** of code duplication. Here is a summary:

### Clone Classification (Roy & Ossher Taxonomy)

| Category | Description | Samples |
|----------|-------------|---------|
| **clone_type_1** | Exact copy except whitespace/comments | 20 |
| **clone_type_2** | Renamed identifiers/literals | 10 |
| **clone_type_3** | Statements added/removed/changed | 15 |
| **clone_type_4** | Semantic equivalence only | 10 |

### Semantic & Business Duplication

| Category | Description | Samples |
|----------|-------------|---------|
| **semantic** | Same business rule, different code | 20 |
| **knowledge** | Business facts in multiple places | 20 |
| **behavioral** | Same behavior, different implementation | 20 |
| **domain_rule** | Core invariants duplicated | varies |
| **policy** | Business policies scattered | varies |

### Structural Duplication

| Category | Description | Samples |
|----------|-------------|---------|
| **architectural** | Same architecture, different modules | 10 |
| **structural** | Same pipeline/workflow pattern | 20 |
| **syntactic** | Same AST shape, different content | 10 |
| **template** | Same template with different params | varies |

### Code Pattern Duplication

| Category | Description | Samples |
|----------|-------------|---------|
| **algorithm** | Same algorithmic pattern, different thresholds | 20 |
| **logic** | Conditional business rules duplicated | 10 |
| **validation** | Input validation repeated | various |
| **error_handling** | Exception handling patterns | various |
| **caching** | Cache management duplicated | 10 |
| **monitoring** | Instrumentation repeated | 10 |

### Data & Configuration Duplication

| Category | Description | Samples |
|----------|-------------|---------|
| **data** | Constants/literals repeated | 20 |
| **configuration** | Config patterns duplicated | 20 |
| **schema** | Schema definitions repeated | 10 |
| **representation** | Multiple models for same entity | 20 |

### Cross-Cutting Concerns

| Category | Description | Samples |
|----------|-------------|---------|
| **permission** | Authorization checks repeated | 10 |
| **dependency** | Dependency injection duplicated | 10 |
| **documentation** | Docs scattered and inconsistent | 10 |
| **localization** | i18n patterns repeated | 10 |
| **serialization** | Serialization logic duplicated | 10 |

### ORM & Database Patterns

| Category | Description | Samples |
|----------|-------------|---------|
| **query** | SQL/ORM queries repeated | 30 |
| **orm_query_duplication** | Same op, different ORM API | 10 |
| **nosql_document_duplication** | Same op, different NoSQL API | 10 |
| **graph_query_duplication** | Same op, different graph DB API | 5 |
| **timeseries_cache_duplication** | Same op, different cache API | 5 |

### Other Patterns

| Category | Description | Samples |
|----------|-------------|---------|
| **copy_paste** | Direct copy-paste examples | 60 |
| **textual** | Identical text strings | 10 |
| **lexical** | Token-level similarity | 10 |
| **functional** | Same functional outcome, different code | 10 |
| **process** | Manual processes codified | 10 |
| **cross_service** | Duplication across microservices | 10 |
| **build** | CI/CD configuration duplication | 9 |
| **workflow** | Workflow patterns repeated | 10 |
| **ui** | Interface patterns duplicated | 20 |
| **event** | Event handling duplicated | 10 |
| **protocol** | Protocol handling repeated | 50 |
| **test** | Test setup/fixtures repeated | 20 |

---

## Benchmark Tools

The benchmark suite supports the following duplicate detection tools:

| Tool | Type | Description |
|------|------|-------------|
| **phpdup** | Primary | Custom PHP duplicate detector (built into this repo) |
| **phpcpd** | External | Sebastian Bergmann's PHP Copy/Paste Detector |
| **jscpd** | External | Multi-language CPD clone via JavaScript |
| **pmd-cpd** | External | PMD's CPD for PHP (via PHPMD) |
| **simian** | External | Commercial duplicate detector (Java-based) |

### Tool Comparison Matrix

| Feature | phpdup | phpcpd | jscpd | pmd-cpd | simian |
|---------|--------|--------|-------|---------|--------|
| Token-based detection | ✓ | ✓ | ✓ | ✓ | ✓ |
| AST-based detection | ✓ | - | - | - | ✓ |
| Min tokens threshold | ✓ | ✓ | ✓ | ✓ | ✓ |
| Ignore annotations | ✓ | ✓ | ✓ | ✓ | ✓ |
| Language support | PHP | PHP | 223+ | Java, PHP, etc. | Java, C#, etc. |
| JSON output | ✓ | XML | JSON | XML | Text |
| Free/Open Source | ✓ | ✓ | ✓ | ✓ | - |

---

## Getting Started

### Requirements

- **PHP 8.1+** with CLI and required extensions
- **Composer** (for some benchmark dependencies)
- **Node.js 18+** (optional, for jscpd)
- **Git** (for downloading corpora)

### Running Benchmarks

#### Full Benchmark Suite

```bash
# Run the complete benchmark (downloads tools, clones corpora, runs all tests)
./bench/run-all.sh
```

This produces:
- `bench/results/latest.md` - Wall time, RSS, cluster count per (tool, corpus)
- `bench/results/detection-rate.md` - Precision/recall/F1 on synthetic corpus

#### Individual Components

```bash
# Download/refresh test corpora
php bench/corpora.php

# Run only one corpus across all tools
php bench/run.php --corpus=synthetic-fuzz --label=initial

# Score a specific run
php bench/score.php bench/results/initial.json

# Quick comparative check
php bench/comparative.php
```

#### Running on Sample Categories

```bash
# Run detection on all samples
php bench/run-samples.php

# Run on specific category
php bench/run-samples.php --category=permission --id=1
```

### Adding New Samples

#### Sample Structure

Each sample needs:

1. **Duplicated blocks** (`block_a.php`, `block_b.php`, `block_c.php`)
2. **Refactored solution** (`refactored/code.php`)
3. **Entry in `samples.json`**

#### Example

```php
// samples/my_category/1/block_a.php
<?php
declare(strict_types=1);

namespace App\Example;

class ServiceA
{
    public function process(string $input): string
    {
        $trimmed = trim($input);
        $lower = strtolower($trimmed);
        return strtoupper($lower);
    }
}
```

```php
// samples/my_category/1/refactored/code.php
<?php
declare(strict_types=1);

namespace App\Example;

class StringNormalizer
{
    public function normalize(string $input): string
    {
        return strtoupper(trim(strtolower($input)));
    }
}
```

#### Adding to samples.json

```json
{
  "name": "my_category",
  "description": "Description of the duplication pattern",
  "sample_count": 1,
  "samples": [
    {
      "id": 1,
      "source_file": "type_my_category_duplication_1.md"
    }
  ]
}
```

---

## Contributing

### Adding New Duplication Categories

1. Create the directory: `samples/my-new-category/1/`
2. Add `block_a.php`, `block_b.php` (duplicated code)
3. Add `refactored/code.php` (deduplicated solution)
4. Update `samples.json` with the category definition
5. Update this README with the new category

### Adding Refactored Solutions

Place the deduplicated, clean implementation in:
```
samples/<category>/<id>/refactored/code.php
```

### Style Guidelines

- Use PHP 8.1+ features (readonly classes, named arguments, match expressions)
- Include `declare(strict_types=1)` in all files
- Use meaningful namespace based on the duplication type
- Include both duplicated blocks AND refactored solution
- Document why the duplication is problematic in comments

---

## License

MIT License

Copyright (c) 2026 Joe Huss

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

---

## References

### Duplicate Detection Tools

- [phpcpd](https://github.com/sebastianbergmann/phpcpd) - PHP Copy/Paste Detector by Sebastian Bergmann
- [PHPMD](https://github.com/phpmd/phpmd) - PHP Mess Detector (includes CPD)
- [jscpd](https://github.com/kucherenko/jscpd) - Multi-language copy/paste detector
- [Simian](https://www.harukizaemon.com/simian/) - Commercial duplicate detector

### PHP Static Analysis

- [PHPStan](https://github.com/phpstan/phpstan) - PHP Static Analysis Tool
- [Psalm](https://github.com/vimeo/psalm) - PHP static analysis tool

### Clone Detection Research

The categorization in this project follows the Roy & Ossher taxonomy for code clones:

- **Type-1**: Exact copy except whitespace/comments
- **Type-2**: Renamed identifiers/literals
- **Type-3**: Statements added/removed/changed  
- **Type-4**: Semantic equivalence only

### Additional Reading

- [Code Duplication Types](./code_duplication_types.md) - Detailed taxonomy with PHP examples
- [Hidden Duplication Challenges](./code_duplication_challenges.md) - Why detection is hard
- [Tool Alternatives](./code_duplication_alternatives.md) - Other detection tools

---

## Project Statistics

- **54** Duplication categories
- **600+** Sample instances
- **5** Supported detection tools
- **4** Clone types (Roy & Ossher taxonomy)

---

*This project is a reference corpus for understanding, detecting, and refactoring code duplication in PHP projects.*
