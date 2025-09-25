<?php

namespace App\Livewire\Pages\Header;

use Livewire\Component;
use App\Models\Category;
use Illuminate\Support\Collection;

class HeaderBottom extends Component
{
    /** @var array<int,array> */
    public  array  $rootCategories;

    public bool $open = false;
    public ?int $activeId = null;

    /** @var array<int,array> */
    public array $panel = [];
    public bool $showRoots = false;

    public function mount(array $rootCategories): void
    {
        $this->rootCategories = $rootCategories;
    }

    public function toggleCatalog(): void
    {
        // Открываем панель со списком корневых категорий
        $this->open = ! $this->open;

        if ($this->open) {
            $this->showRoots = true;   // показать именно корни
            $this->activeId  = null;   // сброс выбора
            $this->panel     = [];     // чистим детей
        } else {
            $this->showRoots = false;
        }
    }
    public function select(int $id): void
    {
        // Если кликнули по той же категории, и панель открыта — закрываем
        if ($this->activeId === $id && $this->open && !$this->showRoots) {
            $this->open = ! $this->open;
            return;
        }

        // Устанавливаем активную категорию и сбрасываем "режим корней"
        $this->activeId  = $id;
        $this->showRoots = false;

        // Загружаем подкатегории
        $this->panel = Category::query()
            ->where('parent_id', $id)
            ->orderBy('order')
            ->get(['id', 'name', 'slug', 'img'])
            ->toArray();

        // Когда данные готовы — открываем панель
        $this->open = true;
    }


    public function close(): void
    {
        $this->open = false;
    }


    public function render()
    {
        return view('livewire.pages.header.header-bottom');
    }
}
