<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class CategoryYamlSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('database/seeders/data/categories.yaml');
        if (! file_exists($path)) {
            $this->command?->warn("Файл categories.yaml не найден");
            return;
        }

        $data = Yaml::parseFile($path);

        if (isset($data['name']) && ! isset($data[0])) {
            $data = [$data];
        }

        Category::truncate();

        DB::transaction(function () use ($data) {
            foreach ($data as $rootNode) {
                $this->importNode($rootNode, -1);
            }
        });
    }

    protected function importNode(array $node, int $parentId): Category
    {
        $name = $node['name'] ?? 'Без имени';
        $slug = $node['slug'] ?? Str::slug($name, '-');

        // вычисляем следующий свободный order
        $nextOrder = (int) Category::where('parent_id', $parentId)->max('order');
        $nextOrder = $nextOrder ? $nextOrder + 1 : 0;

        $category = Category::create([
            'parent_id' => $parentId,
            'name'      => $name,
            'slug'      => $slug,
            'img'       => $node['img'] ?? null,
            'is_active' => (bool)($node['is_active'] ?? true),
            'order'     => $nextOrder,
            'meta_json' => $node['meta_json'] ?? null,
        ]);

        foreach ($node['children'] ?? [] as $childNode) {
            $this->importNode($childNode, $category->id);
        }

        return $category;
    }
}
