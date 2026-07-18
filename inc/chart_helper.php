<?php
declare(strict_types=1);

function chart_json(array $labels, array $values): string {
    return json_encode([
        'labels' => array_values($labels),
        'values' => array_values($values),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}