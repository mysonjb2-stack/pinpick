<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#ffa51a">
    <title>@yield('title', '핀픽') · 핀픽</title>
    <meta name="description" content="내가 저장한 장소를 빠르게 꺼내 쓰는 나만의 지도">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icon-192.png') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('icon-512.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @stack('head')
</head>
<body>
<div class="pp-app @yield('app_class')">
    @hasSection('header')
        @yield('header')
    @endif

    @if(session('success'))
        <div class="pp-flash" data-autodismiss>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="pp-flash pp-flash--error" data-autodismiss>{{ session('error') }}</div>
    @endif
    <script>
        document.querySelectorAll('.pp-flash[data-autodismiss]').forEach(el => {
            setTimeout(() => {
                el.style.transition = 'opacity .4s, transform .4s';
                el.style.opacity = '0';
                el.style.transform = 'translateY(-6px)';
                setTimeout(() => el.remove(), 450);
            }, 3000);
        });
    </script>

    @yield('content')

    @include('partials.nav')
</div>
@stack('scripts')
<script>
(function(){
    window.addEventListener('pageshow', function(e){
        if (e.persisted) { window.location.reload(); }
    });
    var hiddenAt = null;
    var STALE_MS = 30 * 60 * 1000;
    document.addEventListener('visibilitychange', function(){
        if (document.visibilityState === 'hidden') {
            hiddenAt = Date.now();
        } else if (document.visibilityState === 'visible' && hiddenAt) {
            var idle = Date.now() - hiddenAt;
            hiddenAt = null;
            if (idle > STALE_MS) { window.location.reload(); }
        }
    });
})();
</script>
</body>
</html>
