<?php

namespace App\Support\ViewModels;

use Illuminate\Support\Facades\Cache;
use App\Models\Category;

class HomePageViewModel
{
    public function toArray(): array
    {
        return [
            // Для <x-layout>
            'title'       => 'Главная — KratonShop',

            // Для <x-hero>
            'hero'        => $this->hero(),

            // Для <x-header> / <x-nav> / сайд-меню
            'topMenu'     => $this->topMenu(),
            'sideMenu'    => $this->sideMenu(),

            // Любые блоки страницы
            'promoGrid'   => $this->promoGrid(),

            'rootCategories' => $this->rootCategories(),
        ];
    }

    protected function hero(): array
    {
        return [
            'title'    => 'Kraton Shop',
            'subtitle' => 'Оборудование и инструменты для профессионалов',
            'cta'      => 'Смотреть каталог',
        ];
    }

    protected function topMenu(): array
    {
        return Cache::remember('home:topMenu', 3600, function () {
            // TODO: подтянуть корневые категории/разделы из БД/YAML
            return [
                ['label' => 'О компании', 'route' => 'about'],
                ['label' => 'Оплата и доставка', 'route' => 'delivery'],
                ['label' => 'Отзывы', 'route' => 'reviews'],
                ['label' => 'Контакты', 'route' => 'contacts'],
                ['label' => 'Реквизиты', 'route' => 'legal'],
            ];
        });
    }

    protected function sideMenu(): array
    {
        return Cache::remember('home:sideMenu', 3600, function () {
            // TODO: дерево категорий для мобильного дровера
            return [
                ['label' => 'Категория 1', 'url' => '/c/1'],
                ['label' => 'Категория 2', 'url' => '/c/2'],
                ['label' => 'Категория 3', 'url' => '/c/3'],
            ];
        });
    }

    protected function promoGrid(): array
    {
        return [
            // любые карточки/пустышки на первом этапе
            ['title' => 'Промо 1', 'url' => '#'],
            ['title' => 'Промо 2', 'url' => '#'],
            ['title' => 'Промо 3', 'url' => '#'],
            ['title' => 'Промо 4', 'url' => '#'],
        ];
    }

    protected function rootCategories()
    {
        return Cache::remember('home:rootCategories:v1', 3600, function () {
            return Category::query()
                ->where('parent_id', -1)   // только корневые
                ->where('is_active', true)
                ->orderBy('order')         // порядок братьев
                ->get([
                    'id',
                    'name',
                    'slug',
                    'img',
                    'order',
                    'is_active',
                ]);
        });
    }
}
