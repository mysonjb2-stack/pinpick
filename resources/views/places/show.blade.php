@extends('layouts.app')
@section('page_title', $place->name . ' | 핀픽')
@section('noindex', true)
@section('app_class', 'pp-app--detail')

@section('header')
<header class="pp-header">
    <button class="pp-header__icon pp-header__back" onclick="ppShowBack()" aria-label="뒤로">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </button>
    <div class="pp-header__title">장소 상세</div>
    <div class="pp-header__spacer"></div>
    <button type="button" class="pp-header__icon" id="ppDetailShareBtn" aria-label="공유">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
    </button>
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
    {{-- 이미지 갤러리 (사용자 업로드 우선, 없으면 지도 썸네일 + 빠른 추가) --}}
    @if($place->images->count())
    <div class="pp-show-images" id="ppShowImages">
        @foreach($place->images as $i => $img)
        <div class="pp-show-images__item" data-lb-idx="{{ $i }}" data-lb-src="{{ $img->url }}">
            <img src="{{ $img->url }}" alt="{{ $place->name }}" {!! $i === 0 ? 'fetchpriority="high" decoding="async"' : 'loading="lazy" decoding="async"' !!}>
        </div>
        @endforeach
        @if($place->images->count() < 5)
        <form class="pp-show-images__add" action="{{ route('api.places.quick-images', $place) }}" method="POST" enctype="multipart/form-data" id="ppQuickImgForm">
            @csrf
            <label class="pp-show-images__add-btn" aria-label="이미지 추가">
                <input type="file" name="images[]" multiple accept="image/*" hidden id="ppQuickImgInput">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" width="24" height="24"><path d="M12 5v14M5 12h14"/></svg>
                <span class="pp-show-images__add-label">사진 등록</span>
            </label>
        </form>
        @endif
    </div>
    @elseif($place->thumbnail)
    <div class="pp-show-images pp-show-images--with-add">
        <div class="pp-show-images__item">
            <img src="{{ asset('storage/' . $place->thumbnail) }}" alt="{{ $place->name }}">
        </div>
        <form class="pp-show-images__add" action="{{ route('api.places.quick-images', $place) }}" method="POST" enctype="multipart/form-data" id="ppQuickImgForm">
            @csrf
            <label class="pp-show-images__add-btn" aria-label="이미지 추가">
                <input type="file" name="images[]" multiple accept="image/*" hidden id="ppQuickImgInput">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" width="24" height="24"><path d="M12 5v14M5 12h14"/></svg>
                <span class="pp-show-images__add-label">사진 등록</span>
            </label>
        </form>
    </div>
    @else
    <div class="pp-show-images pp-show-images--empty">
        <form class="pp-show-images__add pp-show-images__add--solo" action="{{ route('api.places.quick-images', $place) }}" method="POST" enctype="multipart/form-data" id="ppQuickImgForm">
            @csrf
            <label class="pp-show-images__add-btn" aria-label="이미지 추가">
                <input type="file" name="images[]" multiple accept="image/*" hidden id="ppQuickImgInput">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" width="28" height="28"><path d="M12 5v14M5 12h14"/></svg>
                <span class="pp-show-images__add-label">사진 추가</span>
            </label>
        </form>
    </div>
    @endif

    <div class="pp-card">
        <div class="pp-card__top">
            <div class="pp-card__icon">{{ $place->category?->icon ?? '📌' }}</div>
            <div class="pp-card__body">
                <div class="pp-card__name">{{ $place->name }}</div>
                <div class="pp-card__meta">
                    <span>@if($place->category?->color)<span class="pp-cat-dot" style="background:{{ $place->category->color }}"></span>@endif{{ $place->category?->name ?? '' }}</span>
                    @if($place->themes->isNotEmpty())
                        <span class="pp-meta-dot" aria-hidden="true"></span>
                        @foreach($place->themes as $theme)
                            <span class="pp-theme-badge">{{ $theme->name }}</span>
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="pp-card__status-wrap">
                <button type="button" class="pp-status-toggle" id="ppStatusToggle" data-place-id="{{ $place->id }}" data-status="{{ $place->status }}" data-visited-at="{{ $place->visited_at?->format('Y-m-d') ?? '' }}">
                    <span class="pp-status-pop__dot pp-status-pop__dot--{{ $place->status }}"></span>
                    <span id="ppStatusLabel">{{ $place->status === 'visited' ? '방문완료' : '방문예정' }}</span>
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                @if($place->status === 'visited' && $place->visited_at)
                    <div class="pp-card__status-date" id="ppStatusDate">방문일: {{ $place->visited_at->format('Y.m.d') }}</div>
                @elseif($place->status === 'planned')
                    <div class="pp-card__status-date" id="ppStatusDate">등록일: {{ $place->created_at->format('Y.m.d') }}</div>
                @endif
            </div>
        </div>
        @if($place->original_name && $place->original_name !== $place->name)
            <div class="pp-info-row pp-info-row--original">
                <svg class="pp-info-row__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M12 3v12"/><path d="m8 11 4 4 4-4"/></svg>
                <span>{{ $place->original_name }}</span>
            </div>
        @endif
        @if($place->road_address || $place->address)
            @php
                $displayAddr = $place->road_address ?: $place->address;
                $showBuilding = $place->building_name
                    && $place->building_name !== $place->name
                    && !str_contains($displayAddr, $place->building_name);
            @endphp
            <div class="pp-info-row">
                <svg class="pp-info-row__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>{{ $displayAddr }}@if($showBuilding) {{ $place->building_name }}@endif</span>
            </div>
            @if($place->detail_location)
                <div class="pp-info-row pp-info-row--sub pp-info-row--detail-loc">
                    <svg class="pp-info-row__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                    <span>{{ $place->detail_location }}</span>
                </div>
            @endif
            @if($place->address && $place->road_address)
                <div class="pp-info-row pp-info-row--sub pp-info-row--jibun">
                    <span class="pp-jibun-label">지번</span>
                    <span>{{ $place->address }}</span>
                </div>
            @endif
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
                    <span class="pp-dirs__lab">네이버 길찾기</span>
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
    $naverFallbackThemes = ['food', 'beauty', 'stay', 'medical', 'cafe'];
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
            $bookUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($place->name ?: ($place->lat . ',' . $place->lng)) . '&query_place_id=' . $place->google_place_id;
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
// 빠른 이미지 추가 (파일 선택 시 자동 업로드)
(function(){
    const input = document.getElementById('ppQuickImgInput');
    const form = document.getElementById('ppQuickImgForm');
    if (!input || !form) return;
    input.addEventListener('change', function(){
        if (!this.files.length) return;
        const btn = form.querySelector('.pp-show-images__add-btn');
        btn.classList.add('is-uploading');
        form.submit();
    });
})();

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

    if (provider === 'google') {
        webUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;
        window.open(webUrl, '_blank');
        return;
    } else if (provider === 'kakao') {
        appUrl = `kakaomap://route?ep=${lat},${lng}&by=CAR&dname=${encName}`;
        webUrl = `https://map.kakao.com/link/to/${encName},${lat},${lng}`;
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
            mapTypeControl: false, zoomControl: false, scaleControl: false, mapDataControl: false, logoControl: false
        });
        new naver.maps.Marker({ position: pos, map: map, title: name,
            icon: { content: '<div style="width:28px;height:28px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;border:2px solid #FF6B00;box-shadow:0 2px 6px rgba(0,0,0,.2)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#C2410C" stroke-width="2.5" stroke-linecap="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg></div>', anchor: new naver.maps.Point(14, 14) }
        });
    }
    function initGoogle(){
        if (typeof google === 'undefined' || !google.maps) return;
        const pos = { lat, lng };
        const map = new google.maps.Map(el, {
            center: pos, zoom: 16,
            disableDefaultUI: true, zoomControl: false, gestureHandling: 'greedy'
        });
        new google.maps.Marker({ position: pos, map, title: name,
            icon: { url: 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28"><circle cx="14" cy="14" r="12" fill="#fff" stroke="#FF6B00" stroke-width="2"/><g transform="translate(7,7)" fill="none" stroke="#C2410C" stroke-width="2" stroke-linecap="round"><path d="M7 1C4.79 1 3 2.79 3 5c0 3 4 7.5 4 7.5S11 8 11 5c0-2.21-1.79-4-4-4z"/><circle cx="7" cy="5" r="1.5"/></g></svg>'), scaledSize: new google.maps.Size(28, 28), anchor: new google.maps.Point(14, 14) }
        });
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
    document.addEventListener('keydown', (e) => {
        if (lb.hidden) return;
        if (e.key === 'Escape') close();
        else if (e.key === 'ArrowLeft') prev();
        else if (e.key === 'ArrowRight') next();
    });

    // 통합 제스처: 핀치줌 · 패닝 · 스와이프 · 더블탭 · 배경탭닫기
    let sc = 1, tx = 0, ty = 0;
    const SC_MIN = 1, SC_MAX = 5;
    let mode = 'idle'; // idle | pinch | pan | swipe
    let pinchDist0 = 0, pinchSc0 = 1, pinchMid0 = null, pinchTx0 = 0, pinchTy0 = 0;
    let panX0 = 0, panY0 = 0, panTx0 = 0, panTy0 = 0;
    let tapX = 0, tapY = 0, tapTime = 0, lastTapTime = 0;
    let moved = false;

    function apply() {
        imgEl.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(' + sc + ')';
    }
    function resetZoom() { sc = 1; tx = 0; ty = 0; imgEl.style.transform = ''; }
    function animateZoom(toSc, toTx, toTy) {
        imgEl.style.transition = 'transform .25s ease-out';
        sc = toSc; tx = toTx; ty = toTy; apply();
        setTimeout(() => { imgEl.style.transition = 'none'; }, 260);
    }
    const origRender = render;
    render = function() { resetZoom(); origRender(); };

    function dist(a, b) { return Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY); }
    function mid(a, b) { return { x: (a.clientX + b.clientX) / 2, y: (a.clientY + b.clientY) / 2 }; }

    stage.addEventListener('touchstart', function(e) {
        var t = e.touches;
        if (t.length === 2) {
            mode = 'pinch';
            pinchDist0 = dist(t[0], t[1]);
            pinchSc0 = sc;
            pinchMid0 = mid(t[0], t[1]);
            pinchTx0 = tx; pinchTy0 = ty;
        } else if (t.length === 1 && mode !== 'pinch') {
            mode = sc > 1 ? 'pan' : 'swipe';
            moved = false;
            var p = t[0];
            tapX = p.clientX; tapY = p.clientY; tapTime = Date.now();
            panX0 = p.clientX; panY0 = p.clientY;
            panTx0 = tx; panTy0 = ty;
        }
    }, { passive: true });

    stage.addEventListener('touchmove', function(e) {
        var t = e.touches;
        if (mode === 'pinch' && t.length >= 2) {
            e.preventDefault();
            var d = dist(t[0], t[1]);
            var ns = Math.min(SC_MAX, Math.max(SC_MIN, pinchSc0 * (d / pinchDist0)));
            var m = mid(t[0], t[1]);
            var r = ns / pinchSc0;
            tx = m.x - r * (pinchMid0.x - pinchTx0);
            ty = m.y - r * (pinchMid0.y - pinchTy0);
            sc = ns;
            apply();
            moved = true;
        } else if (mode === 'pan' && t.length === 1) {
            e.preventDefault();
            var p = t[0];
            tx = panTx0 + (p.clientX - panX0);
            ty = panTy0 + (p.clientY - panY0);
            apply();
            moved = true;
        } else if (mode === 'swipe' && t.length === 1) {
            moved = true;
        }
    }, { passive: false });

    stage.addEventListener('touchend', function(e) {
        if (e.touches.length > 0) {
            if (mode === 'pinch' && e.touches.length === 1) {
                var p = e.touches[0];
                panX0 = p.clientX; panY0 = p.clientY;
                panTx0 = tx; panTy0 = ty;
                mode = 'pan';
            }
            return;
        }
        var prevMode = mode;
        mode = 'idle';

        if (prevMode === 'pinch') {
            if (sc <= 1.05) animateZoom(1, 0, 0);
            return;
        }

        var p = e.changedTouches[0];
        var dx = p.clientX - tapX, dy = p.clientY - tapY;
        var dt = Date.now() - tapTime;

        // 더블탭
        if (!moved && dt < 250 && (Date.now() - lastTapTime) < 300) {
            lastTapTime = 0;
            if (sc > 1) {
                animateZoom(1, 0, 0);
            } else {
                var rect = imgEl.getBoundingClientRect();
                var ns = 2.5;
                var ox = p.clientX - rect.left, oy = p.clientY - rect.top;
                animateZoom(ns, tx - ox * (ns - 1), ty - oy * (ns - 1));
            }
            return;
        }
        lastTapTime = (!moved && dt < 250) ? Date.now() : 0;

        if (prevMode === 'pan') {
            if (sc <= 1.05) animateZoom(1, 0, 0);
            return;
        }

        // 스와이프 (1x일 때만)
        if (dt < 400 && sc <= 1) {
            if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
                dx < 0 ? next() : prev(); return;
            }
            if (dy > 70 && Math.abs(dy) > Math.abs(dx)) {
                close(); return;
            }
        }

        // 배경 탭 닫기
        if (!moved && dt < 250 && (e.target === stage || e.target === imgEl && sc <= 1)) {
            // 더블탭 대기 중이면 닫지 않음
            if (!lastTapTime) close();
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

// ── 상태 원탭 전환 ──
(function(){
    const toggle = document.getElementById('ppStatusToggle');
    if (!toggle) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const placeId = toggle.dataset.placeId;
    const label = document.getElementById('ppStatusLabel');
    let dateEl = document.getElementById('ppStatusDate');
    let activePop = null;

    function closePop() { if (activePop) { activePop.remove(); activePop = null; } }
    function fmtDate(d) { return d.replace(/-/g, '.'); }
    function fmtDateKo(d) { const p = d.split('-'); return parseInt(p[1]) + '월 ' + parseInt(p[2]) + '일'; }

    function buildPopHtml(curStatus, visitedAt) {
        if (curStatus === 'planned') {
            return `
                <button type="button" class="pp-status-pop__item is-current" data-action="noop">
                    <span class="pp-status-pop__dot pp-status-pop__dot--planned"></span>방문예정
                    <span class="pp-status-pop__cur">(현재)</span>
                </button>
                <button type="button" class="pp-status-pop__item" data-action="visit-today">
                    <span class="pp-status-pop__dot pp-status-pop__dot--visited"></span>방문완료로 변경
                </button>
                <button type="button" class="pp-status-pop__item" data-action="visit-pick">
                    <span class="pp-status-pop__ico">📅</span>다른 날짜로 완료 기록
                </button>`;
        }
        return `
            <button type="button" class="pp-status-pop__item is-current" data-action="noop">
                <span class="pp-status-pop__dot pp-status-pop__dot--visited"></span>
                <span>방문완료 <span class="pp-status-pop__cur">(현재)</span>${visitedAt ? '<span class="pp-status-pop__sub">' + fmtDate(visitedAt) + '</span>' : ''}</span>
            </button>
            <button type="button" class="pp-status-pop__item" data-action="visit-pick">
                <span class="pp-status-pop__ico">📅</span>방문 날짜 변경
            </button>
            <button type="button" class="pp-status-pop__item" data-action="revert">
                <span class="pp-status-pop__dot pp-status-pop__dot--planned"></span>방문예정으로 되돌리기
            </button>`;
    }

    function openDatePicker(cb) {
        const inp = document.createElement('input');
        inp.type = 'date';
        inp.max = new Date().toISOString().slice(0, 10);
        inp.style.cssText = 'position:fixed;left:-9999px;top:50%;opacity:0';
        document.body.appendChild(inp);
        inp.addEventListener('change', () => { cb(inp.value); inp.remove(); });
        inp.addEventListener('blur', () => setTimeout(() => inp.remove(), 300));
        inp.showPicker ? inp.showPicker() : inp.click();
    }

    function updateUI(status, visitedAt) {
        toggle.dataset.status = status;
        toggle.dataset.visitedAt = visitedAt || '';
        label.textContent = status === 'visited' ? '방문완료' : '방문예정';
        const dot = toggle.querySelector('.pp-status-pop__dot');
        dot.className = 'pp-status-pop__dot pp-status-pop__dot--' + status;
        const wrap = toggle.closest('.pp-card__status-wrap');
        if (status === 'visited' && visitedAt) {
            if (!dateEl && wrap) {
                dateEl = document.createElement('div');
                dateEl.id = 'ppStatusDate';
                dateEl.className = 'pp-card__status-date';
                wrap.appendChild(dateEl);
            }
            if (dateEl) dateEl.textContent = '방문일: ' + fmtDate(visitedAt);
        } else if (status === 'planned') {
            if (dateEl) dateEl.textContent = '';
        }
    }

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        if (activePop) { closePop(); return; }
        const curStatus = toggle.dataset.status;
        const visitedAt = toggle.dataset.visitedAt || '';
        const pop = document.createElement('div');
        pop.className = 'pp-status-pop';
        pop.innerHTML = buildPopHtml(curStatus, visitedAt);
        const rect = toggle.getBoundingClientRect();
        pop.style.position = 'fixed';
        pop.style.top = (rect.bottom + 6) + 'px';
        pop.style.right = Math.max(8, window.innerWidth - rect.right) + 'px';
        pop.style.left = 'auto';
        document.body.appendChild(pop);
        requestAnimationFrame(() => pop.classList.add('is-show'));
        activePop = pop;

        pop.addEventListener('click', (ev) => {
            const item = ev.target.closest('.pp-status-pop__item');
            if (!item) return;
            const action = item.dataset.action;
            closePop();
            if (action === 'noop') return;
            const oldStatus = curStatus;
            const oldVisitedAt = visitedAt;
            if (action === 'visit-today') {
                const today = new Date().toISOString().slice(0, 10);
                applyStatus(today, oldStatus, oldVisitedAt);
            } else if (action === 'visit-pick') {
                openDatePicker((d) => { if (d) applyStatus(d, oldStatus, oldVisitedAt); });
            } else if (action === 'revert') {
                applyStatus(null, oldStatus, oldVisitedAt);
            }
        });
    });

    async function applyStatus(visitedAt, oldStatus, oldVisitedAt) {
        const newStatus = visitedAt ? 'visited' : 'planned';
        updateUI(newStatus, visitedAt);
        try {
            const r = await fetch(`/api/places/${placeId}/status`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ status: newStatus, visited_at: visitedAt || null })
            });
            const j = await r.json();
            if (!j.ok) throw new Error();
            if (newStatus === 'visited') {
                showStatusToast(fmtDateKo(visitedAt) + ' 방문완료로 기록했어요', oldStatus, oldVisitedAt, visitedAt);
            } else {
                showStatusToast('방문예정으로 되돌렸어요', oldStatus, oldVisitedAt);
            }
        } catch {
            updateUI(oldStatus, oldVisitedAt);
        }
    }

    function showStatusToast(msg, prevStatus, prevVisitedAt, newVisitedAt) {
        let t = document.getElementById('ppToast');
        if (!t) { t = document.createElement('div'); t.id = 'ppToast'; t.className = 'pp-toast'; document.body.appendChild(t); }
        const esc = s => String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        let actions = '';
        if (newVisitedAt) actions += '<button type="button" class="pp-toast__undo" data-role="change-date">날짜 변경</button>';
        actions += '<button type="button" class="pp-toast__undo" data-role="undo">되돌리기</button>';
        t.innerHTML = esc(msg) + actions;
        t.classList.add('is-show');
        let done = false;
        const timer = setTimeout(() => t.classList.remove('is-show'), 5000);

        const changeDateBtn = t.querySelector('[data-role="change-date"]');
        if (changeDateBtn) {
            changeDateBtn.addEventListener('click', () => {
                if (done) return; done = true;
                clearTimeout(timer);
                t.classList.remove('is-show');
                openDatePicker(async (d) => {
                    if (!d) return;
                    updateUI('visited', d);
                    try {
                        await fetch(`/api/places/${placeId}/status`, {
                            method: 'PATCH',
                            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ status: 'visited', visited_at: d })
                        });
                    } catch {}
                    showStatusToast(fmtDateKo(d) + '로 변경했어요', null, null);
                });
            }, { once: true });
        }

        t.querySelector('[data-role="undo"]').addEventListener('click', async () => {
            if (done) return; done = true;
            clearTimeout(timer);
            t.classList.remove('is-show');
            try {
                await fetch(`/api/places/${placeId}/status`, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ status: prevStatus, visited_at: prevVisitedAt || null })
                });
            } catch {}
            if (prevStatus) { updateUI(prevStatus, prevVisitedAt); }
            showToastSimple('되돌렸어요');
        }, { once: true });
    }

    function showToastSimple(msg) {
        let t = document.getElementById('ppToast');
        if (!t) { t = document.createElement('div'); t.id = 'ppToast'; t.className = 'pp-toast'; document.body.appendChild(t); }
        t.textContent = msg;
        t.classList.add('is-show');
        setTimeout(() => t.classList.remove('is-show'), 2500);
    }

    document.addEventListener('click', closePop);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePop(); });
})();

// ── 단일 장소 공유 ──
(function(){
    const btn = document.getElementById('ppDetailShareBtn');
    if (!btn) return;
    const placeId = {{ $place->id }};
    const placeName = @js($place->name);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const sheet = document.createElement('div');
    sheet.className = 'pp-share-sheet';
    sheet.id = 'ppShareSheet';
    sheet.innerHTML = `
        <div class="pp-share-sheet__backdrop" data-close-share></div>
        <div class="pp-share-sheet__panel">
            <div class="pp-share-sheet__handle"></div>
            <div class="pp-share-sheet__head">
                <h3 class="pp-share-sheet__title">장소 공유</h3>
                <button type="button" class="pp-share-sheet__close" data-close-share aria-label="닫기">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="pp-share-sheet__field">
                <label class="pp-share-sheet__label">공유 제목</label>
                <input type="text" class="pp-share-sheet__input" id="ppShareTitle" maxlength="100">
            </div>
            <div class="pp-share-sheet__field">
                <label class="pp-share-sheet__label">장소명 표시 방식</label>
                <label class="pp-share-sheet__radio">
                    <input type="radio" name="shareMode" value="original" checked>
                    <span>기본 장소명으로 보내기</span>
                </label>
                <label class="pp-share-sheet__radio">
                    <input type="radio" name="shareMode" value="custom">
                    <span>내가 지은 이름과 메모 포함</span>
                </label>
                <p class="pp-share-sheet__hint" id="ppShareModeHint" hidden>직접 입력한 장소명과 메모가 상대방에게 그대로 보여요</p>
            </div>
            <div class="pp-share-sheet__buttons">
                <button type="button" class="pp-btn pp-btn--kakao-share" id="ppShareKakao">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3C6.48 3 2 6.58 2 11c0 2.86 1.88 5.37 4.7 6.78-.2.74-.75 2.81-.86 3.25-.14.55.2.54.42.4.17-.12 2.7-1.84 3.79-2.58.64.1 1.3.15 1.95.15 5.52 0 10-3.58 10-8S17.52 3 12 3z"/></svg>
                    카카오톡으로 공유
                </button>
                <button type="button" class="pp-btn pp-btn--ghost" id="ppShareNative" hidden>다른 앱으로 공유</button>
                <button type="button" class="pp-btn pp-btn--ghost" id="ppShareCopyLink">링크 복사</button>
            </div>
        </div>`;
    document.body.appendChild(sheet);

    const titleInput = sheet.querySelector('#ppShareTitle');
    const modeHint = sheet.querySelector('#ppShareModeHint');

    sheet.querySelectorAll('input[name="shareMode"]').forEach(r => {
        r.addEventListener('change', () => { modeHint.hidden = r.value !== 'custom' || !r.checked; });
    });

    btn.addEventListener('click', () => {
        titleInput.value = placeName;
        sheet.classList.add('is-open');
    });

    sheet.querySelectorAll('[data-close-share]').forEach(el => {
        el.addEventListener('click', () => sheet.classList.remove('is-open'));
    });

    async function createShare() {
        const mode = sheet.querySelector('input[name="shareMode"]:checked')?.value || 'original';
        try {
            const res = await fetch('/api/share', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ place_ids: [placeId], title: titleInput.value.trim() || placeName, name_display_mode: mode }),
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                ppToast(err.message || '공유 생성에 실패했어요');
                return null;
            }
            return await res.json();
        } catch { ppToast('네트워크 오류가 발생했어요'); return null; }
    }

    const nativeBtn = sheet.querySelector('#ppShareNative');
    if (nativeBtn && typeof navigator.share === 'function') {
        nativeBtn.hidden = false;
        nativeBtn.addEventListener('click', async () => {
            const data = await createShare();
            if (!data) return;
            sheet.classList.remove('is-open');
            try {
                await navigator.share({ title: data.title, text: data.title + ' — 핀픽에서 확인하세요', url: data.url });
            } catch (e) { if (e.name !== 'AbortError') ppToast('공유에 실패했어요'); }
        });
    }

    sheet.querySelector('#ppShareCopyLink').addEventListener('click', async () => {
        const data = await createShare();
        if (!data) return;
        sheet.classList.remove('is-open');
        try {
            if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(data.url);
            else { const ta = document.createElement('textarea'); ta.value = data.url; ta.style.cssText = 'position:fixed;left:-9999px'; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); }
            ppToast('링크가 복사됐어요');
        } catch { ppToast('복사에 실패했어요'); }
    });

    sheet.querySelector('#ppShareKakao').addEventListener('click', async () => {
        const data = await createShare();
        if (!data) return;
        sheet.classList.remove('is-open');
        function send() {
            if (!Kakao.isInitialized()) Kakao.init('{{ config("services.kakao.js_key") }}');
            Kakao.Share.sendDefault({
                objectType: 'feed',
                content: {
                    title: data.title,
                    description: '장소 ' + data.place_count + '개 · 나만의 장소, 나만의 지도 핀픽',
                    imageUrl: data.thumbnail_url || '{{ asset("images/og-image.png") }}',
                    link: { mobileWebUrl: data.url, webUrl: data.url },
                },
                buttons: [{ title: '장소 확인하기', link: { mobileWebUrl: data.url, webUrl: data.url } }],
            });
        }
        if (typeof Kakao === 'undefined') {
            const s = document.createElement('script');
            s.src = 'https://t1.kakaocdn.net/kakao_js_sdk/2.7.4/kakao.min.js';
            s.onload = send;
            s.onerror = () => ppToast('카카오 SDK를 불러오지 못했어요');
            document.head.appendChild(s);
        } else { send(); }
    });

    function ppToast(msg) {
        let t = document.getElementById('ppToast');
        if (!t) { t = document.createElement('div'); t.id = 'ppToast'; t.className = 'pp-toast'; document.body.appendChild(t); }
        t.textContent = msg;
        t.classList.add('is-show');
        setTimeout(() => t.classList.remove('is-show'), 2500);
    }
})();

</script>
@endpush
@endsection
