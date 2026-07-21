@extends('layouts.app')
@section('page_title', $curation->title . ' | 핀픽')
@section('app_class', 'pp-app--shared')

@section('og_title', $curation->title)
@section('og_description', $curation->description ?: ('장소 ' . $curation->places->count() . '곳 · 나만의 장소, 나만의 지도 핀픽'))
@section('og_image', $curation->cover_image ? asset('storage/' . $curation->cover_image) : asset('images/og-image.png'))

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
@endpush

@section('content')
{{-- 고정 헤더 --}}
<header class="pp-cur-header">
    <button type="button" class="pp-cur-header__back" id="curBack" aria-label="뒤로">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <a href="/" class="pp-cur-header__logo" aria-label="핀픽 홈">
        <img src="{{ asset('icon-192.png') }}" alt="" width="26" height="26">
    </a>
    <button type="button" class="pp-cur-header__share" id="curShareBtn" aria-label="공유">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
    </button>
</header>

<div class="pp-share" style="padding-top:50px">
    @if($curation->cover_image)
    <div class="pp-cur__cover">
        <img src="{{ asset('storage/' . $curation->cover_image) }}" alt="{{ $curation->title }}">
    </div>
    @endif

    <div class="pp-share__header">
        <h1 class="pp-share__title">{{ $curation->title }}</h1>
        @if($curation->region_label)
            <div class="pp-cur__regions">
                @foreach(array_map('trim', explode(',', $curation->region_label)) as $tag)
                    <span class="pp-cur__region-chip">{{ $tag }}</span>
                @endforeach
            </div>
        @endif
        @if($curation->description)
            <p class="pp-cur__desc">{{ $curation->description }}</p>
        @endif
        <p class="pp-share__meta">
            장소 {{ $curation->places->count() }}곳
            @if($curation->save_count > 0) · {{ number_format($curation->save_count) }}명이 담아갔어요 @endif
        </p>
    </div>

    <div class="pp-share__map-wrap">
        <div class="pp-share__map" id="shareMap"></div>
        <button type="button" class="pp-cur__fit-btn" id="curFitBtn" style="display:none">전체 보기</button>
    </div>

    @if($curation->type === 'course')
    <div class="pp-cur__days" id="curDays">
        @php $days = $curation->places->pluck('day_number')->filter()->unique()->sort(); @endphp
        <button type="button" class="pp-cur__day-btn is-active" data-day="all">전체</button>
        @foreach($days as $d)
        <button type="button" class="pp-cur__day-btn" data-day="{{ $d }}">Day {{ $d }}</button>
        @endforeach
    </div>
    @endif

    <div class="pp-share__list-header">
        <span class="pp-share__list-count">장소 {{ $curation->places->count() }}곳</span>
        <button type="button" class="pp-share__select-all" id="shareSelectAll">전체선택</button>
    </div>

    <div class="pp-share__list" id="shareList">
        @foreach($curation->places as $i => $place)
        @php $thumbUrl = $place->thumb_url; @endphp
        <div class="pp-share__card" data-idx="{{ $i }}" data-lat="{{ $place->latitude }}" data-lng="{{ $place->longitude }}" data-id="{{ $place->id }}" data-day="{{ $place->day_number }}">
            <div class="pp-share__card-thumb">
                @if($thumbUrl)
                    <img src="{{ $thumbUrl }}" alt="" loading="lazy">
                @else
                    <div class="pp-share__card-emoji">📍</div>
                @endif
                <span class="pp-share__card-num">{{ $i + 1 }}</span>
            </div>
            <div class="pp-share__card-body">
                <div class="pp-share__card-top">
                    <span class="pp-share__card-label">{{ $place->category_label }}</span>
                    @php
                        if ($place->is_overseas) {
                            $mapUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($place->place_name . ' ' . $place->address);
                        } else {
                            $mapUrl = 'https://map.naver.com/p/search/' . urlencode($place->place_name . ' ' . $place->address);
                        }
                    @endphp
                    <a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="pp-share__card-maplink" onclick="event.stopPropagation()">지도 ↗</a>
                </div>
                <h3 class="pp-share__card-name">{{ $place->place_name }}</h3>
                @if($place->address)
                    <p class="pp-share__card-addr">{{ $place->address }}</p>
                @endif
                @if($place->editor_note)
                    <p class="pp-cur__note">"{{ $place->editor_note }}"</p>
                @endif
                @if($place->source_channel)
                    <p class="pp-cur__source">
                        {{ $place->source_channel }}
                        @if($place->source_date) {{ $place->source_date->format('Y.m.d') }} 소개@endif
                        @if($place->source_url)
                            · <a href="{{ $place->source_url }}" target="_blank" rel="noopener" onclick="event.stopPropagation()">원본 보기 ↗</a>
                        @endif
                    </p>
                @endif
                @if($curation->type === 'course' && $place->day_number)
                    <span class="pp-cur__day-label">Day {{ $place->day_number }}</span>
                @endif
            </div>
            <button type="button" class="pp-share__card-add" data-place-id="{{ $place->id }}" aria-label="담기">
                <svg class="pp-share__card-add-icon" width="22" height="22" viewBox="0 0 22 22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="11" y1="5" x2="11" y2="17"/><line x1="5" y1="11" x2="17" y2="11"/></svg>
                <svg class="pp-share__card-check-icon" width="22" height="22" viewBox="0 0 22 22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 11 10 15 16 7"/></svg>
            </button>
        </div>
        @endforeach
    </div>

    <div class="pp-share__cta-spacer"></div>
</div>

<div class="pp-share__cta" id="shareCta">
    <button type="button" class="pp-btn pp-share__cta-btn" id="shareSaveBtn">이 장소들 내 핀픽에 담기</button>
    <p class="pp-share__guest-hint" id="shareGuestHint" style="display:none"></p>
</div>

{{-- 공유 바텀시트 --}}
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

{{-- 게스트 저장 안내 --}}
<div class="pp-share__select-sheet" id="shareGuestSheet">
    <div class="pp-share__select-backdrop" data-role="close"></div>
    <div class="pp-share__select-panel">
        <div class="pp-share__select-header">
            <h3>게스트 저장 안내</h3>
            <button type="button" class="pp-share__select-close" data-role="close">✕</button>
        </div>
        <div class="pp-share__select-info" id="shareGuestInfo"></div>
        <div class="pp-share__select-actions">
            <button type="button" class="pp-btn" id="shareGuestPick">직접 고르기</button>
            <button type="button" class="pp-btn pp-btn--ghost" id="shareGuestLogin">로그인하고 전부 저장</button>
        </div>
    </div>
</div>

{{-- 카테고리 선택 바텀시트 --}}
<div class="pp-share__cat-sheet" id="shareCatSheet">
    <div class="pp-share__select-backdrop" data-role="close"></div>
    <div class="pp-share__select-panel">
        <div class="pp-share__select-header">
            <h3>저장할 카테고리 선택</h3>
            <button type="button" class="pp-share__select-close" data-role="close">✕</button>
        </div>
        <div class="pp-share__cat-list" id="shareCatList">
            <label class="pp-share__cat-option">
                <input type="radio" name="share_cat" value="__new__" checked>
                <span class="pp-share__cat-name">새 카테고리 만들기</span>
            </label>
            <div class="pp-share__cat-input-wrap" id="shareCatInputWrap">
                <input type="text" id="shareCatInput" class="pp-share__cat-input" maxlength="30" value="{{ $curation->title }}">
                <div class="pp-share__cat-dup-hint" id="shareCatDupHint"></div>
            </div>
            <label class="pp-share__cat-option">
                <input type="radio" name="share_cat" value="__existing__">
                <span class="pp-share__cat-name">기존 카테고리에 저장</span>
            </label>
            <div class="pp-share__cat-existing" id="shareCatExisting" style="display:none"></div>
        </div>
        <div class="pp-share__select-actions">
            <button type="button" class="pp-btn" id="shareCatSaveBtn">저장하기</button>
        </div>
    </div>
</div>

@php
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
            'is_overseas' => (bool) $p->is_overseas,
            'day_number' => $p->day_number,
            'source_channel' => $p->source_channel,
        ];
    });
@endphp
<script src="https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId={{ config('services.naver_map.client_id') }}"></script>
<script src="https://t1.kakaocdn.net/kakao_js_sdk/2.7.4/kakao.min.js" integrity="sha384-DKYJZ8NLiK8MN4/C5P2dtSmLQ4KwPaoqAfyA/DQ/7hV+E1NASW+/MNlHjao0fzm" crossorigin="anonymous"></script>
<script>
(function() {
    const csrf = '{{ csrf_token() }}';
    const curationId = {{ $curation->id }};
    const curationType = '{{ $curation->type }}';
    const isAuth = {{ Auth::check() ? 'true' : 'false' }};
    const curTitle = @json($curation->title);
    const curDesc = @json($curation->description ?: '');
    const curUrl = location.href;
    const curOgImage = @json($curation->cover_image ? asset('storage/' . $curation->cover_image) : asset('images/og-image.png'));
    const places = @json($placesJson);
    const userCats = @json($userCategories);
    const totalCount = places.length;
    const selected = new Set();
    let activeMarkerIdx = -1;
    let activeDay = 'all';
    let fullBounds = null;

    // --- Header ---
    document.getElementById('curBack').addEventListener('click', () => {
        if (history.length > 1 && document.referrer) history.back();
        else location.href = '/explore';
    });

    // --- Share ---
    const shareSheet = document.getElementById('curShareSheet');
    document.getElementById('curShareBtn').addEventListener('click', () => shareSheet.classList.add('is-open'));

    if (navigator.share) document.getElementById('curShareNative').style.display = '';
    document.getElementById('curShareNative').addEventListener('click', () => {
        navigator.share({ title: curTitle, text: curDesc, url: curUrl }).catch(() => {});
        shareSheet.classList.remove('is-open');
    });

    document.getElementById('curShareCopy').addEventListener('click', () => {
        navigator.clipboard.writeText(curUrl).then(() => showDone('링크가 복사됐어요'));
        shareSheet.classList.remove('is-open');
    });

    try {
        Kakao.init('{{ config("services.kakao.js_key") }}');
    } catch(e) {}
    document.getElementById('curShareKakao').addEventListener('click', () => {
        try {
            Kakao.Share.sendDefault({
                objectType: 'feed',
                content: { title: curTitle, description: curDesc || '장소 ' + totalCount + '곳', imageUrl: curOgImage, link: { mobileWebUrl: curUrl, webUrl: curUrl } },
                buttons: [{ title: '장소 보기', link: { mobileWebUrl: curUrl, webUrl: curUrl } }],
            });
        } catch(e) { navigator.clipboard.writeText(curUrl).then(() => showDone('카카오톡 연결 실패 · 링크가 복사됐어요')); }
        shareSheet.classList.remove('is-open');
    });

    // --- Map ---
    let map = null, markers = [];
    const pinSize = 28;
    function pinHtml(num, highlight) {
        const bg = highlight ? '#e67e22' : 'var(--pp-primary,#2b211e)';
        const scale = highlight ? 'transform:scale(1.25);' : '';
        return '<div style="background:'+bg+';color:#fff;width:'+pinSize+'px;height:'+pinSize+'px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3);transition:transform .15s;'+scale+'">'+num+'</div>';
    }

    const fitBtn = document.getElementById('curFitBtn');

    if (places.length && typeof naver !== 'undefined') {
        fullBounds = new naver.maps.LatLngBounds();
        places.forEach(p => { if (p.lat && p.lng) fullBounds.extend(new naver.maps.LatLng(p.lat, p.lng)); });
        map = new naver.maps.Map('shareMap', {
            center: fullBounds.getCenter(),
            zoomControl: false, scaleControl: false, logoControl: false, mapDataControl: false,
        });
        if (places.filter(p => p.lat && p.lng).length === 1) {
            map.setCenter(new naver.maps.LatLng(places.find(p => p.lat && p.lng).lat, places.find(p => p.lat && p.lng).lng));
            map.setZoom(15);
        } else {
            map.fitBounds(fullBounds, { top: 40, right: 40, bottom: 40, left: 40 });
        }
        places.forEach((p, i) => {
            if (!p.lat || !p.lng) { markers.push(null); return; }
            const m = new naver.maps.Marker({
                position: new naver.maps.LatLng(p.lat, p.lng),
                map: map,
                icon: { content: pinHtml(i+1, false), anchor: new naver.maps.Point(pinSize/2, pinSize/2) },
                zIndex: 100,
            });
            naver.maps.Event.addListener(m, 'click', () => handleCardTap(i));
            markers.push(m);
        });
        naver.maps.Event.addListener(map, 'click', () => resetMapView());
    }

    function highlightPin(idx) {
        if (activeMarkerIdx >= 0 && markers[activeMarkerIdx]) {
            markers[activeMarkerIdx].setIcon({ content: pinHtml(activeMarkerIdx+1, false), anchor: new naver.maps.Point(pinSize/2, pinSize/2) });
            markers[activeMarkerIdx].setZIndex(100);
        }
        if (idx >= 0 && markers[idx]) {
            markers[idx].setIcon({ content: pinHtml(idx+1, true), anchor: new naver.maps.Point(pinSize/2, pinSize/2) });
            markers[idx].setZIndex(200);
        }
        activeMarkerIdx = idx;
        fitBtn.style.display = idx >= 0 ? '' : 'none';
    }

    function resetMapView() {
        highlightPin(-1);
        document.querySelectorAll('.pp-share__card').forEach(c => c.classList.remove('is-focused'));
        if (map && fullBounds) {
            if (places.filter(p => p.lat && p.lng).length === 1) {
                map.setCenter(fullBounds.getCenter());
                map.setZoom(15);
            } else {
                map.fitBounds(fullBounds, { top: 40, right: 40, bottom: 40, left: 40 });
            }
        }
    }

    fitBtn.addEventListener('click', () => resetMapView());

    function handleCardTap(idx) {
        if (activeMarkerIdx === idx) { resetMapView(); return; }
        const p = places[idx];
        if (!map || !p.lat || !p.lng) return;
        map.panTo(new naver.maps.LatLng(p.lat, p.lng), { duration: 300 });
        map.setZoom(16);
        highlightPin(idx);
        document.querySelectorAll('.pp-share__card').forEach(c => c.classList.remove('is-focused'));
        const card = document.querySelector('.pp-share__card[data-idx="'+idx+'"]');
        if (card) {
            card.classList.add('is-focused');
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    // --- Day filter ---
    document.querySelectorAll('.pp-cur__day-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.pp-cur__day-btn').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            activeDay = btn.dataset.day;
            document.querySelectorAll('.pp-share__card').forEach(card => {
                card.style.display = (activeDay === 'all' || card.dataset.day == activeDay) ? '' : 'none';
            });
            markers.forEach((m, i) => {
                if (!m) return;
                m.setVisible(activeDay === 'all' || places[i].day_number == activeDay);
            });
            resetMapView();
        });
    });

    // --- Card tap ---
    document.querySelectorAll('.pp-share__card-body, .pp-share__card-thumb').forEach(el => {
        el.addEventListener('click', () => {
            const idx = parseInt(el.closest('.pp-share__card').dataset.idx);
            handleCardTap(idx);
        });
    });

    // --- Selection ---
    const saveBtn = document.getElementById('shareSaveBtn');
    const selectAllBtn = document.getElementById('shareSelectAll');
    const allCards = document.querySelectorAll('.pp-share__card');

    document.querySelectorAll('.pp-share__card-add').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const id = parseInt(btn.dataset.placeId);
            const card = btn.closest('.pp-share__card');
            if (selected.has(id)) { selected.delete(id); card.classList.remove('is-selected'); }
            else { selected.add(id); card.classList.add('is-selected'); }
            updateCtaText();
            updateSelectAllBtn();
        });
    });

    selectAllBtn.addEventListener('click', () => {
        if (selected.size === totalCount) {
            selected.clear();
            allCards.forEach(c => c.classList.remove('is-selected'));
        } else {
            places.forEach(p => selected.add(p.id));
            allCards.forEach(c => c.classList.add('is-selected'));
        }
        updateCtaText();
        updateSelectAllBtn();
    });

    function updateSelectAllBtn() { selectAllBtn.textContent = selected.size === totalCount ? '선택해제' : '전체선택'; }
    function updateCtaText() { saveBtn.textContent = selected.size > 0 ? selected.size + '개 장소 담기' : '이 장소들 내 핀픽에 담기'; }
    function getSelectedIds() { return selected.size > 0 ? Array.from(selected) : places.map(p => p.id); }

    saveBtn.addEventListener('click', () => { if (isAuth) openCategorySheet(); else handleGuestSave(); });

    // --- Guest save ---
    const GUEST_KEY = 'pinpick_guest_places';
    const guestSheet = document.getElementById('shareGuestSheet');
    const catSheet = document.getElementById('shareCatSheet');

    document.querySelectorAll('[data-role="close"]').forEach(el => {
        el.addEventListener('click', () => {
            guestSheet.classList.remove('is-open');
            catSheet.classList.remove('is-open');
            shareSheet.classList.remove('is-open');
        });
    });

    function getGuestRemaining() { return 5 - JSON.parse(localStorage.getItem(GUEST_KEY) || '[]').length; }
    function updateGuestHint() {
        const hint = document.getElementById('shareGuestHint');
        if (!hint || isAuth) return;
        hint.textContent = '비로그인은 5개까지 저장돼요 · 남은 저장 ' + getGuestRemaining() + '개';
        hint.style.display = '';
    }

    function handleGuestSave() {
        const remaining = getGuestRemaining();
        const toSave = places.filter(p => getSelectedIds().includes(p.id));
        if (remaining <= 0) {
            document.getElementById('shareGuestInfo').textContent = '비로그인 저장 5개를 모두 사용했어요.\n로그인하면 전부 저장돼요';
            document.getElementById('shareGuestPick').style.display = 'none';
            guestSheet.classList.add('is-open');
            return;
        }
        if (toSave.length > remaining) {
            document.getElementById('shareGuestInfo').textContent = '비로그인은 ' + remaining + '개까지 더 저장할 수 있어요';
            document.getElementById('shareGuestPick').textContent = '저장할 ' + remaining + '개 직접 고르기';
            document.getElementById('shareGuestPick').style.display = '';
            guestSheet.classList.add('is-open');
            return;
        }
        doGuestSave(toSave);
    }

    function doGuestSave(toSave) {
        const list = JSON.parse(localStorage.getItem(GUEST_KEY) || '[]');
        toSave.forEach(p => {
            list.unshift({
                id: 'g' + Date.now() + Math.random().toString(36).slice(2,6),
                name: p.name, category_id: null, category_name: curTitle, category_icon: '📌',
                phone: p.phone || '', road_address: p.address || '', address: '',
                lat: p.lat, lng: p.lng, memo: curationType === 'course' && p.day_number ? 'Day ' + p.day_number : '',
                status: 'planned', visited_at: '', is_overseas: p.is_overseas, created_at: Date.now(),
            });
        });
        localStorage.setItem(GUEST_KEY, JSON.stringify(list));
        showDone(toSave.length + '개 장소가 저장됐어요!');
    }

    document.getElementById('shareGuestLogin').addEventListener('click', () => {
        location.href = '/login?redirect=' + encodeURIComponent(location.pathname);
    });
    if (!isAuth) updateGuestHint();

    // --- Category sheet ---
    function openCategorySheet() {
        const catExisting = document.getElementById('shareCatExisting');
        catExisting.innerHTML = '';
        if (userCats.length) {
            userCats.forEach(c => {
                const label = document.createElement('label');
                label.className = 'pp-share__cat-option';
                label.innerHTML = '<input type="radio" name="share_cat_existing" value="'+c.id+'"><span class="pp-share__cat-name">'+(c.icon||'📌')+' '+escHtml(c.name)+'</span>';
                catExisting.appendChild(label);
            });
        }
        document.querySelectorAll('input[name="share_cat"]').forEach(r => {
            r.addEventListener('change', () => {
                document.getElementById('shareCatInputWrap').style.display = r.value === '__new__' ? '' : 'none';
                catExisting.style.display = r.value === '__new__' ? 'none' : '';
            });
        });
        catSheet.classList.add('is-open');
    }

    document.getElementById('shareCatSaveBtn').addEventListener('click', async () => {
        const mode = document.querySelector('input[name="share_cat"]:checked').value;
        let body = { place_ids: getSelectedIds() };
        if (mode === '__new__') {
            const name = document.getElementById('shareCatInput').value.trim();
            if (!name) { alert('카테고리 이름을 입력하세요'); return; }
            body.new_category_name = name;
        } else {
            const sel = document.querySelector('input[name="share_cat_existing"]:checked');
            if (!sel) { alert('카테고리를 선택하세요'); return; }
            body.category_id = parseInt(sel.value);
        }
        const btn = document.getElementById('shareCatSaveBtn');
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

    // --- Toast ---
    function showDone(msg) {
        let t = document.getElementById('ppShareToast');
        if (!t) { t = document.createElement('div'); t.id = 'ppShareToast'; t.className = 'pp-toast'; document.body.appendChild(t); }
        t.textContent = msg;
        t.classList.add('is-show');
        setTimeout(() => t.classList.remove('is-show'), 3000);
    }
    function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
})();
</script>
@endsection
