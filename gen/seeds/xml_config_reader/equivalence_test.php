<?php
/**
 * Equivalence test for xml_config_reader.
 * Verifies XML parsing into arrays.
 *
 * Run: php gen/seeds/xml_config_reader/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$reader = new $className();

$testCases = [
    [
        'xml' => '<config><name>Test</name><value>42</value></config>',
        'expectKeys' => ['name', 'value'],
        'expectValues' => ['Test', '42'],
    ],
    [
        'xml' => '<settings><debug>true</debug></settings>',
        'expectKeys' => ['debug'],
        'expectValues' => ['true'],
    ],
    [
        'xml' => '<invalid',
        'expectNull' => true,
    ],
];

foreach ($testCases as $i => $case) {
    $result = $reader->parseXmlConfig($case['xml']);

    if (isset($case['expectNull'])) {
        if ($result !== null) {
            $pass = false;
            $errors[] = "xml_config_reader case {$i}: expected null for invalid XML";
        }
        continue;
    }

    if ($result === null) {
        $pass = false;
        $errors[] = "xml_config_reader case {$i}: expected valid parse";
        continue;
    }

    foreach ($case['expectKeys'] as $j => $key) {
        if (!isset($result[$key])) {
            $pass = false;
            $errors[] = "xml_config_reader case {$i}: missing key $key";
            continue;
        }
        if ((string) $result[$key] !== $case['expectValues'][$j]) {
            $pass = false;
            $errors[] = "xml_config_reader case {$i}: key $key value mismatch";
        }
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}