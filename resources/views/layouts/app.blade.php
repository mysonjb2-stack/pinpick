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
    <link rel="dns-prefetch" href="//www.googletagmanager.com">
    <link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v={{ filemtime(public_path('apple-touch-icon.png')) }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    @php $cssFile = file_exists(public_path('css/app.min.css')) ? 'css/app.min.css' : 'css/app.css'; @endphp
    <link rel="stylesheet" href="{{ asset($cssFile) }}?v={{ filemtime(public_path($cssFile)) }}">
    @stack('head')
    <script>if(new URLSearchParams(location.search).get('reset_guest')==='1'){localStorage.removeItem('pinpick_guest_places');alert('게스트 저장 데이터 초기화 완료');history.replaceState(null,'',location.pathname);}</script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','G-HQK3JQ1XEJ',{send_page_view:true});</script>
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

    @unless(View::hasSection('hide_nav'))
    @include('partials.nav')
    @endunless
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

    // GA 지연 로딩
    if (!window.__gaLoaded) {
        window.__gaLoaded = true;
        var gs = document.createElement('script');
        gs.src = 'https://www.googletagmanager.com/gtag/js?id=G-HQK3JQ1XEJ';
        gs.async = true;
        document.head.appendChild(gs);
    }
})();
</script>
<div class="pp-prompt-overlay" id="ppPromptOverlay" hidden>
    <div class="pp-prompt">
        <p class="pp-prompt__msg" id="ppPromptMsg"></p>
        <input class="pp-prompt__input" id="ppPromptInput" type="text" maxlength="30" autocomplete="off">
        <div class="pp-prompt__btns">
            <button type="button" class="pp-prompt__cancel" id="ppPromptCancel">취소</button>
            <button type="button" class="pp-prompt__ok" id="ppPromptOk">확인</button>
        </div>
    </div>
</div>
<script>
window.ppPrompt = function(msg) {
    return new Promise(function(resolve) {
        var ov = document.getElementById('ppPromptOverlay');
        var inp = document.getElementById('ppPromptInput');
        document.getElementById('ppPromptMsg').textContent = msg;
        inp.value = '';
        ov.hidden = false;
        setTimeout(function(){ inp.focus(); }, 50);
        function done(val) {
            ov.hidden = true;
            document.getElementById('ppPromptOk').removeEventListener('click', onOk);
            document.getElementById('ppPromptCancel').removeEventListener('click', onCancel);
            resolve(val);
        }
        function onOk() { done(inp.value); }
        function onCancel() { done(null); }
        document.getElementById('ppPromptOk').addEventListener('click', onOk);
        document.getElementById('ppPromptCancel').addEventListener('click', onCancel);
        inp.onkeydown = function(e) { if (e.key === 'Enter') onOk(); };
    });
};
</script>
</body>
</html>
