<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="{{ config('app.theme_color') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('app.short_name') }}">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<script data-navigate-once>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
    }
</script>
