@extends('layouts.app')
@section('title', $place->name)
@section('app_class', 'pp-app--detail')

@section('header')
<header class="pp-header">
    <button class="pp-header__icon" onclick="ppShowBack()" aria-label="뒤로">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </button>
    <div class="pp-header__title">장소 상세</div>
    <div class="pp-header__spacer"></div>
    <div class="pp-header__more" id="ppMoreWrap">
        <button type="button" class="pp-header__icon" id="ppMoreBtn" aria-label="더보기" aria-haspopup="menu" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
        </button>
        <div class="pp-header__menu" id="ppMoreMenu" role="menu" hidden>
            <a href="{{ route('places.edit', $place) }}" class="pp-header__menu-item" role="menuitem">수정</a>
            <form method="POST" action="{{ route('places.destroy', $place) }}" onsubmit="return confirm('삭제할까요?')" class="pp-header__del-form">
                @csrf @method('DELETE')
                <button type="submit" class="pp-header__menu-item pp-header__menu-item--del" role="menuitem">삭제</button>
            </form>
        </div>
    </div>
</header>
@endsection

@section('content')
<div style="padding:16px">
    {{-- 이미지 갤러리 (사용자 업로드 우선, 없으면 지도 썸네일) --}}
    @if($place->images->count())
    <div class="pp-show-images" id="ppShowImages">
        @foreach($place->images as $i => $img)
        <div class="pp-show-images__item" data-lb-idx="{{ $i }}" data-lb-src="{{ $img->url }}">
            <img src="{{ $img->url }}" alt="{{ $place->name }}" loading="lazy">
        </div>
        @endforeach
    </div>
    @elseif($place->thumbnail)
    <div class="pp-show-images">
        <div class="pp-show-images__item">
            <img src="{{ asset('storage/' . $place->thumbnail) }}" alt="{{ $place->name }}">
        </div>
    </div>
    @endif

    <div class="pp-card">
        <div class="pp-card__top">
            <div class="pp-card__icon">{{ $place->category?->icon ?? '📌' }}</div>
            <div class="pp-card__body">
                <div class="pp-card__name">{{ $place->name }}</div>
                <div class="pp-card__meta">
                    <span>{{ $place->category?->name ?? '기타' }}</span>
                    @if($place->themes->isNotEmpty())
                        <span class="pp-meta-dot" aria-hidden="true"></span>
                        @foreach($place->themes as $theme)
                            <span class="pp-theme-badge">{{ $theme->name }}</span>
                        @endforeach
                    @endif
                </div>
            </div>
            <span class="pp-badge pp-badge--{{ $place->status }}">{{ $place->status === 'visited' ? '방문완료' : '방문예정' }}</span>
        </div>
        @if($place->road_address || $place->address)
            <div class="pp-info-row">
                <svg class="pp-info-row__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>{{ $place->road_address ?: $place->address }}</span>
            </div>
        @endif
        @if($place->phone)
            <div class="pp-info-row pp-info-row--sub">
                <svg class="pp-info-row__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.35 1.85.59 2.81.72A2 2 0 0 1 22 16.92z"/></svg>
                <span>{{ $place->phone }}</span>
            </div>
        @endif
        @if($place->opening_hours)
            <div class="pp-hours">
                <button type="button" class="pp-hours__toggle pp-info-row pp-info-row--sub" id="ppHoursToggle">
                    <svg class="pp-info-row__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>영업시간</span>
                    <svg class="pp-hours__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="pp-hours__list" id="ppHoursList" hidden>
                    @foreach($place->opening_hours as $line)
                        <div class="pp-hours__line">{{ $line }}</div>
                    @endforeach
                </div>
            </div>
        @endif
        @if($place->memo)
            <div style="margin-top:12px;padding:12px;background:var(--pp-bg-soft);border-radius:10px;font-size:13.5px">{{ $place->memo }}</div>
        @endif
        @if($place->visited_at)
            <div style="margin-top:8px;font-size:12px;color:var(--pp-text-sub)">방문일: {{ $place->visited_at->format('Y.m.d') }}</div>
        @endif
    </div>

    @php
        $hasCoord = $place->lat && $place->lng;
    @endphp

    {{-- 위치 (지도 + 길찾기 + 주소복사) --}}
    @if($hasCoord)
    @php $addrText = $place->road_address ?: ($place->address ?: ''); @endphp
    <section class="pp-loc">
        <div class="pp-loc__head">
            <div class="pp-loc__title">위치</div>
        </div>
        @if($addrText)
        <div class="pp-loc__addr">{{ $addrText }}</div>
        @endif
        <div class="pp-loc__map-wrap">
            <div class="pp-loc__map" id="ppLocMap"
                 data-lat="{{ $place->lat }}"
                 data-lng="{{ $place->lng }}"
                 data-name="{{ $place->name }}"
                 data-overseas="{{ $place->is_overseas ? '1' : '0' }}">
                @if($place->thumbnail)
                    <img src="{{ asset('storage/' . $place->thumbnail) }}" alt="{{ $place->name }} 위치" class="pp-loc__map-fallback">
                @endif
            </div>
            @if($place->is_overseas)
            <a href="https://www.google.com/maps/search/?api=1&query={{ $place->lat }},{{ $place->lng }}" target="_blank" rel="noopener" class="pp-loc__glink">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z" fill="#4285F4"/></svg>
                Google 지도에서 보기
            </a>
            @endif
        </div>
        <div class="pp-dirs {{ $place->is_overseas ? 'pp-dirs--solo' : '' }}">
            @if($place->is_overseas)
                <button type="button" onclick="ppOpenRoute('google', {{ $place->lat }}, {{ $place->lng }}, @js($place->name))" class="pp-dirs__btn">
                    <span class="pp-dirs__ico pp-dirs__ico--google">G</span>
                    <span class="pp-dirs__lab">구글지도 길찾기</span>
                </button>
            @else
                <button type="button" onclick="ppOpenRoute('naver', {{ $place->lat }}, {{ $place->lng }}, @js($place->name))" class="pp-dirs__btn">
                    <span class="pp-dirs__ico pp-dirs__ico--naver">N</span>
                    <span class="pp-dirs__lab">네이버지도 길찾기</span>
                </button>
                <button type="button" onclick="ppOpenRoute('kakao', {{ $place->lat }}, {{ $place->lng }}, @js($place->name))" class="pp-dirs__btn">
                    <span class="pp-dirs__ico pp-dirs__ico--kakao">K</span>
                    <span class="pp-dirs__lab">카카오맵 길찾기</span>
                </button>
            @endif
        </div>
        @if($addrText)
        <button type="button" class="pp-loc__copy" data-addr="{{ $addrText }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
            <span>주소복사</span>
        </button>
        @endif
    </section>
    @endif

    {{-- 순서 변경 --}}
    @if($place->category_id)
    <div class="pp-reorder" id="reorderBox">
        <div class="pp-reorder__label">카테고리 내 순서</div>
        <div class="pp-reorder__btns">
            <button type="button" class="pp-reorder__btn" id="reorderUp" aria-label="위로 올리기">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="m18 15-6-6-6 6"/></svg>
                위로 올리기
            </button>
            <button type="button" class="pp-reorder__btn" id="reorderDown" aria-label="아래로 내리기">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="m6 9 6 6 6-6"/></svg>
                아래로 내리기
            </button>
        </div>
    </div>
    @endif

</div>

@php
    $phoneTel = $place->phone ? preg_replace('/[^0-9+]/', '', $place->phone) : '';

    // 예약 폴백 허용 테마 슬러그 (naver_place_id 없을 때 "네이버 예약" 검색 딥링크 노출)
    $naverFallbackThemes = ['food', 'beauty', 'stay', 'medical'];
    $placeThemeSlugs = $place->themes->pluck('slug')->all();
    $isNaverFallbackTheme = count(array_intersect($naverFallbackThemes, $placeThemeSlugs)) > 0;

    // 버튼 노출 결정
    $showBookBtn = false;
    $bookDirect = false;   // true = place_id 있어 직접 예약 페이지로, false = 검색 딥링크 폴백
    $bookProvider = null;
    $bookUrl = null;
    $bookLabel = null;

    if (!$place->is_overseas) {
        if ($place->naver_place_id) {
            $showBookBtn = true;
            $bookDirect = true;
            $bookProvider = 'naver';
            $bookUrl = 'https://m.place.naver.com/place/' . $place->naver_place_id . '/booking?entry=plt';
            $bookLabel = '네이버 예약';
        } elseif ($isNaverFallbackTheme) {
            // 예약 가능성 있는 테마에만 폴백 노출
            $showBookBtn = true;
            $bookDirect = false;
            $bookProvider = 'naver';
            $q = $place->name . ($place->road_address ? ' ' . $place->road_address : '');
            // placePath=/booking → 검색 결과에서 업체 진입 시 예약 탭으로 바로 연결
            $bookUrl = 'https://map.naver.com/p/search/' . urlencode($q) . '?placePath=' . urlencode('/booking');
            $bookLabel = '네이버 예약';
        }
    } else {
        if ($place->google_place_id) {
            $showBookBtn = true;
            $bookDirect = true;
            $bookProvider = 'google';
            $bookUrl = 'https://www.google.com/maps/place/?q=place_id:' . $place->google_place_id;
            $bookLabel = '구글 맵에서 예약';
        } elseif ($isNaverFallbackTheme) {
            $showBookBtn = true;
            $bookDirect = false;
            $bookProvider = 'google';
            $q = $place->name . ($place->road_address ? ', ' . $place->road_address : '');
            $bookUrl = 'https://www.google.com/maps/search/' . urlencode($q);
            $bookLabel = '구글 맵에서 찾기';
        }
    }
@endphp

@if($showBookBtn || $phoneTel)
<div class="pp-detail-cta">
    @if($showBookBtn && !$bookDirect)
        <div class="pp-cta-tip">네이버 예약을 사용중인 상점만 가능</div>
    @endif
    <div class="pp-cta-row">
        @if($showBookBtn)
            <a href="{{ $bookUrl }}"
               target="_blank" rel="noopener noreferrer"
               class="pp-btn pp-btn--block pp-btn--book {{ $bookDirect ? 'pp-btn--book-direct' : 'pp-btn--book-find' }}">
                @if($bookProvider === 'naver')
                    <span class="pp-btn__ico pp-btn__ico--naver" aria-hidden="true">N</span>
                @else
                    <span class="pp-btn__ico pp-btn__ico--google" aria-hidden="true">G</span>
                @endif
                {{ $bookLabel }}
            </a>
        @endif
        @if($phoneTel)
            <a href="tel:{{ $phoneTel }}" class="pp-btn pp-btn--call">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.35 1.85.59 2.81.72A2 2 0 0 1 22 16.92z"/></svg>
                전화 문의
            </a>
        @endif
    </div>
</div>
@endif

@if($place->images->count())
<div class="pp-lb" id="ppLb" hidden aria-hidden="true" role="dialog" aria-label="이미지 갤러리">
    <button type="button" class="pp-lb__close" aria-label="닫기">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" width="22" height="22"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>
    <div class="pp-lb__counter" id="ppLbCounter">1 / {{ $place->images->count() }}</div>
    <button type="button" class="pp-lb__nav pp-lb__nav--prev" aria-label="이전">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="26" height="26"><path d="m15 18-6-6 6-6"/></svg>
    </button>
    <button type="button" class="pp-lb__nav pp-lb__nav--next" aria-label="다음">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="26" height="26"><path d="m9 18 6-6-6-6"/></svg>
    </button>
    <div class="pp-lb__stage" id="ppLbStage">
        <img class="pp-lb__img" id="ppLbImg" alt="">
    </div>
</div>
@endif

@push('scripts')
@if($hasCoord ?? false)
    @if($place->is_overseas)
        @if($googleMapsKey ?? null)
        <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&callback=ppInitLocMap" async defer></script>
        @endif
    @else
        @if($naverClientId ?? null)
        <script src="https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId={{ $naverClientId }}"></script>
        @endif
    @endif
@endif
<script>
// 헤더 더보기 메뉴 (수정/삭제)
(function(){
    const wrap = document.getElementById('ppMoreWrap');
    const btn = document.getElementById('ppMoreBtn');
    const menu = document.getElementById('ppMoreMenu');
    if (!wrap || !btn || !menu) return;
    function close(){ menu.hidden = true; btn.setAttribute('aria-expanded', 'false'); }
    function toggle(e){
        e.stopPropagation();
        const open = menu.hidden;
        menu.hidden = !open;
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    btn.addEventListener('click', toggle);
    document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
})();
function ppShowBack(){
    var r = document.referrer || '';
    if (/\/places\/\d+\/edit(\?|#|\/|$)/.test(r)) {
        location.href = '{{ route('home') }}';
    } else {
        history.back();
    }
}
// 길찾기 (내 위치 → 목적지). 모바일: 앱스킴 → 1초 내 앱 미실행 시 웹 폴백. 데스크톱: 바로 웹.
window.ppOpenRoute = function(provider, lat, lng, name) {
    const ua = navigator.userAgent || '';
    const isMobile = /iPhone|iPad|iPod|Android/i.test(ua);
    const encName = encodeURIComponent(name);
    let webUrl, appUrl;

    if (provider === 'kakao') {
        appUrl = `kakaomap://route?ep=${lat},${lng}&by=CAR`;
        webUrl = `https://map.kakao.com/link/to/${encName},${lat},${lng}`;
    } else if (provider === 'google') {
        appUrl = `comgooglemaps://?daddr=${lat},${lng}&directionsmode=driving`;
        webUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&destination_place_id=${encName}`;
    } else {
        appUrl = `nmap://route/car?dlat=${lat}&dlng=${lng}&dname=${encName}&appname=net.mypinpick`;
        webUrl = `https://map.naver.com/p/directions/-/${lng},${lat},${encName},,PLACE_POI/-/car`;
    }

    if (isMobile) {
        let t = Date.now();
        window.location.href = appUrl;
        setTimeout(() => {
            if (Date.now() - t < 1600 && !document.hidden) window.open(webUrl, '_blank');
        }, 1200);
    } else {
        window.open(webUrl, '_blank');
    }
};

// 위치 지도 초기화
(function(){
    const el = document.getElementById('ppLocMap');
    if (!el) return;
    const lat = parseFloat(el.dataset.lat);
    const lng = parseFloat(el.dataset.lng);
    const name = el.dataset.name || '';
    const overseas = el.dataset.overseas === '1';
    if (!lat || !lng) return;

    function initNaver(){
        if (typeof naver === 'undefined' || !naver.maps) return;
        const pos = new naver.maps.LatLng(lat, lng);
        const map = new naver.maps.Map(el, {
            center: pos, zoom: 16, minZoom: 10,
            draggable: true, pinchZoom: true, scrollWheel: false, disableDoubleTapZoom: false,
            mapTypeControl: false, zoomControl: false, logoControlOptions: { position: naver.maps.Position.BOTTOM_LEFT }
        });
        new naver.maps.Marker({ position: pos, map: map, title: name });
    }
    function initGoogle(){
        if (typeof google === 'undefined' || !google.maps) return;
        const pos = { lat, lng };
        const map = new google.maps.Map(el, {
            center: pos, zoom: 16,
            disableDefaultUI: true, zoomControl: false, gestureHandling: 'cooperative'
        });
        new google.maps.Marker({ position: pos, map, title: name });
    }

    if (overseas) {
        window.ppInitLocMap = initGoogle;
        if (typeof google !== 'undefined' && google.maps) initGoogle();
    } else {
        if (typeof naver !== 'undefined' && naver.maps) initNaver();
        else window.addEventListener('load', initNaver, { once: true });
    }
})();

// 영업시간 토글
(function(){
    const btn = document.getElementById('ppHoursToggle');
    const list = document.getElementById('ppHoursList');
    if (!btn || !list) return;
    btn.addEventListener('click', () => {
        const open = list.hidden;
        list.hidden = !open;
        btn.classList.toggle('is-open', open);
    });
})();

// 주소 복사
document.querySelectorAll('.pp-loc__copy').forEach(btn => {
    btn.addEventListener('click', async () => {
        const addr = btn.dataset.addr || '';
        if (!addr) return;
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(addr);
            } else {
                const ta = document.createElement('textarea');
                ta.value = addr; ta.style.position='fixed'; ta.style.opacity='0';
                document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove();
            }
            const lab = btn.querySelector('span');
            const old = lab.textContent;
            lab.textContent = '복사됨';
            btn.classList.add('is-done');
            setTimeout(() => { lab.textContent = old; btn.classList.remove('is-done'); }, 1500);
        } catch (e) { alert('복사에 실패했어요'); }
    });
});

// 이미지 라이트박스
(function() {
    const lb = document.getElementById('ppLb');
    if (!lb) return;
    const items = Array.from(document.querySelectorAll('#ppShowImages .pp-show-images__item'));
    if (!items.length) return;

    const imgEl = document.getElementById('ppLbImg');
    const counterEl = document.getElementById('ppLbCounter');
    const prevBtn = lb.querySelector('.pp-lb__nav--prev');
    const nextBtn = lb.querySelector('.pp-lb__nav--next');
    const closeBtn = lb.querySelector('.pp-lb__close');
    const stage = document.getElementById('ppLbStage');
    const total = items.length;
    let idx = 0;

    function render() {
        const src = items[idx].dataset.lbSrc;
        imgEl.src = src;
        counterEl.textContent = `${idx + 1} / ${total}`;
        prevBtn.style.visibility = total > 1 ? 'visible' : 'hidden';
        nextBtn.style.visibility = total > 1 ? 'visible' : 'hidden';
        counterEl.style.display = total > 1 ? '' : 'none';
    }
    function open(i) {
        idx = i;
        render();
        lb.hidden = false;
        lb.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    function close() {
        lb.hidden = true;
        lb.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        imgEl.src = '';
    }
    function prev() { idx = (idx - 1 + total) % total; render(); }
    function next() { idx = (idx + 1) % total; render(); }

    items.forEach((el, i) => {
        el.style.cursor = 'zoom-in';
        el.addEventListener('click', () => open(i));
    });
    closeBtn.addEventListener('click', close);
    prevBtn.addEventListener('click', prev);
    nextBtn.addEventListener('click', next);
    lb.addEventListener('click', (e) => { if (e.target === lb || e.target === stage) close(); });
    document.addEventListener('keydown', (e) => {
        if (lb.hidden) return;
        if (e.key === 'Escape') close();
        else if (e.key === 'ArrowLeft') prev();
        else if (e.key === 'ArrowRight') next();
    });

    // 터치 스와이프 (좌/우 네비, 아래로 당기면 닫기)
    let tsX = 0, tsY = 0, tsTime = 0;
    stage.addEventListener('touchstart', (e) => {
        const t = e.changedTouches[0];
        tsX = t.clientX; tsY = t.clientY; tsTime = Date.now();
    }, { passive: true });
    stage.addEventListener('touchend', (e) => {
        const t = e.changedTouches[0];
        const dx = t.clientX - tsX;
        const dy = t.clientY - tsY;
        const dt = Date.now() - tsTime;
        if (dt > 600) return;
        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
            dx < 0 ? next() : prev();
        } else if (dy > 80 && Math.abs(dy) > Math.abs(dx)) {
            close();
        }
    }, { passive: true });
})();

(function() {
    const csrf = '{{ csrf_token() }}';
    const placeId = {{ $place->id }};
    const upBtn = document.getElementById('reorderUp');
    const downBtn = document.getElementById('reorderDown');
    if (!upBtn || !downBtn) return;

    async function reorder(direction) {
        const btn = direction === 'up' ? upBtn : downBtn;
        btn.disabled = true;
        try {
            const r = await fetch(`/api/places/${placeId}/reorder`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ direction })
            });
            const j = await r.json();
            if (j.ok) {
                const label = document.querySelector('.pp-reorder__label');
                label.textContent = direction === 'up' ? '위로 이동했어요' : '아래로 이동했어요';
                label.style.color = '#2f6fed';
                setTimeout(() => { label.textContent = '카테고리 내 순서'; label.style.color = ''; }, 1500);
            } else if (j.error === 'already_at_edge') {
                alert(direction === 'up' ? '이미 맨 위입니다.' : '이미 맨 아래입니다.');
            }
        } catch (e) { alert('네트워크 오류'); }
        btn.disabled = false;
    }

    upBtn.addEventListener('click', () => reorder('up'));
    downBtn.addEventListener('click', () => reorder('down'));
})();
</script>
@endpush
@endsection
