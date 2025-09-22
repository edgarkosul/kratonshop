#!/usr/bin/env php
<?php
/**
 * add-slugs-from-h1.php
 *
 * Добавляет slug в categories.yaml по соответствию name ⇔ H1 из h1_map.yaml.
 * Где нет совпадения — slug: "".
 * Логирует все не найденные name.
 *
 * Опции:
 *   --categories=database/seeders/data/categories.yaml
 *   --h1map=database/seeders/data/h1_map.yaml
 *   --out=database/seeders/data/categories.yaml   (по умолчанию in-place)
 *   --log=storage/app/missing_slug_names.txt
 *   --no-normalize   (отключить нормализацию пробелов при сравнении)
 *
 * Требует: composer require symfony/yaml
 */

use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/vendor/autoload.php';

[$opts] = parseOptions([
    'categories::', 'h1map::', 'out::', 'log::', 'no-normalize'
]);

$categoriesPath = $opts['categories'] ?? 'database/seeders/data/categories.yaml';
$h1MapPath      = $opts['h1map']      ?? 'database/seeders/data/h1_map.yaml';
$outPath        = $opts['out']        ?? $categoriesPath;
$logPath        = $opts['log']        ?? 'storage/app/missing_slug_names.txt';
$doNormalize    = !isset($opts['no-normalize']);

foreach ([$categoriesPath, $h1MapPath] as $p) {
    if (!file_exists($p)) {
        fwrite(STDERR, "Не найден файл: {$p}\n");
        exit(1);
    }
}

// 1) Загружаем YAML
$categories = Yaml::parseFile($categoriesPath);
$h1MapYaml  = Yaml::parseFile($h1MapPath);

// 2) Достаём map H1 → basename
$found = $h1MapYaml['found'] ?? [];
$h1ToBase = [];
foreach ($found as $basename => $h1Text) {
    if (!is_string($h1Text)) continue;
    $key = $doNormalize ? norm($h1Text) : $h1Text;
    $h1ToBase[$key] = $basename;
}

// 3) Обновляем дерево категорий
$stats = ['total' => 0, 'set' => 0, 'empty' => 0];
$missingNames = [];

$updated = addSlugs($categories, $h1ToBase, $stats, $missingNames, $doNormalize);

// 4) Сохраняем categories.yaml
file_put_contents($outPath, Yaml::dump($updated, 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

// 5) Логируем отсутствующие
if ($missingNames) {
    ensureDir(dirname($logPath));
    file_put_contents($logPath, implode(PHP_EOL, $missingNames) . PHP_EOL);
}

echo "Обработано узлов: {$stats['total']}\n";
echo "Slug добавлено:   {$stats['set']}\n";
echo "Пустых slug:      {$stats['empty']} (см. {$logPath})\n";
echo "Результат сохранён в {$outPath}\n";

/* ============= helpers ============= */

function addSlugs($nodeOrList, array $h1ToBase, array &$stats, array &$missingNames, bool $normalize)
{
    if (is_array($nodeOrList) && array_is_list($nodeOrList)) {
        $out = [];
        foreach ($nodeOrList as $n) $out[] = addSlugs($n, $h1ToBase, $stats, $missingNames, $normalize);
        return $out;
    }

    if (!is_array($nodeOrList)) {
        return $nodeOrList;
    }

    $stats['total']++;

    $node = $nodeOrList;
    $name = $node['name'] ?? null;

    if (is_string($name)) {
        $key = $normalize ? norm($name) : $name;
        if (isset($h1ToBase[$key])) {
            $node['slug'] = $h1ToBase[$key];
            $stats['set']++;
        } else {
            $node['slug'] = "";
            $stats['empty']++;
            $missingNames[] = $name;
        }
    }

    if (!empty($node['children']) && is_array($node['children'])) {
        $node['children'] = addSlugs($node['children'], $h1ToBase, $stats, $missingNames, $normalize);
    }

    return $node;
}

function norm(string $s): string
{
    $s = preg_replace('~\s+~u', ' ', $s);
    return trim($s);
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
