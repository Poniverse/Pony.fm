<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#84528A">
    <link rel="icon" href="/favicon.ico">
    <link rel="manifest" href="/manifest.json">
    {!! $meta ?? '' !!}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('pfm-theme');
                if (t !== 'light' && t !== 'dark') {
                    t = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
                }
                document.documentElement.dataset.theme = t;
            } catch (e) {
                document.documentElement.dataset.theme = 'dark';
            }
        })();
    </script>
    @if (config('app.debug'))
    <script>
        // Debugbar defaults: docked top-right and closed. Its own settings
        // menu persists to the same keys, so this only seeds first visits.
        try {
            if (!localStorage.getItem('phpdebugbar-settings')) {
                localStorage.setItem('phpdebugbar-settings', '{"openBtnPosition":"topRight","toolbarPosition":"top"}');
            }
            if (!localStorage.getItem('phpdebugbar-open')) {
                localStorage.setItem('phpdebugbar-open', '0');
            }
        } catch (e) {}
    </script>
    @endif
    @inertiaHead
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
</head>
<body>
    @inertia
</body>
</html>
