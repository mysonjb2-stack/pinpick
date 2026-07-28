@extends('layouts.app')
@section('page_title', $collection->title . ' | 핀픽')
@section('app_class', 'pp-app--shared pp-app--curpage')

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@php $ogImage = \App\Services\OgImageResolver::forSharedCollection($collection); @endphp
@section('og_title', $collection->title)
@section('og_description', '장소 ' . $collection->places->count() . '개 · 나만의 장소, 나만의 지도 핀픽')
@section('og_image', $ogImage)

@push('head')
@include('partials.cur-map-styles')
@endpush

@section('content')
{{-- Full-screen map --}}
<div class="pp-cur-map">
    <div class="pp-cur-map__skel" id="curMapSkel"></div>
    <div class="pp-cur-map__el" id="curMap"></div>
</div>

{{-- Header overlay --}}
<div class="pp-cur-hdr">
    <button type="button" class="pp-cur-hdr__btn" id="curBack" aria-label="뒤로">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
</div>

{{-- App banner --}}
<div class="pp-cur-app-banner" id="ppAppBanner" style="display:none">
    <div class="pp-cur-app-banner__left">
        <img src="{{ asset('icon-192.png') }}" alt="" width="28" height="28" style="border-radius:6px">
        <span class="pp-cur-app-banner__text">앱에서 더 편하게 저장하세요</span>
    </div>
    <div class="pp-cur-app-banner__right">
        <button type="button" class="pp-cur-app-banner__open" id="ppAppOpen">앱으로 열기</button>
        <button type="button" class="pp-cur-app-banner__close" id="ppAppClose" aria-label="닫기">&times;</button>
    </div>
</div>

{{-- Fit button --}}
<button type="button" class="pp-cur-fit" id="curFitBtn">전체 보기</button>

{{-- Bottom Sheet --}}
<div class="pp-cur-sheet is-mid" id="curSheet">
    <div class="pp-cur-sheet__handle" id="curSheetHandle"></div>

    <div class="pp-cur-sheet__scroll" id="curSheetScroll">
        {{-- Peek: always visible --}}
        <div class="pp-cur-sheet__hdr" id="curSheetHdr">
            <div class="pp-cur-sheet__title-row">
                <h1 class="pp-cur-sheet__title">{{ $collection->title }}</h1>
                <a href="/" class="pp-cur-hdr__btn" aria-label="핀픽 홈" style="pointer-events:auto;width:32px;height:32px;">
                    <img src="{{ asset('icon-192.png') }}" alt="" width="22" height="22" style="border-radius:5px">
                </a>
            </div>
            <p class="pp-cur-sheet__summary">
                {{ $collection->user->name }}님이 공유한 장소 {{ $collection->places->count() }}개
            </p>
        </div>

        {{-- Detail: visible from mid --}}
        <div class="pp-cur-sheet__detail"></div>

        <div class="pp-cur-sheet__list-hdr">
            <span class="pp-cur-sheet__list-count">장소 {{ $collection->places->count() }}개</span>
            <button type="button" class="pp-cur-sheet__sel-all" id="curSelAll">전체선택</button>
        </div>

        {{-- Place cards --}}
        @foreach($collection->places as $i => $place)
            @php
                $searchName = $place->original_place_name ?: $place->display_name;
                if ($place->is_overseas) {
                    $mapUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($searchName . ' ' . $place->address);
                    $mapLabel = '구글 지도';
                } else {
                    $mapUrl = 'https://map.naver.com/p/search/' . urlencode($searchName . ' ' . $place->address);
                    $mapLabel = '네이버 지도';
                }
            @endphp
            @include('partials.cur-place-card', [
                'cardIdx' => $i,
                'placeId' => $place->id,
                'placeName' => $place->display_name,
                'categoryLabel' => $place->category_label,
                'address' => $place->address,
                'latitude' => $place->latitude,
                'longitude' => $place->longitude,
                'isOverseas' => (bool) $place->is_overseas,
                'mapUrl' => $mapUrl,
                'mapLabel' => $mapLabel,
                'photos' => [],
                'thumbnailUrl' => $place->thumbnail_url,
                'editorNote' => null,
                'sourceChannel' => null,
                'sourceDate' => null,
                'sourceUrl' => null,
                'memo' => $place->memo,
                'dayNumber' => null,
                'isCourse' => false,
            ])
        @endforeach

        <div class="pp-cur-sheet__bottom-pad"></div>
    </div>
</div>

{{-- CTA --}}
<div class="pp-cur-cta" id="curCta">
    <button type="button" class="pp-btn pp-cur-cta__btn" id="curSaveBtn">이 장소들 내 핀픽에 담기</button>
    <p class="pp-cur-cta__hint" id="curGuestHint" style="display:none"></p>
</div>

{{-- Guest sheet --}}
<div class="pp-share__select-sheet" id="curGuestSheet">
    <div class="pp-share__select-backdrop" data-role="close"></div>
    <div class="pp-share__select-panel">
        <div class="pp-share__select-header">
            <h3>게스트 저장 안내</h3>
            <button type="button" class="pp-share__select-close" data-role="close">&times;</button>
        </div>
        <div class="pp-share__select-info" id="curGuestInfo"></div>
        <div class="pp-share__select-actions">
            <button type="button" class="pp-btn" id="curGuestPick">직접 고르기</button>
            <button type="button" class="pp-btn pp-btn--ghost" id="curGuestLogin">로그인하고 전부 저장</button>
        </div>
    </div>
</div>

{{-- Reselect bar --}}
<div class="pp-share__reselect-bar" id="curReselectBar" style="display:none">
    <span id="curReselectCount"></span>
    <button type="button" class="pp-share__reselect-cancel" id="curReselectCancel">취소</button>
</div>

{{-- Category sheet --}}
<div class="pp-share__cat-sheet" id="curCatSheet">
    <div class="pp-share__select-backdrop" data-role="close"></div>
    <div class="pp-share__select-panel">
        <div class="pp-share__select-header">
            <h3>저장할 카테고리 선택</h3>
            <button type="button" class="pp-share__select-close" data-role="close">&times;</button>
        </div>
        <div class="pp-share__cat-list" id="curCatList">
            <label class="pp-share__cat-option">
                <input type="radio" name="cur_cat" value="__new__" checked>
                <span class="pp-share__cat-name">새 카테고리 만들기</span>
            </label>
            <div class="pp-share__cat-input-wrap" id="curCatInputWrap">
                <input type="text" id="curCatInput" class="pp-share__cat-input" maxlength="30" value="{{ $collection->title }}">
                <div class="pp-share__cat-dup-hint" id="curCatDupHint"></div>
            </div>
            <label class="pp-share__cat-option">
                <input type="radio" name="cur_cat" value="__existing__">
                <span class="pp-share__cat-name">기존 카테고리에 저장</span>
            </label>
            <div class="pp-share__cat-existing" id="curCatExisting" style="display:none"></div>
        </div>
        <div class="pp-share__select-actions">
            <button type="button" class="pp-btn" id="curCatSaveBtn">저장하기</button>
        </div>
    </div>
</div>

@php
    $placesJson = $collection->places->map(function($p) {
        return [
            'id' => $p->id,
            'name' => $p->display_name,
            'original_name' => $p->original_place_name,
            'address' => $p->address,
            'jibeon_address' => $p->jibeon_address,
            'building_name' => $p->building_name,
            'detail_location' => $p->detail_location,
            'phone' => $p->phone,
            'opening_hours' => $p->opening_hours,
            'lat' => $p->latitude,
            'lng' => $p->longitude,
            'category_label' => $p->category_label,
            'themes' => $p->themes ?? [],
            'memo' => $p->memo,
            'thumbnail_url' => $p->thumbnail_url,
            'external_place_id' => $p->external_place_id,
            'naver_place_id' => $p->naver_place_id,
            'google_place_id' => $p->google_place_id,
            'is_overseas' => (bool) $p->is_overseas,
        ];
    });

    $validCoords = $collection->places->filter(fn($p) => $p->latitude && $p->longitude);
    $initZoom = 8;
    $initLat = 37.5;
    $initLng = 127.0;

    if ($validCoords->count() >= 1) {
        $lats = $validCoords->pluck('latitude')->map(fn($v) => (float)$v);
        $lngs = $validCoords->pluck('longitude')->map(fn($v) => (float)$v);
        $minLat = $lats->min(); $maxLat = $lats->max();
        $minLng = $lngs->min(); $maxLng = $lngs->max();
        $midLat = ($minLat + $maxLat) / 2;
        $midLng = ($minLng + $maxLng) / 2;

        $containerH = 480;
        $headerH = 66;
        $sheetVisH = round($containerH * 0.52);
        $visH = $containerH - $sheetVisH - $headerH;
        $containerW = 480;

        if ($validCoords->count() === 1) {
            $initZoom = 15;
        } else {
            $pad = 80;
            $effW = max($containerW - $pad, 50);
            $effH = max($visH - $pad, 50);
            $lngSpan = $maxLng - $minLng;
            $mercMax = log(tan(M_PI / 4 + deg2rad($maxLat) / 2));
            $mercMin = log(tan(M_PI / 4 + deg2rad($minLat) / 2));
            $mercSpan = abs($mercMax - $mercMin);
            $z = 18;
            if ($lngSpan > 0) $z = min($z, log($effW * 360 / ($lngSpan * 256)) / log(2));
            if ($mercSpan > 0) $z = min($z, log($effH * 2 * M_PI / (256 * $mercSpan)) / log(2));
            $initZoom = max(2, min((int)floor($z), 14));
        }

        $deltaY = $containerH / 2 - ($headerH + $visH / 2);
        $mpp = 156543.03392 * cos(deg2rad($midLat)) / pow(2, $initZoom);
        $latOff = $deltaY * $mpp / 111320;
        $initLat = $midLat - $latOff;
        $initLng = $midLng;
    }
@endphp
<script src="https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId={{ config('services.naver_map.client_id') }}"></script>
<script>
(function() {
    const csrf = '{{ csrf_token() }}';
    const token = '{{ $collection->token }}';
    const isAuth = {{ Auth::check() ? 'true' : 'false' }};
    const shareTitle = @json($collection->title);
    const places = @json($placesJson);
    const userCats = @json($userCategories);
    const totalCount = places.length;
    const selected = new Set();
    let reselectMode = false;
    let reselectLimit = 0;
    let savedSelection = null;
    let activeMarkerIdx = -1;

    const IS_APP_WEBVIEW = /MYPINPICK/i.test(navigator.userAgent);
    const _qp = new URLSearchParams(location.search);
    const _qpSelected = _qp.get('selected');
    const _qpAction = _qp.get('action');
    const IS_APP_AUTO_SAVE = IS_APP_WEBVIEW && _qpAction === 'save';

    // ── Deep link helpers ──
    function getSelectedSortOrders() {
        const orders = [];
        selected.forEach(id => {
            const idx = places.findIndex(p => p.id === id);
            if (idx >= 0) orders.push(idx + 1);
        });
        return orders.sort((a,b) => a-b).join(',');
    }

    function buildSchemeUrl(withAction) {
        let url = 'pinpick://s/' + token;
        const params = [];
        const orders = getSelectedSortOrders();
        if (orders) params.push('selected=' + orders);
        if (withAction) params.push('action=save');
        if (params.length) url += '?' + params.join('&');
        return url;
    }

    // ── App banner ──
    (function initAppBanner() {
        const banner = document.getElementById('ppAppBanner');
        if (!banner || IS_APP_WEBVIEW) return;
        if (sessionStorage.getItem('pp_app_banner_closed')) return;
        banner.style.display = '';
        document.getElementById('ppAppOpen').addEventListener('click', () => {
            location.href = buildSchemeUrl(false);
            setTimeout(() => {
                if (document.hidden) return;
            }, 1500);
        });
        document.getElementById('ppAppClose').addEventListener('click', () => {
            banner.style.display = 'none';
            sessionStorage.setItem('pp_app_banner_closed', '1');
        });
    })();

    // ── Back ──
    document.getElementById('curBack').addEventListener('click', () => {
        if (history.length > 1 && document.referrer) history.back();
        else location.href = '/';
    });

    // ── Container ref ──
    const container = document.querySelector('.pp-app');
    const containerH = () => container.offsetHeight;

    // ── Map ──
    let map = null, markers = [];
    const pinSize = 28;
    const HEADER_H = 66;

    function pinHtml(num, hl) {
        const bg = hl ? '#e67e22' : 'var(--pp-primary,#2b211e)';
        const sc = hl ? 'transform:scale(1.25);' : '';
        return '<div style="background:'+bg+';color:#fff;width:'+pinSize+'px;height:'+pinSize+'px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3);transition:transform .15s;'+sc+'">'+num+'</div>';
    }

    function getSheetTopPx() {
        const ch = containerH();
        const sH = sheetH();
        const offsets = getOffsets();
        return ch - sH + offsets[sheetState];
    }

    function getVisibleMapArea() {
        const top = HEADER_H;
        const bottom = Math.max(top + 60, getSheetTopPx());
        return { top: top, bottom: bottom, height: bottom - top };
    }

    function mercY(lat) {
        return Math.log(Math.tan(Math.PI / 4 + lat * Math.PI / 360));
    }

    function calcZoom(vp, viewW, viewH) {
        const PAD = 80;
        const effW = Math.max(viewW - PAD, 50);
        const effH = Math.max(viewH - PAD, 50);
        const lats = vp.map(p => p.lat), lngs = vp.map(p => p.lng);
        const lngSpan = Math.max(...lngs) - Math.min(...lngs);
        const mSpan = Math.abs(mercY(Math.max(...lats)) - mercY(Math.min(...lats)));
        let z = 18;
        if (lngSpan > 0) z = Math.min(z, Math.log2(effW * 360 / (lngSpan * 256)));
        if (mSpan > 0) z = Math.min(z, Math.log2(effH * 2 * Math.PI / (256 * mSpan)));
        return Math.max(2, Math.min(Math.floor(z), 14));
    }

    function centerForVisible(lat, lng, zoom) {
        const ch = containerH();
        const vis = getVisibleMapArea();
        const deltaY = ch / 2 - (vis.top + vis.height / 2);
        const mpp = 156543.03392 * Math.cos(lat * Math.PI / 180) / Math.pow(2, zoom);
        return new naver.maps.LatLng(lat - deltaY * mpp / 111320, lng);
    }

    function fitMapToAll() {
        if (!map) return;
        const vp = places.filter(p => p.lat && p.lng);
        if (!vp.length) return;
        if (vp.length === 1) {
            map.setZoom(15);
            map.setCenter(centerForVisible(vp[0].lat, vp[0].lng, 15));
        } else {
            const vis = getVisibleMapArea();
            const cw = container.offsetWidth;
            const z = calcZoom(vp, cw, vis.height);
            const midLat = (Math.min(...vp.map(p=>p.lat)) + Math.max(...vp.map(p=>p.lat))) / 2;
            const midLng = (Math.min(...vp.map(p=>p.lng)) + Math.max(...vp.map(p=>p.lng))) / 2;
            map.setZoom(z);
            map.setCenter(centerForVisible(midLat, midLng, z));
        }
    }

    const fitBtn = document.getElementById('curFitBtn');
    const mapEl = document.getElementById('curMap');
    const skelEl = document.getElementById('curMapSkel');

    if (places.length && typeof naver !== 'undefined') {
        map = new naver.maps.Map('curMap', {
            center: new naver.maps.LatLng({{ $initLat }}, {{ $initLng }}),
            zoom: {{ $initZoom }},
            zoomControl: false, scaleControl: false, logoControl: false, mapDataControl: false,
        });

        places.forEach((p, i) => {
            if (!p.lat || !p.lng) { markers.push(null); return; }
            const m = new naver.maps.Marker({
                position: new naver.maps.LatLng(p.lat, p.lng),
                map: map,
                icon: { content: pinHtml(i+1, false), anchor: new naver.maps.Point(pinSize/2, pinSize/2) },
                zIndex: 100,
            });
            naver.maps.Event.addListener(m, 'click', () => handlePinTap(i));
            markers.push(m);
        });
        naver.maps.Event.addListener(map, 'click', () => resetHighlight());

        let initFitDone = false;
        function doInitFit() {
            if (initFitDone) return;
            initFitDone = true;
            fitMapToAll();
            mapEl.classList.add('is-ready');
            setTimeout(() => { if (skelEl) skelEl.style.display = 'none'; }, 250);
        }
        naver.maps.Event.addListener(map, 'idle', doInitFit);
        setTimeout(doInitFit, 1200);

        window.addEventListener('resize', () => {
            if (!map) return;
            map.autoResize();
            setTimeout(fitMapToAll, 100);
        });
    } else {
        mapEl.classList.add('is-ready');
        if (skelEl) skelEl.style.display = 'none';
    }

    function syncAllPins() {
        markers.forEach((m, i) => {
            if (!m) return;
            const hl = selected.has(places[i].id);
            m.setIcon({ content: pinHtml(i+1, hl), anchor: new naver.maps.Point(pinSize/2, pinSize/2) });
            m.setZIndex(i === activeMarkerIdx ? 200 : (hl ? 150 : 100));
        });
        fitBtn.style.display = activeMarkerIdx >= 0 ? '' : 'none';
    }

    function highlightPin(idx) {
        activeMarkerIdx = idx;
        syncAllPins();
    }

    function resetHighlight() {
        highlightPin(-1);
        document.querySelectorAll('.pp-cur-card').forEach(c => c.classList.remove('is-focused'));
        fitMapToAll();
    }
    fitBtn.addEventListener('click', () => resetHighlight());

    function focusPin(lat, lng, zoom) {
        const z = zoom || 16;
        map.setZoom(z);
        map.setCenter(centerForVisible(lat, lng, z));
    }

    function toggleCardSelection(idx) {
        const card = document.querySelector('.pp-cur-card[data-idx="'+idx+'"]');
        if (!card) return;
        const id = parseInt(card.dataset.id);
        const p = places[idx];
        if (selected.has(id)) {
            selected.delete(id);
            card.classList.remove('is-selected');
            resetHighlight();
        } else {
            if (reselectMode && selected.size >= reselectLimit) {
                showToast('비로그인은 ' + reselectLimit + '개까지만 선택할 수 있어요');
                return;
            }
            selected.add(id);
            card.classList.add('is-selected');
            document.querySelectorAll('.pp-cur-card').forEach(c => c.classList.remove('is-focused'));
            card.classList.add('is-focused');
            highlightPin(idx);
            if (map && p.lat && p.lng) {
                if (sheetState !== 'full') {
                    setTimeout(() => focusPin(p.lat, p.lng, 16), 10);
                } else {
                    focusPin(p.lat, p.lng, 16);
                }
            }
        }
        updateCtaText();
        updateSelectAllBtn();
    }

    function handlePinTap(idx) {
        const wasPeek = sheetState === 'peek';
        if (wasPeek) setSheetState('mid');
        setTimeout(() => {
            toggleCardSelection(idx);
            const card = document.querySelector('.pp-cur-card[data-idx="'+idx+'"]');
            if (card && selected.has(places[idx].id)) {
                setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 100);
            }
        }, wasPeek ? 350 : 10);
    }

    // ── Bottom Sheet ──
    const sheet = document.getElementById('curSheet');
    const handle = document.getElementById('curSheetHandle');
    const sheetScroll = document.getElementById('curSheetScroll');
    const sheetHdr = document.getElementById('curSheetHdr');
    const sheetH = () => sheet.offsetHeight;

    const PEEK_PX = 110;
    let sheetState = 'mid';

    function getOffsets() {
        const h = sheetH();
        const ch = containerH();
        return {
            peek: h - PEEK_PX,
            mid: h - Math.round(ch * 0.52),
            full: 0,
        };
    }

    function setSheetState(state) {
        const offsets = getOffsets();
        sheetState = state;
        sheet.style.transform = 'translateY(' + Math.max(0, offsets[state]) + 'px)';
        sheet.classList.toggle('is-peek', state === 'peek');
        sheet.classList.toggle('is-mid', state === 'mid');
        sheet.classList.toggle('is-full', state === 'full');
        const handleH = handle.offsetHeight || 28;
        const ctaEl = document.getElementById('curCta');
        const ctaH = ctaEl ? ctaEl.offsetHeight : 60;
        if (state === 'full') {
            sheetScroll.style.maxHeight = '';
        } else if (state === 'mid') {
            const visibleH = Math.round(containerH() * 0.52) - handleH - ctaH;
            sheetScroll.style.maxHeight = Math.max(120, visibleH) + 'px';
        } else {
            sheetScroll.style.maxHeight = (PEEK_PX - handleH) + 'px';
        }
    }

    setSheetState('mid');

    // Touch gestures
    let dragStartY = 0, dragStartOffset = 0, isDragging = false, currentOffset = 0, dragEndTime = 0;

    function getCurrentOffset() {
        const t = getComputedStyle(sheet).transform;
        if (!t || t === 'none') return 0;
        const m = t.match(/matrix\(.+,\s*(.+)\)/);
        return m ? parseFloat(m[1]) : 0;
    }

    function onDragStart(e) {
        const t = e.touches ? e.touches[0] : e;
        dragStartY = t.clientY;
        dragStartOffset = getCurrentOffset();
        isDragging = true;
        sheet.classList.add('is-dragging');
    }

    function onDragMove(e) {
        if (!isDragging) return;
        const t = e.touches ? e.touches[0] : e;
        const dy = t.clientY - dragStartY;
        currentOffset = Math.max(0, Math.min(dragStartOffset + dy, sheetH() - PEEK_PX));
        sheet.style.transform = 'translateY(' + currentOffset + 'px)';
    }

    function onDragEnd() {
        if (!isDragging) return;
        isDragging = false;
        dragEndTime = Date.now();
        sheet.classList.remove('is-dragging');
        const offsets = getOffsets();
        const velocity = currentOffset - dragStartOffset;
        let target;
        if (velocity > 60) {
            target = sheetState === 'full' ? 'mid' : sheetState === 'mid' ? 'peek' : 'peek';
        } else if (velocity < -60) {
            target = sheetState === 'peek' ? 'mid' : sheetState === 'mid' ? 'full' : 'full';
        } else {
            const dists = { peek: Math.abs(currentOffset - offsets.peek), mid: Math.abs(currentOffset - offsets.mid), full: Math.abs(currentOffset - offsets.full) };
            target = Object.keys(dists).reduce((a, b) => dists[a] < dists[b] ? a : b);
        }
        setSheetState(target);
    }

    handle.addEventListener('touchstart', onDragStart, { passive: true });
    handle.addEventListener('touchmove', onDragMove, { passive: true });
    handle.addEventListener('touchend', onDragEnd);

    sheetHdr.addEventListener('touchstart', onDragStart, { passive: true });
    sheetHdr.addEventListener('touchmove', onDragMove, { passive: true });
    sheetHdr.addEventListener('touchend', onDragEnd);
    sheetHdr.addEventListener('mousedown', (e) => { onDragStart(e); });

    let scrollPullStart = 0;
    sheetScroll.addEventListener('touchstart', function(e) {
        if (sheetState !== 'full') return;
        scrollPullStart = sheetScroll.scrollTop <= 0 ? e.touches[0].clientY : 0;
    }, { passive: true });

    sheetScroll.addEventListener('touchmove', function(e) {
        if (sheetState !== 'full' || !scrollPullStart) return;
        if (sheetScroll.scrollTop <= 0) {
            if (e.touches[0].clientY - scrollPullStart > 50) {
                scrollPullStart = 0;
                setSheetState('mid');
            }
        } else {
            scrollPullStart = 0;
        }
    }, { passive: true });

    sheetHdr.addEventListener('click', (e) => {
        if (isDragging || Date.now() - dragEndTime < 300) return;
        if (sheetState === 'peek') setSheetState('mid');
        else if (sheetState === 'mid') setSheetState('full');
    });

    handle.addEventListener('mousedown', (e) => { onDragStart(e); });
    document.addEventListener('mousemove', (e) => { if (isDragging) onDragMove(e); });
    document.addEventListener('mouseup', () => { if (isDragging) onDragEnd(); });

    // ── Card tap = selection toggle + map focus ──
    document.querySelectorAll('.pp-cur-card').forEach(card => {
        card.addEventListener('click', e => {
            if (e.target.closest('.pp-cur-card__photo[data-full]')) return;
            if (e.target.closest('.pp-cur-card__map-chip')) return;
            if (e.target.closest('.pp-cur-card__source a')) return;
            toggleCardSelection(parseInt(card.dataset.idx));
        });
    });

    // ── Query param selected 복원 ──
    if (_qpSelected) {
        const sortOrders = _qpSelected.split(',').map(Number).filter(n => n > 0);
        sortOrders.forEach(order => {
            const idx = order - 1;
            if (idx >= 0 && idx < places.length) {
                selected.add(places[idx].id);
                const card = document.querySelector('.pp-cur-card[data-idx="'+idx+'"]');
                if (card) card.classList.add('is-selected');
            }
        });
        syncAllPins();
        updateCtaText();
        updateSelectAllBtn();
    }

    // ── Selection ──
    const saveBtn = document.getElementById('curSaveBtn');
    const selectAllBtn = document.getElementById('curSelAll');
    const allCards = document.querySelectorAll('.pp-cur-card');

    selectAllBtn.addEventListener('click', () => {
        if (selected.size === totalCount) {
            selected.clear();
            allCards.forEach(c => c.classList.remove('is-selected'));
        } else {
            places.forEach(p => selected.add(p.id));
            allCards.forEach(c => c.classList.add('is-selected'));
        }
        resetHighlight();
        updateCtaText();
        updateSelectAllBtn();
    });

    function updateSelectAllBtn() { selectAllBtn.textContent = selected.size === totalCount ? '선택해제' : '전체선택'; }
    function updateCtaText() {
        if (reselectMode) {
            saveBtn.textContent = selected.size > 0 ? selected.size + '개 저장하기' : '저장할 장소를 골라주세요';
            saveBtn.disabled = selected.size === 0;
            updateReselectCounter();
            return;
        }
        saveBtn.disabled = false;
        saveBtn.textContent = selected.size > 0 ? '선택한 ' + selected.size + '곳 담기' : '이 장소들 내 핀픽에 담기';
    }
    function getSelectedIds() { return selected.size > 0 ? Array.from(selected) : places.map(p => p.id); }

    saveBtn.addEventListener('click', () => {
        if (reselectMode) { doReselectSave(); return; }
        if (isAuth) openCategorySheet(); else handleGuestSave();
    });

    // ── action=save auto-trigger ──
    if (_qpAction === 'save') {
        setTimeout(() => {
            if (IS_APP_AUTO_SAVE && isAuth) {
                checkAndAutoSave();
            } else {
                places.forEach(p => selected.add(p.id));
                allCards.forEach(c => c.classList.add('is-selected'));
                syncAllPins();
                updateCtaText();
                updateSelectAllBtn();
                if (isAuth) openCategorySheet(); else handleGuestSave();
            }
        }, 600);
    }

    async function checkAndAutoSave() {
        const ids = getSelectedIds();
        try {
            const res = await fetch('/s/' + token + '/check-duplicates', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ place_ids: ids }),
            });
            const data = await res.json();
            if (data.all_duplicates) {
                showDone('이미 저장된 장소예요', true);
            } else {
                openCategorySheet();
            }
        } catch (e) {
            openCategorySheet();
        }
    }

    // ── Guest save ──
    const GUEST_KEY = 'pinpick_guest_places';
    const guestSheet = document.getElementById('curGuestSheet');
    const catSheet = document.getElementById('curCatSheet');

    document.querySelectorAll('[data-role="close"]').forEach(el => {
        el.addEventListener('click', () => {
            guestSheet.classList.remove('is-open');
            catSheet.classList.remove('is-open');
        });
    });

    function getGuestRemaining() { return 5 - JSON.parse(localStorage.getItem(GUEST_KEY) || '[]').length; }
    function updateGuestHint() {
        const hint = document.getElementById('curGuestHint');
        if (!hint || isAuth) return;
        hint.textContent = '비로그인은 5개까지 저장돼요 · 남은 저장 ' + getGuestRemaining() + '개';
        hint.style.display = '';
    }

    function countNewPlaces(toSave, guestPlaces) {
        return toSave.filter(p => !guestPlaces.some(g =>
            (p.external_place_id && g.kakao_place_id === p.external_place_id) ||
            (g.name === p.name && g.road_address === p.address)
        )).length;
    }

    function handleGuestSave() {
        const guestPlaces = JSON.parse(localStorage.getItem(GUEST_KEY) || '[]');
        const remaining = 5 - guestPlaces.length;
        const toSave = places.filter(p => getSelectedIds().includes(p.id));
        const newCount = countNewPlaces(toSave, guestPlaces);

        if (remaining <= 0) {
            document.getElementById('curGuestInfo').textContent = '비로그인 저장 5개를 모두 사용했어요.\n로그인하면 전부 저장돼요';
            document.getElementById('curGuestPick').style.display = 'none';
            const loginBtn = document.getElementById('curGuestLogin');
            loginBtn.textContent = '로그인하고 저장';
            loginBtn.className = 'pp-btn';
            guestSheet.classList.add('is-open');
            return;
        }
        if (newCount > remaining) {
            document.getElementById('curGuestInfo').textContent = '비로그인은 ' + remaining + '개까지 더 저장할 수 있어요';
            const pickBtn = document.getElementById('curGuestPick');
            pickBtn.textContent = '저장할 ' + remaining + '개 직접 고르기';
            pickBtn.style.display = '';
            pickBtn.dataset.limit = remaining;
            const loginBtn = document.getElementById('curGuestLogin');
            loginBtn.textContent = '로그인하고 전부 저장';
            loginBtn.className = 'pp-btn pp-btn--ghost';
            guestSheet.classList.add('is-open');
            return;
        }
        doGuestSave(toSave, guestPlaces);
    }

    document.getElementById('curGuestPick').addEventListener('click', function() {
        const limit = parseInt(this.dataset.limit) || getGuestRemaining();
        guestSheet.classList.remove('is-open');
        enterReselectMode(limit);
    });

    function enterReselectMode(limit) {
        reselectMode = true;
        reselectLimit = limit;
        savedSelection = new Set(selected);
        selected.clear();
        allCards.forEach(c => c.classList.remove('is-selected'));
        document.getElementById('curReselectBar').style.display = '';
        selectAllBtn.style.display = 'none';
        document.querySelectorAll('.pp-cur-card__add').forEach(b => { b.style.display = ''; });
        resetHighlight();
        updateReselectCounter();
        updateCtaText();
    }

    function exitReselectMode(restore) {
        reselectMode = false;
        reselectLimit = 0;
        document.getElementById('curReselectBar').style.display = 'none';
        selectAllBtn.style.display = '';
        saveBtn.disabled = false;
        if (restore && savedSelection) {
            selected.clear();
            allCards.forEach(c => c.classList.remove('is-selected'));
            savedSelection.forEach(id => {
                selected.add(id);
                const card = document.querySelector('.pp-cur-card[data-id="' + id + '"]');
                if (card) card.classList.add('is-selected');
            });
        }
        savedSelection = null;
        syncAllPins();
        updateCtaText();
        updateSelectAllBtn();
    }

    function updateReselectCounter() {
        const el = document.getElementById('curReselectCount');
        if (el) el.textContent = '저장할 장소를 골라주세요 (' + selected.size + '/' + reselectLimit + ')';
    }

    document.getElementById('curReselectCancel').addEventListener('click', () => {
        exitReselectMode(true);
    });

    function doReselectSave() {
        const guestPlaces = JSON.parse(localStorage.getItem(GUEST_KEY) || '[]');
        const ids = Array.from(selected);
        const toSave = places.filter(p => ids.includes(p.id));
        exitReselectMode(false);
        doGuestSave(toSave, guestPlaces);
    }

    function doGuestSave(toSave, guestPlaces) {
        let added = 0, skipped = 0;
        toSave.forEach((p, i) => {
            const exists = guestPlaces.some(g =>
                (p.external_place_id && g.kakao_place_id === p.external_place_id) ||
                (g.name === p.name && g.road_address === p.address)
            );
            if (exists) { skipped++; return; }
            guestPlaces.push({
                id: 'g_' + Date.now() + '_' + added,
                name: p.name, category_id: '', category_name: p.category_label || '', category_icon: '📌',
                category_label: shareTitle,
                phone: p.phone || '', opening_hours: p.opening_hours || [],
                address: p.jibeon_address || '', road_address: p.address || '',
                building_name: p.building_name || '', detail_location: p.detail_location || '',
                lat: p.lat, lng: p.lng, memo: p.memo || '',
                status: 'planned', visited_at: '', is_overseas: !!p.is_overseas,
                original_name: p.original_name || '', kakao_place_id: p.external_place_id || '',
                naver_place_id: p.naver_place_id || '', google_place_id: p.google_place_id || '',
                thumbnail_url: p.thumbnail_url || '', themes: p.themes || [],
                created_at: Date.now(),
            });
            added++;
        });
        localStorage.setItem(GUEST_KEY, JSON.stringify(guestPlaces));
        let msg;
        if (added === 0 && skipped > 0) msg = '이미 저장된 장소에요';
        else if (added > 0 && skipped > 0) msg = added + '개 저장 완료! (' + skipped + '개는 이미 저장됨)';
        else msg = added + '개 장소가 저장됐어요!';
        updateGuestHint();
        showDone(msg, false);
    }

    document.getElementById('curGuestLogin').addEventListener('click', () => {
        location.href = '/login?redirect=' + encodeURIComponent(location.pathname);
    });
    if (!isAuth) updateGuestHint();

    // ── Category sheet ──
    function openCategorySheet() {
        const catExisting = document.getElementById('curCatExisting');
        catExisting.innerHTML = '';
        if (userCats.length) {
            userCats.forEach(c => {
                const label = document.createElement('label');
                label.className = 'pp-share__cat-option';
                label.innerHTML = '<input type="radio" name="cur_cat_existing" value="'+c.id+'"><span class="pp-share__cat-name">'+(c.icon||'📌')+' '+escHtml(c.name)+'</span>';
                catExisting.appendChild(label);
            });
        }
        document.querySelectorAll('input[name="cur_cat"]').forEach(r => {
            r.addEventListener('change', () => {
                document.getElementById('curCatInputWrap').style.display = r.value === '__new__' ? '' : 'none';
                catExisting.style.display = r.value === '__new__' ? 'none' : '';
            });
        });
        catSheet.classList.add('is-open');
    }

    document.getElementById('curCatSaveBtn').addEventListener('click', async () => {
        const mode = document.querySelector('input[name="cur_cat"]:checked').value;
        let body = { place_ids: getSelectedIds() };
        if (mode === '__new__') {
            const name = document.getElementById('curCatInput').value.trim();
            if (!name) { alert('카테고리 이름을 입력하세요'); return; }
            body.new_category_name = name;
        } else {
            const sel = document.querySelector('input[name="cur_cat_existing"]:checked');
            if (!sel) { alert('카테고리를 선택하세요'); return; }
            body.category_id = parseInt(sel.value);
        }
        const btn = document.getElementById('curCatSaveBtn');
        btn.disabled = true; btn.textContent = '저장 중...';
        try {
            const res = await fetch('/s/' + token + '/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            const data = await res.json();
            if (data.success) {
                catSheet.classList.remove('is-open');
                let msg = data.saved + '개 장소가 저장됐어요!';
                if (data.skipped > 0) msg += ' (' + data.skipped + '개 중복 제외)';
                showDone(msg, true);
            } else { alert(data.error || '저장에 실패했어요'); }
        } catch(e) { alert('저장에 실패했어요'); }
        finally { btn.disabled = false; btn.textContent = '저장하기'; }
    });

    // ── Done state ──
    function showDone(msg, isServerSaved) {
        showToast(msg);

        if (IS_APP_AUTO_SAVE) {
            setTimeout(() => { location.href = '/'; }, 1200);
            return;
        }

        saveBtn.textContent = '핀픽에서 보기';
        saveBtn.className = 'pp-btn pp-cur-cta__btn pp-share__cta-btn--done';
        saveBtn.onclick = function() {
            if (IS_APP_WEBVIEW) {
                location.href = '/';
                return;
            }
            if (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
                if (isServerSaved) {
                    if (/Android/i.test(navigator.userAgent)) {
                        location.href = 'intent://#Intent;scheme=pinpick;S.browser_fallback_url=' +
                            encodeURIComponent(location.origin + '/') + ';end';
                    } else {
                        location.href = 'pinpick://';
                        setTimeout(() => { if (document.hidden) return; location.href = '/'; }, 1500);
                    }
                } else {
                    const orders = getSelectedSortOrders();
                    let deepPath = 's/' + token + '?action=save';
                    if (orders) deepPath += '&selected=' + orders;
                    if (/Android/i.test(navigator.userAgent)) {
                        location.href = 'intent://' + deepPath +
                            '#Intent;scheme=pinpick;S.browser_fallback_url=' +
                            encodeURIComponent(location.origin + '/') + ';end';
                    } else {
                        location.href = 'pinpick://' + deepPath;
                        setTimeout(() => { if (document.hidden) return; location.href = '/'; }, 1500);
                    }
                }
            } else {
                location.href = '/';
            }
        };
        document.querySelectorAll('.pp-cur-card__add').forEach(b => { b.style.display = 'none'; });
        selectAllBtn.style.display = 'none';
    }

    // ── Toast ──
    function showToast(msg) {
        let t = document.getElementById('ppCurToast');
        if (!t) { t = document.createElement('div'); t.id = 'ppCurToast'; t.className = 'pp-toast'; document.body.appendChild(t); }
        t.textContent = msg;
        t.classList.add('is-show');
        setTimeout(() => t.classList.remove('is-show'), 3000);
    }
    function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
})();
</script>
@endsection
