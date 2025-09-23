@props(['class' => ''])

<form role="search" aria-label="Поиск по каталогу товаров" class="relative {{ $class }}">
    <label for="q" class="sr-only">Поиск по каталогу товаров</label>
    <input id="q" name="q" type="search" placeholder="Поиск по каталогу товаров"
        class="w-full rounded-md border border-gray-300 pl-3 pr-10 h-10 outline-none focus:ring-2 focus:ring-brand-500">
    <button type="submit"
        class="absolute inset-y-0 right-0 w-10 grid place-items-center rounded-r-md bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
        aria-label="Найти" title="Найти">
        <!-- SVG лупы -->
        <x-heroicon-o-magnifying-glass class="h-5" />
    </button>
</form>
