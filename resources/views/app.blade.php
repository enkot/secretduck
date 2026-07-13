<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @isset($metaRobots)
            <meta name="robots" content="{{ $metaRobots }}">
        @endisset

        <meta name="description" content="Create private invitations with playful challenges, share one link, and reveal the details only after guests solve them.">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'SecretDuck') }}">
        <meta property="og:title" content="{{ config('app.name', 'SecretDuck') }} — Playful private invitations">
        <meta property="og:description" content="Create private invitations with playful challenges, share one link, and reveal the details only after guests solve them.">
        <meta property="og:url" content="{{ request()->url() }}">
        <meta property="og:image" content="{{ asset('logo.png') }}">
        <meta property="og:image:type" content="image/png">
        <meta property="og:image:width" content="1549">
        <meta property="og:image:height" content="1549">
        <meta property="og:image:alt" content="{{ config('app.name', 'SecretDuck') }} duck logo">

        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ config('app.name', 'SecretDuck') }} — Playful private invitations">
        <meta name="twitter:description" content="Create private invitations with playful challenges, share one link, and reveal the details only after guests solve them.">
        <meta name="twitter:image" content="{{ asset('logo.png') }}">
        <meta name="twitter:image:alt" content="{{ config('app.name', 'SecretDuck') }} duck logo">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
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

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #fcfcfb;
            }

            html.dark {
                background-color: #15131c;
            }
        </style>

        <link rel="icon" href="/logo.png" type="image/png">
        <link rel="apple-touch-icon" href="/logo.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'SecretDuck') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
