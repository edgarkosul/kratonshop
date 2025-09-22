#!/usr/bin/env php
<?php
/**
 * remove-slugs.php — удаляет все поля "slug" из categories.yaml (рекурсивно).
 *
 * Опции:
 *   --in=database/seeders/data/categories.yaml
 *   --out=database/seeders/data/categories.yaml   (in-place по умолчанию)
 *   --backup                                      (сделать .bak рядом с исходным)
 *
 * Требует: composer require symfony/yaml
 */

use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/vendor/autoload.php';

[$opts] = parseOptions(['in::', 'out::', 'backup']);

$in  = $opts['in']  ?? 'database/seeders/data/categories.yaml';
$out = $opts['out'] ?? $in;

if (!file_exists($in)) {
    fwrite(STDERR, "Не найден файл: {$in}\n");
    exit(1);
}

if (isset($opts['backup'])) {
    copy($in, $in . '.bak');
    echo "Backup: {$in}.bak\n";
}

$data = Yaml::parseFile($in);
$stats = ['nodes' => 0, 'removed' => 0];

$data = stripSlugs($data, $stats);

// глубина 6 на всякий случай, отступ 2 пробела
file_put_contents($out, Yaml::dump($data, 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

echo "Готово. Узлов: {$stats['nodes']}, удалено slug: {$stats['removed']}\n";
echo "Сохранено в: {$out}\n";

/* ---------- helpers ---------- */

function stripSlugs($node, array &$stats)
{
    $stats['nodes']++;

    // если это список — обработаем каждый элемент
    if (is_array($node) && array_is_list($node)) {
        foreach ($node as $i => $child) {
            $node[$i] = stripSlugs($child, $stats);
        }
        return $node;
    }

    // если это ассоц. массив узла категории
    if (is_array($node)) {
        if (array_key_exists('slug', $node)) {
            unset($node['slug']);
            $stats['removed']++;
        }
        if (isset($node['children']) && is_array($node['children'])) {
            $node['children'] = stripSlugs($node['children'], $stats);
        }
    }

    return $node;
}

function parseOptions(array $long): array
{
    if (function_exists('getopt')) {
        $o = getopt('', $long);
        return [$o ?: []];
    }
    $o = [];
    foreach (array_slice($_SERVER['argv'], 1) as $a) {
        if (str_starts_with($a, '--') && strpos($a, '=') !== false) {
            [$k, $v] = explode('=', substr($a, 2), 2);
            $o[$k] = $v;
        } elseif (str_starts_with($a, '--')) {
            $o[substr($a, 2)] = true;
        }
    }
    return [$o];
}
