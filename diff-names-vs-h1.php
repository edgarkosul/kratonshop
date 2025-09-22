#!/usr/bin/env php
<?php
/**
 * diff-names-vs-h1.php
 *
 * Находит все категории name в categories.yaml,
 * которых нет в значениях h1_map.yaml.
 *
 * Использование:
 *   php diff-names-vs-h1.php \
 *     --categories=database/seeders/data/categories.yaml \
 *     --h1map=database/seeders/data/h1_map.yaml \
 *     --out=storage/app/missing_names.txt
 */

use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/vendor/autoload.php';

[$opts] = parseOptions([
    'categories::', 'h1map::', 'out::'
]);

$categoriesPath = $opts['categories'] ?? 'database/seeders/data/categories.yaml';
$h1MapPath      = $opts['h1map']      ?? 'database/seeders/data/h1_map.yaml';
$outPath        = $opts['out']        ?? 'storage/app/missing_names.txt';

foreach ([$categoriesPath, $h1MapPath] as $p) {
    if (!file_exists($p)) {
        fwrite(STDERR, "Файл не найден: {$p}\n");
        exit(1);
    }
}

// Загружаем YAML
$categories = Yaml::parseFile($categoriesPath);
$h1MapYaml  = Yaml::parseFile($h1MapPath);

// Собираем все name из categories.yaml
$allNames = [];
collectNames($categories, $allNames);

// Собираем все значения H1 из h1_map.yaml
$found = $h1MapYaml['found'] ?? [];
$h1Values = array_map('trim', array_values($found));

// Сравниваем
$missing = array_diff($allNames, $h1Values);

// Сохраняем результат
if ($missing) {
    ensureDir(dirname($outPath));
    file_put_contents($outPath, implode(PHP_EOL, $missing) . PHP_EOL);
    echo "Найдено несовпадений: " . count($missing) . "\n";
    echo "Список сохранён в {$outPath}\n";
} else {
    echo "Все name найдены в h1_map.yaml!\n";
}

/* ===== helpers ===== */

function collectNames($node, array &$names): void
{
    if (is_array($node)) {
        if (array_is_list($node)) {
            foreach ($node as $item) {
                collectNames($item, $names);
            }
        } else {
            if (isset($node['name'])) {
                $names[] = trim($node['name']);
            }
            if (isset($node['children']) && is_array($node['children'])) {
                collectNames($node['children'], $names);
            }
        }
    }
}

function parseOptions(array $long): array
{
    if (function_exists('getopt')) {
        $opts = getopt('', $long);
        return [$opts ?: []];
    }
    $opts = [];
    foreach (array_slice($_SERVER['argv'], 1) as $a) {
        if (str_starts_with($a, '--') && strpos($a, '=') !== false) {
            [$k, $v] = explode('=', substr($a, 2), 2);
            $opts[$k] = $v;
        } elseif (str_starts_with($a, '--')) {
            $opts[substr($a, 2)] = true;
        }
    }
    return [$opts];
}

function ensureDir(string $dir): void
{
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}
