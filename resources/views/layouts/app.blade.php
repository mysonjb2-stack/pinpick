<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#ffa51a">
    <meta name="naver-site-verification" content="8fc3a513aca43a1416d55813adba83f8baf6a28a" />
    <meta name="google-site-verification" content="2mqKDLK12Nw5E2pimrdTIryzolgiC_A-icMVU0FZSzk" />
    <title>@yield('page_title', '나만의 장소, 나만의 지도 | 핀픽')</title>
    <meta name="description" content="@yield('meta_description', '맛집, 카페, 여행지, 가고싶은 곳까지 — 내 장소를 모두 저장하고 쉽게 꺼내쓰는 나만의 지도앱, 핀픽. 로그인 없이 바로 시작하세요.')">
    @if(View::yieldContent('noindex'))
    <meta name="robots" content="noindex">
    @endif
    @php $canonicalUrl = View::yieldContent('canonical', url()->current()); @endphp
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="핀픽">
    <meta property="og:title" content="@yield('og_title', '나만의 장소, 나만의 지도 | 핀픽')">
    <meta property="og:description" content="@yield('og_description', '가고싶은 곳을 핀으로 저장하고 쉽게 꺼내쓰는 나만의 지도')">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="@yield('og_image', asset('images/og-image.png'))">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}" sizes="any">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icon-192.png') }}?v={{ filemtime(public_path('icon-192.png')) }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('icon-512.png') }}?v={{ filemtime(public_path('icon-512.png')) }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v={{ filemtime(public_path('apple-touch-icon.png')) }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @stack('head')
    <script>if(new URLSearchParams(location.search).get('reset_guest')==='1'){localStorage.removeItem('pinpick_guest_places');alert('게스트 저장 데이터 초기화 완료');history.replaceState(null,'',location.pathname);}</script>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-HQK3JQ1XEJ"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','G-HQK3JQ1XEJ');</script>
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
    var pageTime = Date.now();
    var STALE_MS = 30 * 60 * 1000;
    window.addEventListener('pageshow', function(e){
        if (e.persisted && (Date.now() - pageTime > STALE_MS)) {
            window.location.reload();
        }
    });
    var hiddenAt = null;
    document.addEventListener('visibilitychange', function(){
        if (document.visibilityState === 'hidden') {
            hiddenAt = Date.now();
        } else if (document.visibilityState === 'visible' && hiddenAt) {
            var idle = Date.now() - hiddenAt;
            hiddenAt = null;
            if (idle > STALE_MS) { window.location.reload(); }
        }
    });

    // 터치 시작 시 링크 프리페치 — 탭→이동 사이 ~100ms 동안 미리 로드
    var prefetched = {};
    document.addEventListener('touchstart', function(e) {
        var a = e.target.closest('a[href]');
        if (!a) return;
        var url = a.href;
        if (!url || url === location.href || prefetched[url]) return;
        if (url.indexOf(location.origin) !== 0) return;
        prefetched[url] = true;
        var link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        document.head.appendChild(link);
    }, { passive: true });
})();
</script>
</body>
</html>
