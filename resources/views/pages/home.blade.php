<x-layout :title="$title" :topMenu="$topMenu" :sideMenu="$sideMenu" :rootCategories="$rootCategories">
  <x-hero
    :title="$hero['title']"
    :subtitle="$hero['subtitle']"
    :cta="$hero['cta']"
  />

  <section class="mx-auto max-w-7xl px-4 py-10">
    <h2 class="text-xl font-semibold">Промоблок / контент</h2>
    <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
      @foreach($promoGrid as $item)
        <a href="{{ $item['url'] }}" class="h-24 rounded-lg border flex items-center justify-center text-sm">
          {{ $item['title'] }}
        </a>
      @endforeach
    </div>
  </section>
</x-layout>

