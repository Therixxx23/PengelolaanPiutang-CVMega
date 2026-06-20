<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sistem Piutang') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600|ibm-plex-sans:500,600|ibm-plex-mono:400,500&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="flex min-h-screen">
            @include('layouts.sidebar')

            <main class="flex-1 p-8">
                @isset($header)
                    <h1 class="font-display text-2xl font-semibold text-ink mb-8">
                        {{ $header }}
                    </h1>
                @endisset

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
