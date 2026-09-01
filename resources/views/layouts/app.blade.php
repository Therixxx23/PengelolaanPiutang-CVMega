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

                @if (session('success'))
                    <div class="mb-6 px-4 py-3 bg-status-paid/10 border border-status-paid rounded text-sm text-status-paid font-medium" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('warning'))
                    <div class="mb-6 px-4 py-3 bg-status-watch30/10 border border-status-watch30 rounded text-sm text-status-watch30 font-medium" role="alert">
                        {{ session('warning') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 px-4 py-3 bg-status-critical/10 border border-status-critical rounded text-sm text-status-critical font-medium" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>

        @stack('scripts')

        <script>
            (function() {
                const TIMEOUT = 7200000;
                let timer;

                function resetTimer() {
                    clearTimeout(timer);
                    timer = setTimeout(function() {
                        if (confirm(
                            'Sesi Anda akan berakhir karena tidak aktif. Klik OK untuk tetap login.'
                        )) {
                            fetch('{{ route("refresh-session") }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                            }).then(() => resetTimer());
                        } else {
                            window.location = '{{ route("logout") }}';
                        }
                    }, TIMEOUT - 300000);
                }

                ['mousemove', 'keypress', 'click', 'scroll', 'touchstart']
                    .forEach(e => document.addEventListener(e, resetTimer));

                resetTimer();
            })();
        </script>
    </body>
</html>
