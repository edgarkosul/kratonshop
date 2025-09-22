#!/usr/bin/env php
<?php
/**
 * titles-to-yaml.php
 *
 * Рекурсивно проходит по *.php (CP1251):
 *  - name: из <h1> (приоритет) или <title>
 *  - slug: имя файла без .php
 *  - images: href всех <a ... class=highslide ...> (картинки товаров/галерей)
 *  - description: текст из itemprop="description" (BR → \n)
 *  - is_category: true/false — по слугам из categories.yaml
 *  - + ДОБАВЛЕНО: для категорий добавляем картинки, если на них ссылаются
 *      <a href="category.php"> <img src="..."> … </a> из других файлов
 *
 * Пример:
 *   php titles-to-yaml.php \
 *     --dir=/abs/path/to/php/files \
 *     --out=database/seeders/data/titles.yaml \
 *     --categories=database/seeders/data/categories.yaml \
 *     --ignore=vendor,node_modules,.git
 *
 * Требует: composer require symfony/yaml
 */

use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/vendor/autoload.php';

[$opts] = parseOptions(['dir:', 'out:', 'categories::', 'ignore::']);
if (empty($opts['dir']) || empty($opts['out'])) {
    fwrite(STDERR, "Usage: php titles-to-yaml.php --dir=DIR --out=OUTPUT.yaml [--categories=categories.yaml] [--ignore=dirs]\n");
    exit(1);
}

$startDir = realpath($opts['dir']);
if ($startDir === false) {
    throw new UnexpectedValueException("Directory not found: {$opts['dir']}");
}
$outPath = $opts['out'];
$ignore  = array_filter(array_map('trim', explode(',', $opts['ignore'] ?? 'vendor,node_modules,.git,.idea,.vscode,storage/logs')));

/** 1) Читаем categories.yaml и собираем множество slug’ов категорий */
$categorySlugs = [];
if (!empty($opts['categories'])) {
    $categoriesPath = $opts['categories'];
    if (!is_file($categoriesPath)) {
        fwrite(STDERR, "⚠️  categories file not found: {$categoriesPath}\n");
    } else {
        try {
            $cats = Yaml::parseFile($categoriesPath);
            $categorySlugs = collectCategorySlugs($cats);
        } catch (Throwable $e) {
            fwrite(STDERR, "⚠️  failed to parse categories.yaml: {$e->getMessage()}\n");
        }
    }
}
$categorySlugsSet = array_fill_keys($categorySlugs, true);

/** 2) Итерируем файлы */
$entries = [];
// карта внешних картинок категорий: slug => [src1, src2, ...]
$categoryImagesFromRefs = [];

$rii = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($startDir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
        function (SplFileInfo $cur) use ($startDir, $ignore) {
            if ($cur->isDir()) {
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

// Регексы заголовков
$reH1    = '~<\s*h1\b[^>]*>(.*?)<\s*/\s*h1\s*>~is';
$reTitle = '~<\s*title\b[^>]*>(.*?)<\s*/\s*title\s*>~is';

// Регексы для <a> и <img>
$reATagOpen  = '~<\s*a\b([^>]*)>~i';                       // для highslide-изображений (как раньше)
$reATagBlock = '~<\s*a\b([^>]*)>(.*?)</\s*a\s*>~is';       // блок целиком, чтобы искать <img> внутри
$reImgTag    = '~<\s*img\b([^>]*)>~i';

// Регекс для атрибутов
$reAttr = '~\b([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))~';

// itemprop="description"
$reItemprop = '~<\s*([a-z0-9]+)\b[^>]*\bitemprop\s*=\s*(["\'])description\2[^>]*>(.*?)</\s*\1\s*>~is';

foreach ($rii as $file) {
    /** @var SplFileInfo $file */
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') continue;

    $path = $file->getPathname();
    $raw  = @file_get_contents($path);
    if ($raw === false) {
        fwrite(STDERR, "⚠️  Не удалось прочитать: {$path}\n");
        continue;
    }

    // CP1251 -> UTF-8
    $html = @iconv('CP1251', 'UTF-8//IGNORE', $raw);
    if ($html === false) {
        fwrite(STDERR, "⚠️  Ошибка кодировки (iconv): {$path}\n");
        continue;
    }

    // name: h1 -> title
    $name = null;
    if (preg_match($reH1, $html, $m)) {
        $name = normalizeText($m[1]);
    } elseif (preg_match($reTitle, $html, $m)) {
        $name = normalizeText($m[1]);
    }
    if ($name === null || $name === '') {
        fwrite(STDERR, "⚠️  Нет <h1> и <title>: {$path}\n");
        continue;
    }

    // slug из имени файла
    $slug = pathinfo($path, PATHINFO_FILENAME);

    // images: <a ... class=highslide ... href="..."> (как раньше)
    $images = [];
    if (preg_match_all($reATagOpen, $html, $aMatches, PREG_SET_ORDER)) {
        foreach ($aMatches as $tag) {
            $attrStr = $tag[1] ?? '';
            if ($attrStr === '') continue;

            $attrs = parseAttributes($attrStr, $reAttr);
            $class = $attrs['class'] ?? '';
            if ($class === '' || !preg_match('~\bhighslide\b~i', $class)) continue;

            $href = $attrs['href'] ?? '';
            if ($href === '') continue;

            $href = html_entity_decode(trim($href), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $images[] = $href;
        }
    }
    $images = $images ? array_values(array_unique($images)) : [];

    // description: itemprop="description"
    $description = null;
    if (preg_match($reItemprop, $html, $dm)) {
        $inner = $dm[3] ?? '';
        $inner = preg_replace('~<\s*br\s*/?\s*>~i', "\n", $inner); // BR → \n
        $inner = strip_tags($inner);
        $inner = html_entity_decode($inner, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $inner = str_replace("\r", '', $inner);
        $inner = preg_replace('/[ \t]+\n/', "\n", $inner);
        $inner = preg_replace("/\n{3,}/", "\n\n", $inner);
        $inner = trim($inner);
        if ($inner !== '') {
            $description = $inner;
        }
    }

    // Фиксируем запись (без внешних картинок категорий — их добавим после обхода всех файлов)
    $isCategory = isset($categorySlugsSet[$slug]);
    $entries[] = [
        'name'        => $name,
        'slug'        => $slug,
        'images'      => $images,
        'is_category' => $isCategory,
    ] + ($description !== null ? ['description' => $description] : []);

    /** Собираем внешние картинки категорий:
     *  Ищем все <a ... href="*.php">...</a>, внутри — <img ... src="...">,
     *  в карту $categoryImagesFromRefs[slugFromHref][] = src
     */
    if (preg_match_all($reATagBlock, $html, $aBlocks, PREG_SET_ORDER)) {
        foreach ($aBlocks as $aBlock) {
            $aAttr = $aBlock[1] ?? '';
            $aInner = $aBlock[2] ?? '';
            if ($aAttr === '') continue;

            $attrs = parseAttributes($aAttr, $reAttr);
            $href = $attrs['href'] ?? '';
            if ($href === '') continue;

            $href = html_entity_decode(trim($href), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // интересуют только ссылки на *.php — извлечём целевой slug
            $hrefPath = parse_url($href, PHP_URL_PATH) ?? $href;
            $hrefBase = basename($hrefPath);
            if ($hrefBase === '' || !str_ends_with_ci($hrefBase, '.php')) {
                continue;
            }
            $targetSlug = substr($hrefBase, 0, -4); // без .php

            // Ищем все <img ...> внутри этого <a>...</a>
            if (preg_match_all($reImgTag, $aInner, $imgMatches, PREG_SET_ORDER)) {
                foreach ($imgMatches as $img) {
                    $imgAttrStr = $img[1] ?? '';
                    if ($imgAttrStr === '') continue;

                    $iAttrs = parseAttributes($imgAttrStr, $reAttr);
                    $src = $iAttrs['src'] ?? '';
                    if ($src === '') continue;

                    $src = html_entity_decode(trim($src), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                    // Сохраняем в карту (потом добавим к категориям с таким слугом)
                    $categoryImagesFromRefs[$targetSlug][] = $src;
                }
            }
        }
    }
}

/** 3) После обхода — добавляем найденные внешние картинки к категориям */
if ($entries && $categoryImagesFromRefs) {
    // Уникализируем списки
    foreach ($categoryImagesFromRefs as $k => $arr) {
        $categoryImagesFromRefs[$k] = array_values(array_unique($arr));
    }

    foreach ($entries as &$e) {
        if (!empty($e['is_category']) && isset($categoryImagesFromRefs[$e['slug']])) {
            $merged = array_values(array_unique(array_merge($e['images'] ?? [], $categoryImagesFromRefs[$e['slug']])));
            $e['images'] = $merged;
        }
    }
    unset($e);
}

/** 4) Пишем YAML (UTF-8) */
$yaml = Yaml::dump($entries, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
file_put_contents($outPath, $yaml);

echo "✅ Готово. Элементов: " . count($entries) . "\n";
echo "📄 YAML: {$outPath}\n";

/* ================= helpers ================= */

function collectCategorySlugs($nodes): array {
    $out = [];
    if (!is_array($nodes)) return $out;

    $stack = isAssoc($nodes) ? [$nodes] : $nodes;
    while ($stack) {
        $node = array_pop($stack);
        if (!is_array($node)) continue;

        if (!empty($node['slug']) && is_string($node['slug'])) {
            $out[] = $node['slug'];
        }
        if (!empty($node['children']) && is_array($node['children'])) {
            foreach ($node['children'] as $ch) {
                $stack[] = $ch;
            }
        }
    }
    return array_values(array_unique(array_filter($out, fn($s) => $s !== '')));
}

function isAssoc(array $arr): bool {
    return array_keys($arr) !== range(0, count($arr) - 1);
}

function normalizeText(string $s): string {
    $s = strip_tags($s);
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $s = preg_replace('~\s+~u', ' ', $s);
    return trim($s);
}

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

function parseAttributes(string $attrStr, string $reAttr): array {
    $attrs = [];
    if (preg_match_all($reAttr, $attrStr, $am, PREG_SET_ORDER)) {
        foreach ($am as $a) {
            $attrName = strtolower($a[1]);
            $v2 = $a[2] ?? null; // "..."
            $v3 = $a[3] ?? null; // '...'
            $v4 = $a[4] ?? null; // bare
            if ($v2 !== null && $v2 !== '') {
                $attrValue = $v2;
            } elseif ($v3 !== null && $v3 !== '') {
                $attrValue = $v3;
            } elseif ($v4 !== null && $v4 !== '') {
                $attrValue = $v4;
            } else {
                $attrValue = '';
            }
            $attrs[$attrName] = $attrValue;
        }
    }
    return $attrs;
}

function str_ends_with_ci(string $haystack, string $needle): bool {
    $len = strlen($needle);
    if ($len === 0) return true;
    return strcasecmp(substr($haystack, -$len), $needle) === 0;
}
