<?php

$files = glob(__DIR__ . '/gen/recipes/L05_refactorability/*.json');

foreach ($files as $filepath) {
    $content = file_get_contents($filepath);
    $data = json_decode($content, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        echo "SKIP (cannot decode): " . basename($filepath) . " - " . json_last_error_msg() . "\n";
        continue;
    }

    $modified = false;
    foreach ($data['sets'] ?? [] as &$set) {
        if (isset($set['solution']) && is_string($set['solution'])) {
            // The solution string has actual newlines that need to be \n escape sequences
            // and other special chars need proper escaping
            $fixed = json_encode($set['solution']);
            if ($fixed !== false && $fixed !== '"' . $set['solution'] . '"') {
                $set['solution'] = json_decode($fixed);
                $modified = true;
            }
        }
    }
    unset($set);

    if ($modified) {
        $newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($filepath, $newContent . "\n");
        echo "FIXED: " . basename($filepath) . "\n";
    } else {
        echo "OK: " . basename($filepath) . "\n";
    }
}
