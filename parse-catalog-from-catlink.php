#!/usr/bin/env php
<?php
/**
 * parse-catalog-from-catlink.php
 *
 * Собирает дерево каталога из <div class="catlink">...</div> по всем *.php (CP1251).
 *
 * Опции (все необязательны):
 *   --dir=./path                            стартовая директория (по умолчанию ".")
 *   --ext=php                               расширение файлов
 *   --out=database/seeders/data/categories.yaml  файл для YAML-результата
 *   --logdir=storage/app/catlink_logs       папка с логами
 *   --ignore=node_modules,vendor,.git       каталоги-исключения (через запятую)
 *
 * Формат YAML:
 *   - name: ...
 *     slug: ...
 *     children:
 *       - name: ...
 *         slug: ...
 *         children: [...]
 */

use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/vendor/autoload.php';

// ---------- опции ----------
[$opts] = parseOptions(['dir::','ext::','out::','logdir::','ignore::']);

$startDir = realpath($opts['dir'] ?? '.') ?: '.';
$ext      = ltrim($opts['ext'] ?? 'php', '.');
$outPath  = $opts['out'] ?? 'database/seeders/data/categories.yaml';
$logDir   = rtrim($opts['logdir'] ?? 'storage/app/catlink_logs', '/');

$ignore = array_filter(array_map('trim', explode(',', $opts['ignore'] ?? 'node_modules,vendor,.git,.idea,.vscode,storage/logs,bootstrap/cache')));

$startDir = rtrim($startDir, DIRECTORY_SEPARATOR);
$extLower = strtolower($ext);

// ---------- подготовка логов ----------
ensureDir($logDir);
$logNoCatlink  = fopen($logDir . '/no_catlink.txt', 'w');
$logEmptyTrail = fopen($logDir . '/empty_breadcrumbs.txt', 'w');
$logParseErr   = fopen($logDir . '/parse_errors.txt', 'w');
$logStats      = fopen($logDir . '/stats.txt', 'w');

if (!$logNoCatlink || !$logEmptyTrail || !$logParseErr || !$logStats) {
    fwrite(STDERR, "Не удалось открыть лог-файлы в {$logDir}\n");
    exit(1);
}

// ---------- дерево как ассоц. структура ----------
$tree = createNode('ROOT', '__root__'); // виртуальный корень, не выводится

$total = 0; $withCatlink = 0; $insertedPaths = 0;
$start = microtime(true);

// итератор по файлам
$iter = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($startDir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
        function (SplFileInfo $cur) use ($startDir, $ignore) {
            if ($cur->isDir()) {
                $name = $cur->getFilename();
                if (in_array($name, $ignore, true)) return false;
                // отфильтровать подпути "storage/logs" и т.п.
                $rel = ltrim(str_replace('\\', '/', substr($cur->getPathname(), strlen($startDir))), '/');
                foreach ($ignore as $ig) {
                    $ig = trim($ig, '/');
                    if ($ig !== '' && strpos($rel.'/', $ig.'/') !== false) return false;
                }
            }
            return true;
        }
    ),
    RecursiveIteratorIterator::LEAVES_ONLY,
    RecursiveIteratorIterator::CATCH_GET_CHILD
);

// регексы для быстрого извлечения
$divRe = '~<\s*div\b[^>]*class\s*=\s*("|\')([^"\']*?\bcatlink\b[^"\']*)\1[^>]*>(.*?)<\s*/\s*div\s*>~is';
$aRe   = '~<\s*a\b[^>]*href\s*=\s*("|\')([^"\']+)\1[^>]*>(.*?)<\s*/\s*a\s*>~is';

foreach ($iter as $file) {
    /** @var SplFileInfo $file */
    if (!$file->isFile()) continue;

    $name = $file->getFilename();
    $dot  = strrpos($name, '.');
    if ($dot === false) continue;
    $fileExt = strtolower(substr($name, $dot + 1));
    if ($fileExt !== $extLower) continue;

    $total++;
    $absPath = $file->getPathname();
    $relPath = ltrim(str_replace('\\', '/', substr($absPath, strlen($startDir))), '/');

    // читаем и конвертируем CP1251 -> UTF-8
    $raw = @file_get_contents($absPath);
    if ($raw === false) {
        fwrite($logParseErr, "READ_FAIL\t{$relPath}\n");
        continue;
    }
    $html = @iconv('CP1251', 'UTF-8//IGNORE', $raw);
    if ($html === false) {
        fwrite($logParseErr, "ICONV_FAIL\t{$relPath}\n");
        continue;
    }

    // ищем div.catlink (регистронезависимо)
    if (!preg_match($divRe, $html, $dm)) {
        fwrite($logNoCatlink, $relPath . "\n");
        continue;
    }
    $withCatlink++;

    $divInner = $dm[3];

    // извлекаем все <a href="…">…</a>
    if (!preg_match_all($aRe, $divInner, $am, PREG_SET_ORDER)) {
        fwrite($logEmptyTrail, $relPath . "\n");
        continue;
    }

    // игнор первых двух ссылок ("/" и "catal1.php")
    $links = array_slice($am, 2);
    if (!$links) {
        fwrite($logEmptyTrail, $relPath . "\n");
        continue;
    }

    // Сформировать путь: список [ ['slug'=>..., 'name'=>...], ... ]
    $path = [];
    foreach ($links as $link) {
        $href = trim($link[2] ?? '');
        $text = normalizeText($link[3] ?? '');

        if ($href === '' || $text === '') continue;

        // slug = имя файла из href без .php (с учётом возможных query/фрагментов)
        $slug = hrefToSlug($href);
        if ($slug === '') continue;

        $path[] = ['slug' => $slug, 'name' => $text];
    }

    if (!$path) {
        fwrite($logEmptyTrail, $relPath . "\n");
        continue;
    }

    // вставляем путь в дерево
    insertPath($tree, $path);
    $insertedPaths++;

    if (($insertedPaths % 1000) === 0) {
        echo "processed paths: {$insertedPaths}, scanned files: {$total}\n";
    }
}

// преобразуем дерево в массив для YAML (без виртуального корня)
$result = childrenToList($tree['children']);

// пишем YAML (всегда строки в одинарных кавычках безопасно)
file_put_contents($outPath, Yaml::dump($result, 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

$dt = number_format(microtime(true) - $start, 2, '.', '');
fwrite($logStats, "Scanned files:   {$total}\n");
fwrite($logStats, "With catlink:    {$withCatlink}\n");
fwrite($logStats, "Inserted paths:  {$insertedPaths}\n");
fwrite($logStats, "Elapsed:         {$dt}s\n");

fclose($logNoCatlink);
fclose($logEmptyTrail);
fclose($logParseErr);
fclose($logStats);

echo "Готово. YAML: {$outPath}\n";
echo "Логи: {$logDir}/no_catlink.txt, empty_breadcrumbs.txt, parse_errors.txt, stats.txt\n";

/* ================== helpers ================== */

function parseOptions(array $long): array {
    if (function_exists('getopt')) {
        $opts = getopt('', $long);
        return [$opts ?: []];
    }
    $o = [];
    foreach (array_slice($_SERVER['argv'], 1) as $a) {
        if (substr($a,0,2) === '--' && strpos($a,'=') !== false) {
            [$k,$v] = explode('=', substr($a,2), 2);
            $o[$k] = $v;
        } elseif (substr($a,0,2) === '--') {
            $o[substr($a,2)] = true;
        }
    }
    return [$o];
}

function ensureDir(string $dir): void {
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
}

function createNode(string $name, string $slug): array {
    return ['name' => $name, 'slug' => $slug, 'children' => [] /* map slug => node */];
}

function normalizeText(string $s): string {
    // убрать теги, сущности, схлопнуть пробелы
    $s = strip_tags($s);
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $s = preg_replace('~\s+~u', ' ', $s);
    return trim($s);
}

function hrefToSlug(string $href): string {
    // убрать query/fragment
    $href = preg_replace('~[#?].*$~', '', $href);
    // basename (может быть подкаталог)
    $base = basename($href);
    // снять расширение .php (регистр независим)
    if (preg_match('~^(.*)\.php$~i', $base, $m)) {
        return strtolower($m[1]);
    }
    // если нет .php — взять всё имя (на случай статических директорий)
    return strtolower($base);
}

function insertPath(array &$root, array $path): void {
    $node =& $root;
    foreach ($path as $step) {
        $slug = $step['slug'];
        $name = $step['name'];

        if (!isset($node['children'][$slug])) {
            $node['children'][$slug] = createNode($name, $slug);
        } else {
            // если имя новое длиннее — обновим (часто бывает более полное)
            $currName = $node['children'][$slug]['name'] ?? '';
            if (mb_strlen($name, 'UTF-8') > mb_strlen((string)$currName, 'UTF-8')) {
                $node['children'][$slug]['name'] = $name;
            }
        }
        $node =& $node['children'][$slug];
    }
}

function childrenToList(array $childrenMap): array {
    $list = [];
    foreach ($childrenMap as $slug => $node) {
        $entry = ['name' => $node['name'], 'slug' => $node['slug']];
        if (!empty($node['children'])) {
            $entry['children'] = childrenToList($node['children']);
        }
        $list[] = $entry;
    }

    // сортируем по name без учёта регистра
    usort($list, function($a, $b) {
        return strcmp(
            mb_strtolower($a['name'], 'UTF-8'),
            mb_strtolower($b['name'], 'UTF-8')
        );
    });

    return $list;
}
