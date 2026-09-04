<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark'=> ($appearance ?? 'light') == 'dark'])>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="X-Content-Type-Options" content="nosniff">
  <meta name="description" content="">
  <meta name="referrer" content="same-origin">
  <meta name="robots" content="index, follow">
  <link rel="icon" href="{{ asset('laravel-logo.min.svg') }}" type="image/svg+xml">
  <script>
      (function() {
          const appearance = '{{ $appearance ?? "system" }}';
          if (appearance === 'system') {
              const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
              if (prefersDark) {
                  document.documentElement.classList.add('dark');
              }
          }
      })();
  </script>
  @fonts
  @viteReactRefresh
  @vite(['src/app.tsx', "src/pages/{$page['component']}.tsx"])
  <x-inertia::head>
      <title>{{ $page['props']['title'] ?? config('app.name') }}</title>
  </x-inertia::head>
</head>

<body class="font-sans antialiased">
  <noscript>
    <div style="padding: 2rem; text-align: center; background-color: #fff; color: #000;" class="dark:bg-gray-900 dark:text-white">
      <h1>JavaScript es requerido</h1>
      <p>Esta aplicación requiere JavaScript para funcionar. Por favor, habilita JavaScript en tu navegador e intenta nuevamente.</p>
    </div>
  </noscript>
  <x-inertia::app />
</body>

</html>
