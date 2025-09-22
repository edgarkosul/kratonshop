#!/usr/bin/env php
<?php
/**
 * check-slugs.php — сравнивает слаги из YAML со списком php_basenames.txt
 *
 * Опции:
 *   --yaml=database/seeders/data/categories.yaml
 *   --basenames=php_basenames.txt
 *   --out=missing_slugs.txt
 */

use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/vendor/autoload.php'; // нужен symfony/yaml

[$opts] = parseOptions([
    'yaml::', 'basenames::', 'out::'
]);

$yamlPath     = $opts['yaml']      ?? 'database/seeders/data/categories.yaml';
$basenamesTxt = $opts['basenames'] ?? 'php_basenames.txt';
$outFile      = $opts['out']       ?? 'missing_slugs.txt';

if (!file_exists($yamlPath)) {
    fwrite(STDERR, "YAML не найден: {$yamlPath}\n");
    exit(1);
}
if (!file_exists($basenamesTxt)) {
    fwrite(STDERR, "Файл php_basenames.txt не найден: {$basenamesTxt}\n");
    exit(1);
}

// 1) Загружаем слаги из YAML
$data  = Yaml::parseFile($yamlPath);
$slugs = collectSlugs($data);

// 2) Загружаем базовые имена из файла
$basenames = array_filter(array_map('trim', file($basenamesTxt, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)));

// 3) Сравниваем
$missing = array_diff($slugs, $basenames);

// 4) Вывод и сохранение
if (!$missing) {
    echo "✅ Все слаги нашли файлы\n";
    file_put_contents($outFile, "Все слаги нашли файлы\n");
} else {
    echo "❌ Отсутствуют файлы для " . count($missing) . " слугов:\n";
    foreach ($missing as $slug) {
        echo " - {$slug}\n";
    }
    file_put_contents($outFile, implode(PHP_EOL, $missing) . PHP_EOL);
    echo "\nСписок сохранён в {$outFile}\n";
}

// === функции ===

function collectSlugs(array $nodes): array
{
    $slugs = [];
    $stack = (isset($nodes['slug']) || isset($nodes['name'])) ? [$nodes] : $nodes;

    while ($stack) {
        $n = array_pop($stack);
        if (!is_array($n)) continue;

        if (!empty($n['slug'])) {
            $slugs[] = (string)$n['slug'];
        }
        if (!empty($n['children']) && is_array($n['children'])) {
            foreach ($n['children'] as $c) $stack[] = $c;
        }
    }
    return array_values(array_unique($slugs));
}

function parseOptions(array $long): array
{
    if (function_exists('getopt')) {
        $opts = getopt('', $long);
        return [$opts ?: []];
    }
    $opts = [];
    foreach (array_slice($_SERVER['argv'], 1) as $a) {
        if (substr($a, 0, 2) === '--' && strpos($a, '=') !== false) {
            [$k, $v] = explode('=', substr($a, 2), 2);
            $opts[$k] = $v;
        } elseif (substr($a, 0, 2) === '--') {
            $opts[substr($a, 2)] = true;
        }
    }
    return [$opts];
}
