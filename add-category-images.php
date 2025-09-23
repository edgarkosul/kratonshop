#!/usr/bin/env php
<?php
/**
 * add-category-images.php
 *
 * Берёт картинки категорий из titles.yaml и добавляет поле `img`
 * ко всем узлам в categories.yaml (рекурсивно).
 *
 * Пример:
 *   php add-category-images.php \
 *     --categories=database/seeders/data/categories.yaml \
 *     --titles=database/seeders/data/titles.yaml \
 *     --out=database/seeders/data/categories.yaml
 *
 * Требует: composer require symfony/yaml
 */

use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/vendor/autoload.php';

$options = getopt('', ['categories:', 'titles:', 'out:']);
if (empty($options['categories']) || empty($options['titles']) || empty($options['out'])) {
    fwrite(STDERR, "Usage: php add-category-images.php --categories=PATH --titles=PATH --out=PATH\n");
    exit(1);
}

$categoriesPath = $options['categories'];
$titlesPath     = $options['titles'];
$outPath        = $options['out'];

if (!is_file($categoriesPath)) {
    fwrite(STDERR, "❌ categories file not found: {$categoriesPath}\n");
    exit(1);
}
if (!is_file($titlesPath)) {
    fwrite(STDERR, "❌ titles file not found: {$titlesPath}\n");
    exit(1);
}

try {
    $categories = Yaml::parseFile($categoriesPath);
} catch (Throwable $e) {
    fwrite(STDERR, "❌ Failed to parse categories.yaml: {$e->getMessage()}\n");
    exit(1);
}

try {
    $titles = Yaml::parseFile($titlesPath);
} catch (Throwable $e) {
    fwrite(STDERR, "❌ Failed to parse titles.yaml: {$e->getMessage()}\n");
    exit(1);
}

/**
 * Строим карту slug => img (берём ПЕРВУЮ непустую картинку)
 * Только для записей, где is_category === true.
 */
$slugToImg = [];
if (is_array($titles)) {
    foreach ($titles as $row) {
        if (!is_array($row)) continue;
        $slug = $row['slug'] ?? null;
        if (!is_string($slug) || $slug === '') continue;

        $isCategory = $row['is_category'] ?? null;
        if ($isCategory !== true && $isCategory !== 1 && $isCategory !== '1') {
            continue; // интересуют только категории
        }

        $img = '';
        if (!empty($row['images']) && is_array($row['images'])) {
            foreach ($row['images'] as $src) {
                if (is_string($src)) {
                    $src = trim($src);
                    if ($src !== '') { $img = $src; break; }
                }
            }
        }
        // фиксируем (даже если пусто — дальше подставим "")
        if (!array_key_exists($slug, $slugToImg)) {
            $slugToImg[$slug] = $img; // может быть ""
        }
    }
}

/**
 * Рекурсивно добавляем img к каждому узлу категорий.
 * Стараемся сохранить порядок ключей: name, slug, img, (прочее...), children.
 */
$updated = addImgRecursive($categories, $slugToImg);

$yaml = Yaml::dump($updated, 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
if (false === file_put_contents($outPath, $yaml)) {
    fwrite(STDERR, "❌ Failed to write: {$outPath}\n");
    exit(1);
}

echo "✅ Done. Saved with img fields → {$outPath}\n";


/* ================= helpers ================= */

/**
 * @param mixed $node
 * @param array $map slug => img
 * @return mixed
 */
function addImgRecursive($node, array $map) {
    if (is_array($node)) {
        // Ветка: массив-список категорий
        if (array_keys($node) === range(0, count($node) - 1)) {
            $out = [];
            foreach ($node as $item) {
                $out[] = addImgRecursive($item, $map);
            }
            return $out;
        }

        // Ветка: один узел категории (ассоциативный массив)
        $name     = $node['name']     ?? null;
        $slug     = $node['slug']     ?? null;
        $children = $node['children'] ?? null;

        // подберём картинку
        $img = '';
        if (is_string($slug) && $slug !== '' && isset($map[$slug])) {
            $img = (string) $map[$slug]; // может быть пустой строкой
        }

        // Соберём узел, сохраняя логичный порядок
        $new = [];
        if ($name !== null) $new['name'] = $name;
        if ($slug !== null) $new['slug'] = $slug;

        // гарантированно добавляем img ко ВСЕМ категориям
        $new['img'] = $img;

        // перенесём все промежуточные поля (если они есть, кроме children)
        foreach ($node as $k => $v) {
            if ($k === 'name' || $k === 'slug' || $k === 'children' || $k === 'img') continue;
            $new[$k] = $v;
        }

        // рекурсивно обработаем children
        if (is_array($children)) {
            $new['children'] = addImgRecursive($children, $map);
        }

        return $new;
    }

    // не массив — возвращаем как есть
    return $node;
}
