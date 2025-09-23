@props(['items' => []])

<nav class="mx-auto max-w-7xl px-4">
  <ul class="flex gap-6 overflow-x-auto py-3 text-sm">
    @foreach($items as $item)
      <li><a href="{{ $item['url'] }}" class="hover:text-gray-700">{{ $item['label'] }}</a></li>
    @endforeach
  </ul>
</nav>
