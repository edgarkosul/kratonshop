<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Yaml\Yaml;

class CategoryYamlSeeder extends Seeder
{
    public function run(): void
    {
        $yamlPath = base_path('database/seeders/data/categories.yaml');
        if (! file_exists($yamlPath)) {
            $this->command?->warn("Файл {$yamlPath} не найден — пропускаю.");
            return;
        }

        $data = \Symfony\Component\Yaml\Yaml::parseFile($yamlPath);

        // Если YAML начинается с одного корня — оборачиваем в массив
        if (isset($data['name']) && ! isset($data[0])) {
            $data = [$data];
        }

        DB::transaction(function () use ($data) {
            $roots = [];

            // Импортируем КОРНИ в том порядке, как в YAML
            foreach ($data as $rootNode) {
                $roots[] = $this->importNode($rootNode, null);
            }

            // === РАССТАНОВКА ПОРЯДКА ДЛЯ КОРНЕЙ ===
            if (count($roots) > 0) {
                // Первый корень делаем самым левым (если он не первый)
                $firstCurrent = \App\Models\Category::whereIsRoot()->defaultOrder()->first();
                if ($firstCurrent && $firstCurrent->id !== $roots[0]->id) {
                    $roots[0]->beforeNode($firstCurrent)->save();
                }
                // sort = 0 у первого
                if ($roots[0]->sort !== 0) {
                    $roots[0]->sort = 0;
                    $roots[0]->save();
                }

                // Остальных ставим строго после предыдущего + sort = индекс
                $prev = $roots[0];
                foreach (array_slice($roots, 1) as $i => $root) {
                    $root->afterNode($prev)->save();
                    $pos = $i + 1; // 1,2,3...
                    if ($root->sort !== $pos) {
                        $root->sort = $pos;
                        $root->save();
                    }
                    $prev = $root;
                }
            }
        });

        // При желании:
        // \App\Models\Category::fixTree();
    }


    /**
     * Импорт одного узла и рекурсивно его детей.
     *
     * @param array $node ['name','slug','img','children'=>[...]]
     * @param Category|null $parent
     * @return Category
     */
    protected function importNode(array $node, ?Category $parent): Category
    {
        $name = $node['name'] ?? 'Без имени';
        $slug = $node['slug'] ?? Str::slug($name, '-');

        // Ищем по (parent_id, slug)
        $query = Category::query()
            ->when(
                $parent,
                fn($q) => $q->where('parent_id', $parent->id),
                fn($q) => $q->whereNull('parent_id')
            )
            ->where('slug', $slug);

        $category = $query->first();

        if (! $category) {
            $category = new Category([
                'name' => $name,
                'slug' => $slug,
                'img'  => $node['img'] ?? null,
                'is_active' => (bool)($node['is_active'] ?? true),
                // sort выставим ниже при расстановке детей родителя
            ]);

            if ($parent) {
                // Временно кладём в конец — позже переупорядочим
                $category->appendToNode($parent)->save();
            } else {
                $category->saveAsRoot();
            }
        } else {
            $category->fill([
                'name' => $name,
                'img'  => $node['img'] ?? $category->img,
                'is_active' => array_key_exists('is_active', $node) ? (bool)$node['is_active'] : $category->is_active,
            ]);

            // Если сменился «родительский» контекст — переместим под нужного
            if ($parent && $category->parent_id !== $parent->id) {
                $category->appendToNode($parent);
            }
            if (! $parent && $category->parent_id !== null) {
                $category->makeRoot();
            }

            $category->save();
        }

        // Рекурсивно создаём/обновляем детей
        $children = $node['children'] ?? [];
        $createdChildren = [];
        foreach ($children as $childNode) {
            $createdChildren[] = $this->importNode($childNode, $category);
        }

        // === КЛЮЧ: жёстко расставляем порядок как в YAML ===
        // Первый ребёнок — в самое начало, остальные — строго после предыдущего
        $prev = null;
        foreach (array_values($createdChildren) as $i => $childCat) {
            // поддержим колонку sort в БД, чтобы в Filament было видно порядок
            if ($childCat->sort !== $i) {
                $childCat->sort = $i;
            }

            if ($prev === null) {
                // делаем первым среди братьев
                $childCat->prependToNode($category);
            } else {
                // ставим сразу ПОСЛЕ предыдущего брата
                $childCat->afterNode($prev);
            }

            // save() обязателен после prepend/after
            $childCat->save();
            $prev = $childCat;
        }

        return $category;
    }
}
