@extends('layouts.app')
@section('title', $place->name . ' - 핀픽')
@section('app_class', 'pp-app--pub')

@section('content')
@php
    $categoryName = $place->category?->name ?? '';
    $regionText = '';
    if ($place->is_overseas) {
        $regionText = $topRegion?->name ?? '';
    } else {
        $addr = $place->road_address ?: ($place->address ?: '');
        $parts = array_slice(explode(' ', $addr), 0, 3);
        $regionText = trim(implode(' ', $parts));
    }
    $shareUrl = route('public.place.show', $place);
@endphp

<div class="pp-pub">
    {{-- Top Nav --}}
    <div class="pp-pub__top">
        <button type="button" class="pp-pub__back" onclick="history.length > 1 ? history.back() : location.href='/'" aria-label="뒤로">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><polyline points="15 18 9 12 15 6"/></svg>
            <span>장소</span>
        </button>
        <div class="pp-pub__spacer"></div>
        <button type="button" class="pp-pub__top-action" id="ppPubShareTop" aria-label="공유">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
        </button>
        <button type="button" class="pp-pub__top-action" aria-label="더보기">
            <svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>
        </button>
    </div>

    {{-- 1+2. Hero + Basic Info (한 카드) --}}
    <section class="pp-pub__card pp-pub__card--hero">
        <div class="pp-pub__hero">
            @if($heroImage)
                <img src="{{ $heroImage }}" alt="{{ $place->name }}" class="pp-pub__hero-img">
            @else
                <div class="pp-pub__hero-ph">{{ $place->category?->icon ?? '📍' }}</div>
            @endif
            @if($weeklySaves > 0)
                <span class="pp-pub__badge pp-pub__badge--week">이번주 {{ number_format($weeklySaves) }} 저장</span>
            @endif
            @if($isHot)
                <span class="pp-pub__badge pp-pub__badge--hot">요즘 많이 저장</span>
            @endif
        </div>
        <div class="pp-pub__card-body">
            <h1 class="pp-pub__name">{{ $place->name }}</h1>
            <div class="pp-pub__subline">
                @if($categoryName)<span class="pp-pub__cat">{{ $categoryName }}</span>@endif
                @if($regionText)<span class="pp-pub__sep">·</span><span class="pp-pub__addr">{{ $regionText }}</span>@endif
            </div>
            <div class="pp-pub__actions">
                <button type="button" class="pp-pub__btn pp-pub__btn--outline" id="ppPubShare">공유</button>
                <button type="button" class="pp-pub__btn pp-pub__btn--dark pp-pub__btn--save" id="ppPubSaveTop">내 장소에 저장하기</button>
            </div>
        </div>
    </section>

    {{-- 3. 왜 많이 저장됐을까요? --}}
    @if(count($insights))
    <section class="pp-pub__card">
        <h2 class="pp-pub__section-title">왜 많이 저장됐을까요?</h2>
        <div class="pp-pub__insights">
            @foreach($insights as $ins)
                <div class="pp-pub__insight">
                    <div class="pp-pub__insight-title">{{ $ins['title'] }}</div>
                    <div class="pp-pub__insight-desc">{{ $ins['desc'] }}</div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 4. 저장 데이터 요약 --}}
    <section class="pp-pub__card">
        <h2 class="pp-pub__section-title">저장 데이터 요약</h2>
        <div class="pp-pub__stats">
            <div class="pp-pub__stat">
                <div class="pp-pub__stat-v">{{ number_format($weeklySaves) }}</div>
                <div class="pp-pub__stat-l">이번주 저장 수</div>
            </div>
            <div class="pp-pub__stat">
                @if($categoryRank)
                    <div class="pp-pub__stat-v">#{{ $categoryRank }}</div>
                    <div class="pp-pub__stat-l">요즘 많이 찾는 {{ $categoryName ?: '장소' }}</div>
                @else
                    <div class="pp-pub__stat-v">{{ number_format($totalSaves) }}</div>
                    <div class="pp-pub__stat-l">전체 저장 수</div>
                @endif
            </div>
            <div class="pp-pub__stat">
                <div class="pp-pub__stat-v pp-pub__stat-v--sm">{{ $topRegion?->name ?? '-' }}</div>
                <div class="pp-pub__stat-l">저장이 많은 지역</div>
            </div>
            <div class="pp-pub__stat">
                <div class="pp-pub__stat-v pp-pub__stat-v--sm">{{ $topTheme?->name ?? '-' }}</div>
                <div class="pp-pub__stat-l">가장 많이 저장된 테마</div>
            </div>
        </div>
    </section>

    {{-- 5. 테마 분포 --}}
    @if($themes->isNotEmpty())
    <section class="pp-pub__card">
        <h2 class="pp-pub__section-title">저장한 사람들의 테마</h2>
        <div class="pp-pub__themes">
            @foreach($themes as $t)
                <div class="pp-pub__theme-row">
                    <div class="pp-pub__theme-head">
                        <span class="pp-pub__theme-name">{{ $t->name }}</span>
                        <span class="pp-pub__theme-pct">{{ round($t->ratio * 100) }}%</span>
                    </div>
                    <div class="pp-pub__theme-bar">
                        <div class="pp-pub__theme-bar-fill" style="width: {{ round($t->ratio * 100) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 6. 대표 메모 --}}
    @if($memos->isNotEmpty())
    <section class="pp-pub__card">
        <h2 class="pp-pub__section-title">저장한 사람들의 메모</h2>
        <div class="pp-pub__memos">
            @foreach($memos as $memo)
                <div class="pp-pub__memo">{{ $memo }}</div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 8. 위치 --}}
    @if($place->lat && $place->lng)
    <section class="pp-pub__card">
        <h2 class="pp-pub__section-title">위치</h2>
        <div class="pp-pub__map" id="ppPubMap"
             data-lat="{{ $place->lat }}"
             data-lng="{{ $place->lng }}"
             data-name="{{ $place->name }}"
             data-overseas="{{ $place->is_overseas ? '1' : '0' }}">
            @if($place->thumbnail)
                <img src="{{ asset('storage/' . $place->thumbnail) }}" alt="" class="pp-pub__map-fallback">
            @endif
        </div>
        @if($place->road_address || $place->address)
        <div class="pp-pub__map-addr">{{ $place->road_address ?: $place->address }}</div>
        @endif
        <div class="pp-pub__map-actions">
            <button type="button" class="pp-pub__btn pp-pub__btn--outline" id="ppPubOpenMap">지도 열기</button>
            <button type="button" class="pp-pub__btn pp-pub__btn--dark" id="ppPubRoute">길찾기</button>
        </div>
    </section>
    @endif

    {{-- 7. 비슷하게 많이 저장한 장소 --}}
    @if($similarPlaces->isNotEmpty())
    <section class="pp-pub__card">
        <h2 class="pp-pub__section-title">비슷하게 많이 저장한 장소</h2>
        <div class="pp-pub__similar">
            @foreach($similarPlaces as $sp)
                @php
                    $spThumb = $sp->images->first()?->thumb_url ?: ($sp->thumbnail ? asset('storage/' . $sp->thumbnail) : null);
                    $spAddr = $sp->road_address ?: ($sp->address ?: '');
                    $spRegionParts = array_slice(explode(' ', $spAddr), 0, 2);
                    $spRegion = trim(implode(' ', $spRegionParts));
                @endphp
                <a href="{{ route('public.place.show', $sp) }}" class="pp-pub__sim-card">
                    <div class="pp-pub__sim-thumb"@if($spThumb) style="background-image:url('{{ $spThumb }}')"@endif>
                        @unless($spThumb)<span>{{ $sp->category?->icon ?? '📍' }}</span>@endunless
                    </div>
                    <div class="pp-pub__sim-name">{{ $sp->name }}</div>
                    <div class="pp-pub__sim-meta">{{ $sp->category?->name ?? '기타' }}@if($spRegion) · {{ $spRegion }}@endif</div>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 9. 하단 CTA --}}
    <div class="pp-pub__cta">
        <div class="pp-pub__cta-text">사람들이 많이 저장한 장소를 내 카테고리에 바로 담아보세요</div>
        <div class="pp-pub__cta-actions">
            <button type="button" class="pp-pub__btn pp-pub__btn--outline" id="ppPubPickCat">카테고리 선택</button>
            <button type="button" class="pp-pub__btn pp-pub__btn--dark" id="ppPubSaveBottom">내 장소에 저장</button>
        </div>
    </div>
</div>

{{-- 카테고리 선택 바텀시트 --}}
<div class="pp-pub-sheet" id="ppPubSheet" hidden aria-hidden="true">
    <div class="pp-pub-sheet__backdrop" id="ppPubSheetBack"></div>
    <div class="pp-pub-sheet__body">
        <div class="pp-pub-sheet__handle"></div>
        <div class="pp-pub-sheet__title">저장할 카테고리를 선택하세요</div>
        <div class="pp-pub-sheet__list" id="ppPubSheetList">
            <div class="pp-pub-sheet__loading">불러오는 중…</div>
        </div>
    </div>
</div>
@endsection

@push('head')
@if($place->is_overseas)
    @if($googleMapsKey)
        <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&callback=ppPubInitMap" async defer></script>
    @endif
@else
    @if($naverClientId)
        <script src="https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId={{ $naverClientId }}"></script>
    @endif
@endif
@endpush

@push('scripts')
@php
    $placeJs = [
        'id' => $place->id,
        'name' => $place->name,
        'lat' => (float) $place->lat,
        'lng' => (float) $place->lng,
        'is_overseas' => (bool) $place->is_overseas,
        'address' => $place->road_address ?: ($place->address ?: ''),
    ];
    $isAuth = (bool) auth()->user();
@endphp
<script>
(function() {
    const PLACE = {!! json_encode($placeJs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!};
    const SHARE_URL = {!! json_encode($shareUrl, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!};
    const IS_AUTH = {!! json_encode($isAuth) !!};
    const CATEGORY_NAME = {!! json_encode($categoryName, JSON_UNESCAPED_UNICODE) !!};

    // ===== 공유 =====
    const shareText = `${PLACE.name} | ${CATEGORY_NAME}${PLACE.address ? ' · ' + PLACE.address : ''}`;
    async function sharePlace() {
        const data = { title: `${PLACE.name} - 핀픽`, text: shareText, url: SHARE_URL };
        try {
            if (navigator.share) { await navigator.share(data); return; }
        } catch(e) { if (e.name === 'AbortError') return; }
        try {
            await navigator.clipboard.writeText(SHARE_URL);
            alert('링크가 복사되었어요');
        } catch(e) { prompt('아래 링크를 복사하세요', SHARE_URL); }
    }
    document.getElementById('ppPubShare')?.addEventListener('click', sharePlace);
    document.getElementById('ppPubShareTop')?.addEventListener('click', sharePlace);

    // ===== 지도 =====
    const mapEl = document.getElementById('ppPubMap');
    function initNaverMap() {
        if (!mapEl || typeof naver === 'undefined' || !naver.maps) return;
        const pos = new naver.maps.LatLng(PLACE.lat, PLACE.lng);
        const map = new naver.maps.Map(mapEl, {
            center: pos, zoom: 16,
            mapTypeControl: false, zoomControl: false, scaleControl: false, logoControl: false, mapDataControl: false,
        });
        new naver.maps.Marker({ position: pos, map, title: PLACE.name });
    }
    function initGoogleMap() {
        if (!mapEl || typeof google === 'undefined' || !google.maps) return;
        const pos = { lat: PLACE.lat, lng: PLACE.lng };
        const map = new google.maps.Map(mapEl, {
            center: pos, zoom: 16, disableDefaultUI: true, clickableIcons: false,
        });
        new google.maps.Marker({ position: pos, map, title: PLACE.name });
    }
    if (mapEl) {
        if (PLACE.is_overseas) {
            window.ppPubInitMap = initGoogleMap;
            if (typeof google !== 'undefined' && google.maps) initGoogleMap();
        } else {
            if (typeof naver !== 'undefined' && naver.maps) initNaverMap();
            else window.addEventListener('load', initNaverMap, { once: true });
        }
    }

    // ===== 지도 열기 / 길찾기 =====
    function openExternalMap(isRoute) {
        const ua = navigator.userAgent || '';
        const mobile = /iPhone|iPad|iPod|Android/i.test(ua);
        const encName = encodeURIComponent(PLACE.name);
        let webUrl, appUrl;
        if (PLACE.is_overseas) {
            if (isRoute) {
                appUrl = `comgooglemaps://?daddr=${PLACE.lat},${PLACE.lng}&directionsmode=driving`;
                webUrl = `https://www.google.com/maps/dir/?api=1&destination=${PLACE.lat},${PLACE.lng}`;
            } else {
                appUrl = `comgooglemaps://?q=${PLACE.lat},${PLACE.lng}(${encName})`;
                webUrl = `https://www.google.com/maps/search/?api=1&query=${PLACE.lat},${PLACE.lng}`;
            }
        } else {
            if (isRoute) {
                appUrl = `nmap://route/car?dlat=${PLACE.lat}&dlng=${PLACE.lng}&dname=${encName}&appname=net.mypinpick`;
                webUrl = `https://map.naver.com/p/directions/-/${PLACE.lng},${PLACE.lat},${encName},,PLACE_POI/-/car`;
            } else {
                appUrl = `nmap://place?lat=${PLACE.lat}&lng=${PLACE.lng}&name=${encName}&appname=net.mypinpick`;
                webUrl = `https://map.naver.com/p/search/${encName}/place/?c=15,0,0,0,dh`;
            }
        }
        if (mobile) {
            const t = Date.now();
            window.location.href = appUrl;
            setTimeout(() => { if (Date.now() - t < 1600 && !document.hidden) window.open(webUrl, '_blank'); }, 1200);
        } else {
            window.open(webUrl, '_blank');
        }
    }
    document.getElementById('ppPubOpenMap')?.addEventListener('click', () => openExternalMap(false));
    document.getElementById('ppPubRoute')?.addEventListener('click', () => openExternalMap(true));

    // ===== 저장 바텀시트 =====
    const sheet = document.getElementById('ppPubSheet');
    const sheetBack = document.getElementById('ppPubSheetBack');
    const sheetList = document.getElementById('ppPubSheetList');
    let catsLoaded = false;

    function openSheet() {
        if (!IS_AUTH) {
            if (confirm('로그인이 필요한 기능입니다. 로그인 페이지로 이동할까요?')) {
                location.href = '/login';
            }
            return;
        }
        sheet.hidden = false;
        requestAnimationFrame(() => sheet.classList.add('is-open'));
        if (!catsLoaded) loadCategories();
    }
    function closeSheet() {
        sheet.classList.remove('is-open');
        setTimeout(() => { sheet.hidden = true; }, 220);
    }

    async function loadCategories() {
        try {
            const r = await fetch('/api/categories', { headers: { 'Accept': 'application/json' } });
            if (!r.ok) throw new Error('load fail');
            const j = await r.json();
            const cats = Array.isArray(j) ? j : (j.items || []);
            if (!cats.length) {
                sheetList.innerHTML = '<div class="pp-pub-sheet__loading">카테고리가 없어요. 먼저 카테고리를 추가해주세요.</div>';
                return;
            }
            sheetList.innerHTML = cats.map(c =>
                `<button type="button" class="pp-pub-sheet__item" data-cid="${c.id}"><span class="pp-pub-sheet__ico">${c.icon || '📌'}</span><span>${escapeHtml(c.name)}</span></button>`
            ).join('');
            catsLoaded = true;
        } catch (e) {
            sheetList.innerHTML = '<div class="pp-pub-sheet__loading">불러오기에 실패했어요</div>';
        }
    }
    function escapeHtml(s){return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}

    async function saveToMyPlaces(categoryId) {
        try {
            const r = await fetch(`/place/${PLACE.id}/copy`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ category_id: categoryId }),
            });
            const j = await r.json();
            if (r.ok && j.ok) {
                alert('내 장소에 저장되었어요');
                location.href = j.redirect || '/';
            } else {
                alert(j.error || '저장에 실패했어요');
            }
        } catch (e) { alert('네트워크 오류'); }
    }

    sheetList.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-cid]');
        if (!btn) return;
        saveToMyPlaces(btn.dataset.cid);
    });
    sheetBack.addEventListener('click', closeSheet);

    document.getElementById('ppPubSaveTop')?.addEventListener('click', openSheet);
    document.getElementById('ppPubSaveBottom')?.addEventListener('click', openSheet);
    document.getElementById('ppPubPickCat')?.addEventListener('click', openSheet);
})();
</script>
@endpush
