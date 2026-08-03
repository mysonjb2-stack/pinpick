@extends('layouts.app')
@section('page_title', $curation->title . ' | 핀픽')
@section('app_class', 'pp-app--shared pp-app--curpage')

@php $ogImage = \App\Services\OgImageResolver::forCuration($curation); @endphp
@section('og_title', $curation->title)
@section('og_description', $curation->description ?: ('장소 ' . $curation->places->count() . '곳 · 나만의 장소, 나만의 지도 핀픽'))
@section('og_image', $ogImage)

@push('head')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "ItemList",
    "name": @json($curation->title),
    "description": @json($curation->description ?: ''),
    "numberOfItems": {{ $curation->places->count() }},
    "itemListElement": [
        @foreach($curation->places as $i => $p)
        {
            "@@type": "ListItem",
            "position": {{ $i + 1 }},
            "item": {
                "@@type": "Place",
                "name": @json($p->place_name),
                "address": @json($p->address ?: '')
            }
        }@if(!$loop->last),@endif
        @endforeach
    ]
}
</script>
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

{{-- Fit button --}}
<button type="button" class="pp-cur-fit" id="curFitBtn">전체 보기</button>

{{-- Bottom Sheet --}}
<div class="pp-cur-sheet is-mid" id="curSheet">
    <div class="pp-cur-sheet__handle" id="curSheetHandle"></div>

    <div class="pp-cur-sheet__scroll" id="curSheetScroll">
        {{-- Peek: always visible --}}
        <div class="pp-cur-sheet__hdr" id="curSheetHdr">
            <div class="pp-cur-sheet__title-row">
                <h1 class="pp-cur-sheet__title">{{ $curation->title }}</h1>
                <button type="button" class="pp-cur-sheet__share-btn" id="curShareBtn" aria-label="공유">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                </button>
            </div>
            <p class="pp-cur-sheet__summary">
                장소 {{ $curation->places->count() }}곳
                @if($curation->save_count > 0) · {{ number_format($curation->save_count) }}명이 담아갔어요 @endif
            </p>
            <div class="pp-cur-sheet__author">
                @if($curation->author_type === 'user' && $curation->author)
                    @if($curation->author->profile_image)
                        <img class="pp-cur-author__avatar" src="{{ $curation->author->profile_image }}" alt="">
                    @else
                        @php $authorHue = crc32($curation->author->name) % 360; @endphp
                        <span class="pp-cur-author__avatar pp-cur-author__avatar--initial" style="background:hsl({{ $authorHue }},45%,55%)">{{ mb_substr($curation->author->name, 0, 1) }}</span>
                    @endif
                    <span class="pp-cur-author__name">{{ $curation->author->name }}</span>
                    <span class="pp-cur-author__dot">·</span>
                    <span class="pp-cur-author__count">{{ $curation->places->count() }}개 장소</span>
                @else
                    <img class="pp-cur-author__avatar" src="{{ asset('icon-192.png') }}" alt="">
                    <span class="pp-cur-author__name">핀픽</span>
                    <span class="pp-cur-author__dot">·</span>
                    <span class="pp-cur-author__count">{{ $curation->places->count() }}개 장소</span>
                @endif
            </div>
        </div>

        {{-- Detail: visible from mid --}}
        <div class="pp-cur-sheet__detail">
            @if($curation->region_label || $curation->duration_label)
                <div class="pp-cur__regions">
                    @if($curation->duration_label)
                        <span class="pp-cur__region-chip">{{ $curation->duration_label }}</span>
                    @endif
                    @foreach(array_map('trim', explode(',', $curation->region_label ?? '')) as $tag)
                        @if($tag)<span class="pp-cur__region-chip">{{ $tag }}</span>@endif
                    @endforeach
                </div>
            @endif
            @if($curation->description)
                <p class="pp-cur__desc">{{ $curation->description }}</p>
            @endif
        </div>

        @if($curation->type === 'course' && ($curation->days ?? 0) > 1)
        <div class="pp-cur__days" id="curDays">
            @php $days = $curation->places->pluck('day_number')->filter()->unique()->sort(); @endphp
            <button type="button" class="pp-cur__day-btn is-active" data-day="all">전체</button>
            @foreach($days as $d)
            <button type="button" class="pp-cur__day-btn" data-day="{{ $d }}">Day {{ $d }}</button>
            @endforeach
        </div>
        @endif

        <div class="pp-cur-sheet__list-hdr">
            <span class="pp-cur-sheet__list-count">장소 {{ $curation->places->count() }}곳</span>
            <button type="button" class="pp-cur-sheet__sel-all" id="curSelAll">전체선택</button>
        </div>

        {{-- Place cards --}}
        @foreach($curation->places as $i => $place)
            @php
                if ($place->is_overseas) {
                    $mapUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($place->place_name . ' ' . $place->address);
                    $mapLabel = '구글 지도';
                } else {
                    $mapUrl = 'https://map.naver.com/p/search/' . urlencode($place->place_name . ' ' . $place->address);
                    $mapLabel = '네이버 지도';
                }
            @endphp
            @php
                $gRevData = null;
                if ($place->google_place_id && isset($googleReviews[$place->google_place_id])) {
                    $gRevData = $googleReviews[$place->google_place_id];
                    $gRevData['place_id'] = $place->google_place_id;
                }
            @endphp
            @include('partials.cur-place-card', [
                'cardIdx' => $i,
                'placeId' => $place->id,
                'placeName' => $place->place_name,
                'categoryLabel' => $place->category_label,
                'address' => $place->address,
                'latitude' => $place->latitude,
                'longitude' => $place->longitude,
                'isOverseas' => (bool) $place->is_overseas,
                'mapUrl' => $mapUrl,
                'mapLabel' => $mapLabel,
                'photos' => $place->photos ?? [],
                'thumbnailUrl' => null,
                'editorNote' => $place->editor_note,
                'sourceChannel' => $place->source_channel,
                'sourceDate' => $place->source_date,
                'sourceUrl' => $place->source_url,
                'memo' => null,
                'dayNumber' => $place->day_number,
                'isCourse' => $curation->type === 'course',
                'durationDays' => $curation->days,
                'googleReviewData' => $gRevData,
            ])
        @endforeach

        <div class="pp-cur-sheet__bottom-pad"></div>

        @auth
        <div class="pp-cur-report">
            <button type="button" class="pp-cur-report__btn" id="curReportBtn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                신고
            </button>
        </div>
        @endauth
    </div>
</div>

{{-- Report Sheet --}}
@auth
<div class="pp-cur-report-sheet" id="curReportSheet">
    <div class="pp-cur-report-sheet__backdrop" data-close-report></div>
    <div class="pp-cur-report-sheet__panel">
        <div class="pp-cur-report-sheet__head">
            <h3>리스트 신고</h3>
            <button type="button" data-close-report class="pp-cur-report-sheet__close">&times;</button>
        </div>
        <div class="pp-cur-report-sheet__body">
            <label class="pp-cur-report-opt"><input type="radio" name="reportReason" value="spam"> 스팸/광고</label>
            <label class="pp-cur-report-opt"><input type="radio" name="reportReason" value="inappropriate"> 부적절한 콘텐츠</label>
            <label class="pp-cur-report-opt"><input type="radio" name="reportReason" value="copyright"> 저작권 침해</label>
            <label class="pp-cur-report-opt"><input type="radio" name="reportReason" value="false_info"> 허위 정보</label>
            <label class="pp-cur-report-opt"><input type="radio" name="reportReason" value="other"> 기타</label>
            <textarea class="pp-cur-report-detail" id="curReportDetail" placeholder="상세 내용 (선택)" maxlength="500"></textarea>
        </div>
        <button type="button" class="pp-btn pp-cur-report-submit" id="curReportSubmit">신고하기</button>
    </div>
</div>
@endauth

{{-- CTA --}}
<div class="pp-cur-cta" id="curCta">
    <button type="button" class="pp-btn pp-cur-cta__btn" id="curSaveBtn">이 장소들 내 핀픽에 담기</button>
    <p class="pp-cur-cta__hint" id="curGuestHint" style="display:none"></p>
</div>

{{-- Lightbox --}}
<div class="pp-cur-lb" id="curLb">
    <button type="button" class="pp-cur-lb__close" id="curLbClose">&times;</button>
    <button type="button" class="pp-cur-lb__nav pp-cur-lb__nav--prev" id="curLbPrev">&#8249;</button>
    <img class="pp-cur-lb__img" id="curLbImg" src="" alt="">
    <button type="button" class="pp-cur-lb__nav pp-cur-lb__nav--next" id="curLbNext">&#8250;</button>
    <div class="pp-cur-lb__counter" id="curLbCounter"></div>
</div>

{{-- Share sheet --}}
<div class="pp-share__select-sheet" id="curShareSheet">
    <div class="pp-share__select-backdrop" data-role="close"></div>
    <div class="pp-share__select-panel">
        <div class="pp-share__select-header">
            <h3>공유하기</h3>
            <button type="button" class="pp-share__select-close" data-role="close">✕</button>
        </div>
        <div class="pp-share__select-actions" style="gap:10px">
            <button type="button" class="pp-btn" id="curShareKakao">카카오톡 공유</button>
            <button type="button" class="pp-btn pp-btn--ghost" id="curShareCopy">링크 복사</button>
            <button type="button" class="pp-btn pp-btn--ghost" id="curShareNative" style="display:none">다른 앱으로 공유</button>
        </div>
    </div>
</div>

{{-- Guest sheet --}}
<div class="pp-share__select-sheet" id="curGuestSheet">
    <div class="pp-share__select-backdrop" data-role="close"></div>
    <div class="pp-share__select-panel">
        <div class="pp-share__select-header">
            <h3>게스트 저장 안내</h3>
            <button type="button" class="pp-share__select-close" data-role="close">✕</button>
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
            <button type="button" class="pp-share__select-close" data-role="close">✕</button>
        </div>
        <div class="pp-share__cat-list" id="curCatList">
            <label class="pp-share__cat-option">
                <input type="radio" name="cur_cat" value="__new__" checked>
                <span class="pp-share__cat-name">새 카테고리 만들기</span>
            </label>
            <div class="pp-share__cat-input-wrap" id="curCatInputWrap">
                <input type="text" id="curCatInput" class="pp-share__cat-input" maxlength="30" value="{{ $curation->title }}">
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
    $isOverseasCuration = $curation->places->contains(fn($p) => $p->is_overseas);
    $placesJson = $curation->places->map(function($p) {
        return [
            'id' => $p->id,
            'name' => $p->place_name,
            'address' => $p->address,
            'building_name' => $p->building_name,
            'phone' => $p->phone,
            'lat' => $p->latitude,
            'lng' => $p->longitude,
            'category_label' => $p->category_label,
            'thumbnail_url' => $p->thumb_url,
            'external_place_id' => $p->external_place_id,
            'naver_place_id' => $p->naver_place_id,
            'google_place_id' => $p->google_place_id,
            'opening_hours' => $p->opening_hours,
            'jibeon_address' => $p->jibeon_address,
            'is_overseas' => (bool) $p->is_overseas,
            'day_number' => $p->day_number,
            'source_channel' => $p->source_channel,
            'original_name' => $p->original_name ?? '',
            'detail_location' => $p->detail_location ?? '',
            'themes' => $p->themes ?? [],
        ];
    });

    // Server-side initial map center/zoom (480px shell, mid snap)
    $validCoords = $curation->places->filter(fn($p) => $p->latitude && $p->longitude);
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

        // Fixed layout constants (480px shell, mid snap = 52%)
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

        // Center offset: shift south so pins appear in visible area center
        $deltaY = $containerH / 2 - ($headerH + $visH / 2);
        $mpp = 156543.03392 * cos(deg2rad($midLat)) / pow(2, $initZoom);
        $latOff = $deltaY * $mpp / 111320;
        $initLat = $midLat - $latOff;
        $initLng = $midLng;
    }
@endphp
@if($isOverseasCuration)
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&language=ko"></script>
@else
<script src="https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId={{ config('services.naver_map.client_id') }}"></script>
@endif
<script src="https://t1.kakaocdn.net/kakao_js_sdk/2.7.4/kakao.min.js" integrity="sha384-DKYJZ8NLiK8MN4/C5P2dtSmLQ4KwPaoqAfyA/DQ/7hV+E1NASW+/MNlHjao0fzm" crossorigin="anonymous"></script>
<script>
(function() {
    const csrf = '{{ csrf_token() }}';
    const curationId = {{ $curation->id }};
    const curationType = '{{ $curation->type }}';
    const isAuth = {{ Auth::check() ? 'true' : 'false' }};
    const curTitle = @json($curation->title);
    const curDesc = @json($curation->description ?: '');
    const curUrl = location.href.split('?')[0];
    const curOgImage = @json($ogImage);
    const places = @json($placesJson);
    const userCats = @json($userCategories);
    const totalCount = places.length;
    const isOverseasCuration = {{ $isOverseasCuration ? 'true' : 'false' }};
    const selected = new Set();
    let reselectMode = false;
    let reselectLimit = 0;
    let savedSelection = null;
    let activeMarkerIdx = -1;
    let activeDay = 'all';
    let fullBounds = null;

    // ── Back ──
    document.getElementById('curBack').addEventListener('click', () => {
        if (history.length > 1 && document.referrer) history.back();
        else location.href = '/explore';
    });

    // ── Share ──
    const shareSheet = document.getElementById('curShareSheet');
    document.getElementById('curShareBtn').addEventListener('click', () => shareSheet.classList.add('is-open'));

    if (navigator.share) document.getElementById('curShareNative').style.display = '';
    document.getElementById('curShareNative').addEventListener('click', () => {
        navigator.share({ title: curTitle, text: curDesc, url: curUrl }).catch(() => {});
        shareSheet.classList.remove('is-open');
    });
    document.getElementById('curShareCopy').addEventListener('click', () => {
        navigator.clipboard.writeText(curUrl).then(() => showToast('링크가 복사됐어요'));
        shareSheet.classList.remove('is-open');
    });

    try { Kakao.init('{{ config("services.kakao.js_key") }}'); } catch(e) {}
    document.getElementById('curShareKakao').addEventListener('click', () => {
        try {
            Kakao.Share.sendDefault({
                objectType: 'feed',
                content: { title: curTitle, description: curDesc || '장소 ' + totalCount + '곳', imageUrl: curOgImage, link: { mobileWebUrl: curUrl, webUrl: curUrl } },
                buttons: [{ title: '장소 보기', link: { mobileWebUrl: curUrl, webUrl: curUrl } }],
            });
        } catch(e) { navigator.clipboard.writeText(curUrl).then(() => showToast('카카오톡 연결 실패 · 링크가 복사됐어요')); }
        shareSheet.classList.remove('is-open');
    });

    // ── Report ──
    const reportSheet = document.getElementById('curReportSheet');
    if (reportSheet) {
        document.getElementById('curReportBtn').addEventListener('click', () => reportSheet.classList.add('is-open'));
        reportSheet.querySelectorAll('[data-close-report]').forEach(el => el.addEventListener('click', () => reportSheet.classList.remove('is-open')));
        document.getElementById('curReportSubmit').addEventListener('click', () => {
            const reason = reportSheet.querySelector('input[name="reportReason"]:checked');
            if (!reason) { alert('신고 사유를 선택해주세요.'); return; }
            fetch('/c/' + curationId + '/report', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ reason: reason.value, detail: document.getElementById('curReportDetail').value }),
            }).then(r => r.json()).then(d => {
                if (d.success) { showToast('신고가 접수되었어요'); reportSheet.classList.remove('is-open'); }
            }).catch(() => alert('신고 실패'));
        });
    }

    // ── Container ref ──
    const container = document.querySelector('.pp-app');
    const containerH = () => container.offsetHeight;

    // ── Map ──
    let map = null, markers = [];
    const pinSize = 28;
    const HEADER_H = 66;
    const DEBUG_FIT = false;

    function pinHtml(num, hl) {
        const bg = hl ? '#FF6B00' : '#fff';
        const clr = hl ? '#fff' : '#C2410C';
        const bdr = hl ? '2px solid #fff' : '2px solid #FF6B00';
        const sc = hl ? 'transform:scale(1.15);box-shadow:0 4px 12px rgba(0,0,0,.35);' : '';
        return '<div style="background:'+bg+';color:'+clr+';width:'+pinSize+'px;height:'+pinSize+'px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;border:'+bdr+';box-shadow:0 2px 6px rgba(0,0,0,.2);transition:transform .15s;'+sc+'">'+num+'</div>';
    }

    function visiblePlaces() {
        if (activeDay === 'all') return places;
        const d = parseInt(activeDay);
        return places.filter(p => p.day_number === d);
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
        const offsetLat = lat - deltaY * mpp / 111320;
        if (isOverseasCuration) return new google.maps.LatLng(offsetLat, lng);
        return new naver.maps.LatLng(offsetLat, lng);
    }

    function fitMapToAll() {
        if (!map) return;
        const vp = visiblePlaces().filter(p => p.lat && p.lng);
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
        if (DEBUG_FIT) showDebugBox();
    }

    function showDebugBox() {
        let el = document.getElementById('dbgFitBox');
        if (!el) {
            el = document.createElement('div');
            el.id = 'dbgFitBox';
            el.style.cssText = 'position:absolute;left:0;right:0;z-index:999;pointer-events:none;border:2px solid rgba(255,0,0,.6);background:rgba(255,0,0,.06);';
            container.appendChild(el);
        }
        const vis = getVisibleMapArea();
        el.style.top = vis.top + 'px';
        el.style.height = vis.height + 'px';
        el.style.display = '';
    }

    const fitBtn = document.getElementById('curFitBtn');
    const mapEl = document.getElementById('curMap');
    const skelEl = document.getElementById('curMapSkel');

    function gPinIcon(num, hl) {
        const bg = hl ? '%23FF6B00' : '%23fff';
        const clr = hl ? '%23fff' : '%23C2410C';
        const stroke = hl ? '%23fff' : '%23FF6B00';
        const sz = hl ? 32 : 28;
        const fs = hl ? 14 : 12;
        const r = sz / 2;
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${sz}" height="${sz}"><circle cx="${r}" cy="${r}" r="${r-1.5}" fill="${bg}" stroke="${stroke}" stroke-width="2"/><text x="${r}" y="${r}" text-anchor="middle" dominant-baseline="central" font-size="${fs}" font-weight="700" font-family="sans-serif" fill="${clr}">${num}</text></svg>`;
        return {
            url: 'data:image/svg+xml,' + svg,
            scaledSize: new google.maps.Size(sz, sz),
            anchor: new google.maps.Point(sz/2, sz/2),
        };
    }

    if (isOverseasCuration && places.length && typeof google !== 'undefined') {
        map = new google.maps.Map(mapEl, {
            center: { lat: {{ $initLat }}, lng: {{ $initLng }} },
            zoom: {{ $initZoom }},
            disableDefaultUI: true,
            gestureHandling: 'greedy',
        });

        places.forEach((p, i) => {
            if (!p.lat || !p.lng) { markers.push(null); return; }
            const m = new google.maps.Marker({
                position: { lat: p.lat, lng: p.lng },
                map: map,
                icon: gPinIcon(i+1, false),
                zIndex: 100,
            });
            m.addListener('click', () => handlePinTap(i));
            markers.push(m);
        });
        map.addListener('click', () => resetHighlight());

        let initFitDone = false;
        function doInitFit() {
            if (initFitDone) return;
            initFitDone = true;
            fitMapToAll();
            mapEl.classList.add('is-ready');
            setTimeout(() => { if (skelEl) skelEl.style.display = 'none'; }, 250);
        }
        map.addListener('idle', doInitFit);
        setTimeout(doInitFit, 1200);

        window.addEventListener('resize', () => { if (map) setTimeout(fitMapToAll, 100); });

    } else if (!isOverseasCuration && places.length && typeof naver !== 'undefined') {
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
            const z = i === activeMarkerIdx ? 200 : (hl ? 150 : 100);
            if (isOverseasCuration) {
                m.setIcon(gPinIcon(i+1, hl));
                m.setZIndex(z);
            } else {
                m.setIcon({ content: pinHtml(i+1, hl), anchor: new naver.maps.Point(pinSize/2, pinSize/2) });
                m.setZIndex(z);
            }
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

    // Pull-down from scroll top → shrink to mid
    let scrollPullStart = 0;
    sheetScroll.addEventListener('touchstart', function(e) {
        if (sheetState !== 'full') return;
        if (sheetScroll.scrollTop <= 0) {
            scrollPullStart = e.touches[0].clientY;
        } else {
            scrollPullStart = 0;
        }
    }, { passive: true });

    sheetScroll.addEventListener('touchmove', function(e) {
        if (sheetState !== 'full' || !scrollPullStart) return;
        if (sheetScroll.scrollTop <= 0) {
            const dy = e.touches[0].clientY - scrollPullStart;
            if (dy > 50) {
                scrollPullStart = 0;
                setSheetState('mid');
            }
        } else {
            scrollPullStart = 0;
        }
    }, { passive: true });

    // Click on header → toggle peek/mid (suppress if touch drag just ended)
    sheetHdr.addEventListener('click', (e) => {
        if (isDragging || Date.now() - dragEndTime < 300) return;
        if (sheetState === 'peek') setSheetState('mid');
        else if (sheetState === 'mid') setSheetState('full');
    });

    // Mouse drag (desktop)
    handle.addEventListener('mousedown', (e) => { onDragStart(e); });
    document.addEventListener('mousemove', (e) => { if (isDragging) onDragMove(e); });
    document.addEventListener('mouseup', () => { if (isDragging) onDragEnd(); });

    // ── Day filter ──
    document.querySelectorAll('.pp-cur__day-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.pp-cur__day-btn').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            activeDay = btn.dataset.day;
            document.querySelectorAll('.pp-cur-card').forEach(card => {
                card.style.display = (activeDay === 'all' || card.dataset.day == activeDay) ? '' : 'none';
            });
            markers.forEach((m, i) => {
                if (!m) return;
                const vis = activeDay === 'all' || places[i].day_number == activeDay;
                if (isOverseasCuration) { m.setMap(vis ? map : null); }
                else { m.setVisible(vis); }
            });
            resetHighlight();
        });
    });

    // ── Card tap = selection toggle + map focus ──
    document.querySelectorAll('.pp-cur-card').forEach(card => {
        card.addEventListener('click', e => {
            if (e.target.closest('.pp-cur-card__photo[data-full]')) return;
            if (e.target.closest('.pp-cur-card__map-chip')) return;
            if (e.target.closest('.pp-cur-card__source a')) return;
            if (e.target.closest('.pp-cur-card__grev')) return;
            if (e.target.closest('.pp-cur-card__grev-feat')) return;
            if (e.target.closest('.pp-cur-card__grev-body')) return;
            toggleCardSelection(parseInt(card.dataset.idx));
        });
    });

    // ── Deep link ──
    const urlParams = new URLSearchParams(location.search);
    const placeParam = urlParams.get('place');
    if (placeParam) {
        const card = document.querySelector('.pp-cur-card[data-id="'+placeParam+'"]');
        if (card) {
            const idx = parseInt(card.dataset.idx);
            setTimeout(() => {
                toggleCardSelection(idx);
                setTimeout(() => card.scrollIntoView({behavior: 'smooth', block: 'center'}), 400);
            }, 800);
        }
    }

    // ── ?action=save auto-trigger ──
    const allCards = document.querySelectorAll('.pp-cur-card');
    if (urlParams.get('action') === 'save') {
        setTimeout(() => {
            places.forEach(p => selected.add(p.id));
            allCards.forEach(c => c.classList.add('is-selected'));
            syncAllPins();
            updateCtaText();
            updateSelectAllBtn();
            document.getElementById('curSaveBtn').click();
        }, 600);
    }

    // ── Selection ──
    const saveBtn = document.getElementById('curSaveBtn');
    const selectAllBtn = document.getElementById('curSelAll');

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

    // ── Guest save ──
    const GUEST_KEY = 'pinpick_guest_places';
    const guestSheet = document.getElementById('curGuestSheet');
    const catSheet = document.getElementById('curCatSheet');

    document.querySelectorAll('[data-role="close"]').forEach(el => {
        el.addEventListener('click', () => {
            guestSheet.classList.remove('is-open');
            catSheet.classList.remove('is-open');
            shareSheet.classList.remove('is-open');
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
                category_label: curTitle,
                phone: p.phone || '', opening_hours: p.opening_hours || [],
                address: p.jibeon_address || '', road_address: p.address || '',
                building_name: p.building_name || '', detail_location: p.detail_location || '',
                lat: p.lat, lng: p.lng,
                memo: curationType === 'course' && p.day_number ? 'Day ' + p.day_number : '',
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
        showDone(msg);
    }

    function showDone(msg) {
        showToast(msg);
        saveBtn.textContent = '핀픽에서 보기';
        saveBtn.className = 'pp-btn pp-cur-cta__btn pp-share__cta-btn--done';
        saveBtn.onclick = function() { location.href = '/'; };
        document.querySelectorAll('.pp-cur-card__add').forEach(b => { b.style.display = 'none'; });
        selectAllBtn.style.display = 'none';
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
            const res = await fetch('/c/' + curationId + '/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            const data = await res.json();
            if (data.success) {
                catSheet.classList.remove('is-open');
                let msg = data.saved + '개 장소가 저장됐어요!';
                if (data.skipped > 0) msg += ' (' + data.skipped + '개 중복 제외)';
                showDone(msg);
            } else { alert(data.error || '저장에 실패했어요'); }
        } catch(e) { alert('저장에 실패했어요'); }
        finally { btn.disabled = false; btn.textContent = '저장하기'; }
    });

    // ── Toast ──
    function showToast(msg) {
        let t = document.getElementById('ppCurToast');
        if (!t) { t = document.createElement('div'); t.id = 'ppCurToast'; t.className = 'pp-toast'; document.body.appendChild(t); }
        t.textContent = msg;
        t.classList.add('is-show');
        setTimeout(() => t.classList.remove('is-show'), 3000);
    }
    function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    // ── Lightbox ──
    const lb = document.getElementById('curLb');
    const lbImg = document.getElementById('curLbImg');
    const lbCounter = document.getElementById('curLbCounter');
    let lbPhotos = [], lbIdx = 0;

    window.openLightbox = function(el) {
        const card = el.closest('.pp-cur-card');
        lbPhotos = Array.from(card.querySelectorAll('.pp-cur-card__photo[data-full]')).map(p => p.dataset.full);
        lbIdx = Array.from(card.querySelectorAll('.pp-cur-card__photo[data-full]')).indexOf(el);
        if (lbIdx < 0) lbIdx = 0;
        showLbPhoto();
        lb.classList.add('is-open');
    };

    function showLbPhoto() {
        lbImg.src = lbPhotos[lbIdx];
        lbCounter.textContent = lbPhotos.length > 1 ? (lbIdx + 1) + ' / ' + lbPhotos.length : '';
        document.getElementById('curLbPrev').style.display = lbPhotos.length > 1 ? '' : 'none';
        document.getElementById('curLbNext').style.display = lbPhotos.length > 1 ? '' : 'none';
    }

    function closeLb() { lb.classList.remove('is-open'); lbImg.src = ''; }

    document.getElementById('curLbClose').addEventListener('click', closeLb);
    lb.addEventListener('click', function(e) { if (e.target === lb) closeLb(); });
    document.getElementById('curLbPrev').addEventListener('click', function(e) {
        e.stopPropagation();
        lbIdx = (lbIdx - 1 + lbPhotos.length) % lbPhotos.length;
        showLbPhoto();
    });
    document.getElementById('curLbNext').addEventListener('click', function(e) {
        e.stopPropagation();
        lbIdx = (lbIdx + 1) % lbPhotos.length;
        showLbPhoto();
    });
    document.addEventListener('keydown', function(e) {
        if (!lb.classList.contains('is-open')) return;
        if (e.key === 'Escape') closeLb();
        else if (e.key === 'ArrowLeft') { lbIdx = (lbIdx - 1 + lbPhotos.length) % lbPhotos.length; showLbPhoto(); }
        else if (e.key === 'ArrowRight') { lbIdx = (lbIdx + 1) % lbPhotos.length; showLbPhoto(); }
    });

    // Swipe on lightbox
    let lbTouchX = 0;
    lbImg.addEventListener('touchstart', function(e) { lbTouchX = e.touches[0].clientX; }, { passive: true });
    lbImg.addEventListener('touchend', function(e) {
        const dx = e.changedTouches[0].clientX - lbTouchX;
        if (Math.abs(dx) > 50 && lbPhotos.length > 1) {
            lbIdx = dx < 0 ? (lbIdx + 1) % lbPhotos.length : (lbIdx - 1 + lbPhotos.length) % lbPhotos.length;
            showLbPhoto();
        }
    });
})();
</script>
@endsection
