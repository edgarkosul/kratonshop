<?php

namespace App\Support\ViewModels;

use Illuminate\Support\Facades\Cache;

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
                ['label' => 'Категория 1', 'url' => '/c/1'],
                ['label' => 'Категория 2', 'url' => '/c/2'],
                ['label' => 'Категория 3', 'url' => '/c/3'],
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
}
