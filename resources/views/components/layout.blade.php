@props([
  'title' => 'Kraton Shop',
  // прокидываем в header
  'topMenu' => [],
  'sideMenu' => [],
  'rootCategories' => [],
])

<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title }}</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  {{ $head ?? '' }}
  @stack('head')
  @fluxAppearance
</head>
<body class="min-h-full bg-white text-gray-900 antialiased">
  <div class="flex min-h-screen flex-col">
    <x-header :topMenu="$topMenu" :sideMenu="$sideMenu" :rootCategories="$rootCategories"/>
    <main id="content" class="flex-1">
      {{ $slot }}
    </main>
    <x-footer/>
  </div>
  @fluxScripts
  @stack('scripts')
</body>
</html>
