#!/usr/bin/env php
<?php
/**
 * update-slugs-from-h1.php
 *
 * Меняет slug в database/seeders/data/categories.yaml на basename из
 * database/seeders/data/h1_map.yaml, где found[basename] == name.
 *
 * Опции:
 *   --categories=database/seeders/data/categories.yaml
 *   --h1map=database/seeders/data/h1_map.yaml
 *   --out=database/seeders/data/categories.yaml   (по умолчанию — in-place)
 *   --report=storage/app/slug_update_report.txt
 *   --no-normalize     (отключить нормализацию пробелов/переносов при сравнении)
 *
 * Требует: composer require symfony/yaml
 */

use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/vendor/autoload.php';

[$opts] = parseOptions([
    'categories::', 'h1map::', 'out::', 'report::', 'no-normalize'
]);

$categoriesPath = $opts['categories'] ?? 'database/seeders/data/categories.yaml';
$h1MapPath      = $opts['h1map']      ?? 'database/seeders/data/h1_map.yaml';
$outPath        = $opts['out']        ?? $categoriesPath;
$reportPath     = $opts['report']     ?? 'storage/app/slug_update_report.txt';
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
if (!is_array($found)) {
    fwrite(STDERR, "Ожидаю секцию 'found' в {$h1MapPath}\n");
    exit(1);
}

// reverse map: normalized(H1) => basename (учтём дубликаты)
$h1ToBase = [];
$dups     = []; // для одинаковых H1 в разных файлах
foreach ($found as $basename => $h1Text) {
    if (!is_string($h1Text)) continue;
    $key = $doNormalize ? norm($h1Text) : $h1Text;
    if (isset($h1ToBase[$key]) && $h1ToBase[$key] !== $basename) {
        $dups[$key] = array_unique(array_merge($dups[$key] ?? [$h1ToBase[$key]], [$basename]));
    } else {
        $h1ToBase[$key] = $basename;
    }
}

if ($dups) {
    fwrite(STDERR, "ВНИМАНИЕ: найдены дубликаты одинаковых H1 в разных файлах. Эти имена будут пропущены:\n");
    foreach ($dups as $h1norm => $bases) {
        fwrite(STDERR, "  H1: \"" . denorm($h1norm) . "\" → [" . implode(', ', $bases) . "]\n");
        unset($h1ToBase[$h1norm]); // исключаем неоднозначные
    }
}

// 3) Обход категорий и обновление slug
$stats = ['total' => 0, 'updated' => 0, 'skipped' => 0, 'unmatched' => 0];

$updatedTree = updateTree($categories, $h1ToBase, $stats, $doNormalize);

// 4) Сохраняем YAML
file_put_contents($outPath, Yaml::dump($updatedTree, 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

// 5) Отчёт
$report = [];
$report[] = "Updated file: {$outPath}";
$report[] = "Total nodes: {$stats['total']}";
$report[] = "Updated:     {$stats['updated']}";
$report[] = "Skipped:     {$stats['skipped']}   (slug уже совпадает с basename)";
$report[] = "Unmatched:   {$stats['unmatched']} (нет соответствия по H1 → basename)";
$report[] = "Duplicates of H1 skipped: " . count($dups);
$report[] = "";
$report[] = "Note: сопоставление делалось по полю 'name' (категории) == 'H1' (из h1_map.yaml).";
$report[] = $doNormalize
    ? "Normalization: включена (схлопывание пробелов и трим). Отключите флагом --no-normalize."
    : "Normalization: отключена.";
ensureDir(dirname($reportPath));
file_put_contents($reportPath, implode(PHP_EOL, $report) . PHP_EOL);

echo implode(PHP_EOL, $report) . PHP_EOL;


/* ================= helpers ================ */

/**
 * Рекурсивно обходит дерево категорий (массив узлов) и обновляет slug,
 * если есть точное соответствие по имени → H1.
 */
function updateTree(array $nodeOrList, array $h1ToBase, array &$stats, bool $normalize): array
{
    // корень может быть списком узлов
    if (array_is_list($nodeOrList)) {
        $out = [];
        foreach ($nodeOrList as $n) $out[] = updateTree($n, $h1ToBase, $stats, $normalize);
        return $out;
    }

    // один узел
    $node = $nodeOrList;
    $stats['total']++;

    $name = $node['name'] ?? null;
    $slug = $node['slug'] ?? null;

    if (is_string($name)) {
        $key = $normalize ? norm($name) : $name;

        if (isset($h1ToBase[$key])) {
            $basename = $h1ToBase[$key];
            if ($slug === $basename) {
                $stats['skipped']++;
            } else {
                $node['slug'] = $basename;
                $stats['updated']++;
            }
        } else {
            // нет совпадения — оставляем как есть
            $stats['unmatched']++;
        }
    }

    if (!empty($node['children']) && is_array($node['children'])) {
        $node['children'] = updateTree($node['children'], $h1ToBase, $stats, $normalize);
    }

    return $node;
}

/** Нормализация строки для сравнения: схлопнуть пробелы/переносы, trim */
function norm(string $s): string
{
    $s = preg_replace('~\s+~u', ' ', $s);
    return trim($s);
}

/** Для сообщений о дубликатах — обратное к norm, чтобы показывать «как было» */
function denorm(string $s): string
{
    return $s;
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

function ensureDir(string $dir): void
{
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}
