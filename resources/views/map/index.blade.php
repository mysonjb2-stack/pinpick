@extends('layouts.app')
@section('title', '내 지도')
@section('app_class', 'pp-app--map')

@section('content')
<div class="pp-map-wrap">
    <div class="pp-map-scope" id="ppScope">
        <button type="button" class="yg-segtab__btn{{ $defaultScope === 'domestic' ? ' is-active' : '' }}" data-scope="domestic">국내장소</button>
        <button type="button" class="yg-segtab__btn{{ $defaultScope === 'overseas' ? ' is-active' : '' }}" data-scope="overseas">해외장소</button>
    </div>
    <div class="pp-map-tabs" id="ppMapTabs">
        <button type="button" class="pp-map-tab is-active" data-cat="all">전체</button>
        @foreach($categories as $c)
            <button type="button" class="pp-map-tab" data-cat="{{ $c->id }}">
                <span class="pp-map-tab__dot" data-cat-dot="{{ $c->id }}"></span>{{ $c->name }}
            </button>
        @endforeach
    </div>
    <div id="pp-map-naver" class="pp-map"{{ $defaultScope === 'domestic' ? '' : ' hidden' }}></div>
    <div id="pp-map-google" class="pp-map"{{ $defaultScope === 'overseas' ? '' : ' hidden' }}></div>
    <div class="pp-map-credit" id="ppMapCredit">지도: NAVER</div>
    <button type="button" class="pp-map-locate" id="ppMapLocate" aria-label="현재 위치">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
    </button>
</div>

<div class="pp-map-regions" id="ppRegions" hidden>
    <div class="pp-map-regions__title" id="ppRegionsTitle"></div>
    <div class="pp-map-regions__list" id="ppRegionsList"></div>
</div>

<div class="pp-map-sheet" id="ppMapSheet" hidden aria-hidden="true">
    <button type="button" class="pp-map-sheet__close" id="ppMapSheetClose" aria-label="닫기">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" width="18" height="18"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>
    <div class="pp-map-sheet__top">
        <img class="pp-map-sheet__thumb" id="ppMsThumb" alt="" hidden>
        <div class="pp-map-sheet__body">
            <div class="pp-map-sheet__name" id="ppMsName"></div>
            <div class="pp-map-sheet__meta" id="ppMsMeta"></div>
        </div>
        <span class="pp-badge" id="ppMsBadge"></span>
    </div>
    <div class="pp-map-sheet__actions">
        <a href="#" id="ppMsCall" class="pp-map-sheet__btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.35 1.85.59 2.81.72A2 2 0 0 1 22 16.92z"/></svg>
            전화
        </a>
        <button type="button" id="ppMsRoute" class="pp-map-sheet__btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M3 11l18-8-8 18-2-8-8-2z"/></svg>
            길찾기
        </button>
        <a href="#" id="ppMsDetail" class="pp-map-sheet__btn pp-map-sheet__btn--primary">상세보기</a>
    </div>
</div>
@endsection

@push('head')
@if($naverClientId)
<script src="https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId={{ $naverClientId }}"></script>
@endif
@if($googleMapsKey)
<script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&loading=async" async defer></script>
@endif
@endpush

@push('scripts')
<script>
(function() {
    const CATEGORY_ORDER = @json($categories->pluck('id')->values());
    const CATEGORY_NAMES = @json($categories->pluck('name', 'id'));
    const USER_NAME = @json(auth()->user()?->name ?? '내');
    const FIXED_PALETTE = ['#C96A5D','#C9A13A','#8E9A57','#5E9B8C','#6F93C9','#9A7AAE'];
    const FALLBACK_PALETTE = ['#D98A7A','#E0B964','#A7B578','#7FB5A8','#8EAFD9','#B898C6','#C98A8A','#A7926B','#6F9FA7','#A39CB8'];
    function catColor(id) {
        const n = Number(id);
        const idx = CATEGORY_ORDER.indexOf(n);
        if (idx < 0) return '#888';
        if (idx < FIXED_PALETTE.length) return FIXED_PALETTE[idx];
        return FALLBACK_PALETTE[(idx - FIXED_PALETTE.length) % FALLBACK_PALETTE.length];
    }
    document.querySelectorAll('[data-cat-dot]').forEach(el => {
        el.style.backgroundColor = catColor(el.dataset.catDot);
    });

    const places = {!! json_encode($places->map(function($p){
        return [
            'id' => $p->id,
            'name' => $p->name,
            'lat' => (float) $p->lat,
            'lng' => (float) $p->lng,
            'category_id' => $p->category_id,
            'category_name' => optional($p->category)->name ?? '기타',
            'address' => $p->road_address ?: ($p->address ?: ''),
            'phone' => $p->phone ?: '',
            'status' => $p->status,
            'is_overseas' => (bool) $p->is_overseas,
            'thumb_url' => optional($p->images->first())->thumb_url ?: '',
        ];
    })->values()) !!};

    function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    function pinHtml(label, count, color, active) {
        const safe = label.length > 8 ? label.slice(0, 8) + '…' : label;
        const badge = count > 1 ? `<span class="pp-pin__count">${count}</span>` : '';
        const cls = active ? 'pp-pin is-active' : 'pp-pin';
        return `<div class="${cls}"><div class="pp-pin__label"><span class="pp-pin__label-dot" style="background:${color}"></span>${escapeHtml(safe)}${badge}</div><div class="pp-pin__tail"></div></div>`;
    }

    const PROVINCE_MAP = {
        '서울특별시': '서울', '서울시': '서울', '서울': '서울',
        '부산광역시': '부산', '부산시': '부산', '부산': '부산',
        '대구광역시': '대구', '대구시': '대구', '대구': '대구',
        '인천광역시': '인천', '인천시': '인천', '인천': '인천',
        '광주광역시': '광주광역시', '광주시': '광주광역시',
        '대전광역시': '대전', '대전시': '대전', '대전': '대전',
        '울산광역시': '울산', '울산시': '울산', '울산': '울산',
        '세종특별자치시': '세종', '세종시': '세종', '세종': '세종',
        '경기도': '경기', '경기': '경기',
        '강원도': '강원', '강원특별자치도': '강원', '강원': '강원',
        '충청북도': '충북', '충북': '충북',
        '충청남도': '충남', '충남': '충남',
        '전라북도': '전북', '전북특별자치도': '전북', '전북': '전북',
        '전라남도': '전남', '전남': '전남',
        '경상북도': '경북', '경북': '경북',
        '경상남도': '경남', '경남': '경남',
        '제주특별자치도': '제주', '제주도': '제주', '제주': '제주',
    };
    function regionOf(p) {
        const addr = (p.address || '').trim();
        if (!addr) return '';
        if (p.is_overseas) {
            let last = addr;
            if (addr.includes(',')) {
                const parts = addr.split(',').map(s => s.trim()).filter(Boolean);
                last = parts[parts.length - 1] || '';
            }
            if (/\d/.test(last)) {
                const words = last.split(/\s+/).filter(Boolean);
                for (let i = words.length - 1; i >= 0; i--) {
                    if (!/^[\d\-]+$/.test(words[i])) return words[i];
                }
            }
            return last;
        }
        const first = addr.split(/\s+/)[0] || '';
        return PROVINCE_MAP[first] || '';
    }

    function groupByCoord(items) {
        const buckets = new Map();
        items.forEach(p => {
            const key = p.lat.toFixed(5) + ',' + p.lng.toFixed(5);
            if (!buckets.has(key)) buckets.set(key, []);
            buckets.get(key).push(p);
        });
        return Array.from(buckets.values());
    }

    // 내비게이션 타입 감지: 새 페이지 이동/리로드는 fresh, 브라우저 back/forward는 이전 상태 유지
    const _navEntry = (performance.getEntriesByType('navigation') || [])[0];
    const _navType = _navEntry ? _navEntry.type : 'navigate';
    const isFreshNav = (_navType === 'navigate' || _navType === 'reload');

    // fresh 내비: 이전 뷰포트/카테고리 필터 초기화 → 현재 위치 기준으로 다시 보여주기 위함
    if (isFreshNav) {
        sessionStorage.removeItem('pp_map_naver_view');
        sessionStorage.removeItem('pp_map_google_view');
        sessionStorage.removeItem('pp_map_cat');
    }

    const _savedScope = sessionStorage.getItem('pp_map_scope');
    let currentScope = (_savedScope === 'domestic' || _savedScope === 'overseas') ? _savedScope : @json($defaultScope);
    let currentCat = sessionStorage.getItem('pp_map_cat') || 'all';

    // ===== Naver (국내) =====
    let nMap = null, nMarkers = [], nActive = null;
    function initNaver() {
        if (nMap) return;
        if (typeof naver === 'undefined' || !naver.maps) return;
        const domestic = places.filter(p => !p.is_overseas);
        const c = domestic.length
            ? new naver.maps.LatLng(domestic[0].lat, domestic[0].lng)
            : new naver.maps.LatLng(37.5665, 126.9780);
        const savedN = JSON.parse(sessionStorage.getItem('pp_map_naver_view') || 'null');
        const nCenter = savedN ? new naver.maps.LatLng(savedN.lat, savedN.lng) : c;
        const nZoom = savedN ? savedN.zoom : 13;
        nMap = new naver.maps.Map('pp-map-naver', {
            center: nCenter, zoom: nZoom,
            mapTypeControl: false, zoomControl: false,
            scaleControl: false, logoControl: false, mapDataControl: false,
        });
        naver.maps.Event.addListener(nMap, 'click', closeSheet);
        naver.maps.Event.addListener(nMap, 'idle', () => {
            const ct = nMap.getCenter();
            sessionStorage.setItem('pp_map_naver_view', JSON.stringify({ lat: ct.lat(), lng: ct.lng(), zoom: nMap.getZoom() }));
        });
    }
    function clearNaver() { nMarkers.forEach(m => m.setMap(null)); nMarkers = []; nActive = null; }
    function renderNaver(cat) {
        if (!nMap) return;
        clearNaver();
        const filtered = places.filter(p => !p.is_overseas && (cat === 'all' || String(p.category_id) === String(cat)));
        const groups = groupByCoord(filtered);
        groups.forEach(group => {
            const head = group[0];
            const label = group.length > 1 ? `${head.name} 외 ${group.length - 1}` : head.name;
            const color = catColor(head.category_id);
            const marker = new naver.maps.Marker({
                position: new naver.maps.LatLng(head.lat, head.lng),
                map: nMap,
                icon: { content: pinHtml(label, group.length, color, false), anchor: new naver.maps.Point(0, 0) },
                title: head.name,
            });
            marker._label = label; marker._count = group.length; marker._color = color;
            naver.maps.Event.addListener(marker, 'click', () => {
                if (group.length === 1) { setNaverActive(marker); openSheet(head); }
                else { nMap.setCenter(marker.getPosition()); nMap.setZoom(Math.min(nMap.getZoom() + 2, 19)); }
            });
            nMarkers.push(marker);
        });
    }
    function setNaverActive(m) {
        if (nActive && nActive !== m) {
            nActive.setIcon({ content: pinHtml(nActive._label, nActive._count, nActive._color, false), anchor: new naver.maps.Point(0, 0) });
        }
        if (m) m.setIcon({ content: pinHtml(m._label, m._count, m._color, true), anchor: new naver.maps.Point(0, 0) });
        nActive = m;
    }

    // ===== Google (해외) =====
    let gMap = null, gMarkers = [], gActive = null, HtmlOverlay = null;
    let _pinClickGuard = false;
    function initGoogle() {
        if (gMap) return;
        if (typeof google === 'undefined' || !google.maps) return;
        const overseas = places.filter(p => p.is_overseas);
        const c = overseas.length ? { lat: overseas[0].lat, lng: overseas[0].lng } : { lat: 35.6762, lng: 139.6503 };
        const savedG = JSON.parse(sessionStorage.getItem('pp_map_google_view') || 'null');
        const gCenter = savedG ? { lat: savedG.lat, lng: savedG.lng } : c;
        const gZoom = savedG ? savedG.zoom : 13;
        gMap = new google.maps.Map(document.getElementById('pp-map-google'), {
            center: gCenter, zoom: gZoom,
            mapTypeControl: false, streetViewControl: false, fullscreenControl: false, zoomControl: false,
            clickableIcons: false,
        });
        gMap.addListener('click', () => { if (!_pinClickGuard) closeSheet(); });
        gMap.addListener('idle', () => {
            const ct = gMap.getCenter();
            sessionStorage.setItem('pp_map_google_view', JSON.stringify({ lat: ct.lat(), lng: ct.lng(), zoom: gMap.getZoom() }));
        });

        HtmlOverlay = class extends google.maps.OverlayView {
            constructor(position, html, onClick) {
                super();
                this._pos = position;
                this.el = document.createElement('div');
                this.el.style.position = 'absolute';
                this.el.innerHTML = html;
                this.el.addEventListener('click', (e) => { e.stopPropagation(); e.preventDefault(); _pinClickGuard = true; setTimeout(() => { _pinClickGuard = false; }, 1500); onClick && onClick(); });
                this.el.addEventListener('touchstart', (e) => { e.stopPropagation(); });
                this.el.addEventListener('touchend', (e) => { e.stopPropagation(); });
            }
            setHtml(html) { this.el.innerHTML = html; }
            onAdd() { this.getPanes().overlayMouseTarget.appendChild(this.el); }
            draw() {
                const proj = this.getProjection();
                if (!proj) return;
                const pt = proj.fromLatLngToDivPixel(this._pos);
                if (!pt) return;
                this.el.style.left = pt.x + 'px';
                this.el.style.top = pt.y + 'px';
            }
            onRemove() { if (this.el.parentNode) this.el.parentNode.removeChild(this.el); }
            setMapSafe(m) { this.setMap(m); }
        };
    }
    function clearGoogle() { gMarkers.forEach(m => m.setMap(null)); gMarkers = []; gActive = null; }
    function renderGoogle(cat) {
        if (!gMap || !HtmlOverlay) return;
        clearGoogle();
        const filtered = places.filter(p => p.is_overseas && (cat === 'all' || String(p.category_id) === String(cat)));
        const groups = groupByCoord(filtered);
        groups.forEach(group => {
            const head = group[0];
            const label = group.length > 1 ? `${head.name} 외 ${group.length - 1}` : head.name;
            const color = catColor(head.category_id);
            const pos = new google.maps.LatLng(head.lat, head.lng);
            const overlay = new HtmlOverlay(pos, pinHtml(label, group.length, color, false), () => {
                if (group.length === 1) { setGoogleActive(overlay); openSheet(head); }
                else { gMap.setCenter(pos); gMap.setZoom(Math.min(gMap.getZoom() + 2, 19)); }
            });
            overlay._label = label; overlay._count = group.length; overlay._color = color;
            overlay.setMap(gMap);
            gMarkers.push(overlay);
        });
    }
    function setGoogleActive(o) {
        if (gActive && gActive !== o) {
            gActive.setHtml(pinHtml(gActive._label, gActive._count, gActive._color, false));
        }
        if (o) o.setHtml(pinHtml(o._label, o._count, o._color, true));
        gActive = o;
    }

    // ===== Scope / Category tabs =====
    const naverEl = document.getElementById('pp-map-naver');
    const googleEl = document.getElementById('pp-map-google');

    function waitForGoogle(cb, tries) {
        tries = tries || 0;
        if (typeof google !== 'undefined' && google.maps && google.maps.OverlayView) { cb(); return; }
        if (tries > 100) { console.warn('Google Maps 로드 실패'); return; }
        setTimeout(() => waitForGoogle(cb, tries + 1), 100);
    }

    const creditEl = document.getElementById('ppMapCredit');

    // fresh 내비일 때 최초 1회 현재 위치로 재중앙화 (국내 scope에서만 자동 시도 — 해외 scope는 geolocation이 엉뚱한 곳을 찍을 수 있어 보류)
    let _initialGeolocated = false;
    function tryInitialGeolocate() {
        if (_initialGeolocated || !isFreshNav) return;
        if (!navigator.geolocation) { _initialGeolocated = true; return; }
        _initialGeolocated = true;
        navigator.geolocation.getCurrentPosition((pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            if (currentScope === 'domestic' && nMap && typeof naver !== 'undefined') {
                nMap.setCenter(new naver.maps.LatLng(lat, lng));
                nMap.setZoom(13);
            } else if (currentScope === 'overseas' && gMap && typeof google !== 'undefined') {
                gMap.setCenter({ lat, lng });
                gMap.setZoom(13);
            }
        }, () => {}, { enableHighAccuracy: false, timeout: 5000, maximumAge: 300000 });
    }

    function applyScope(scope) {
        currentScope = scope;
        sessionStorage.setItem('pp_map_scope', scope);
        naverEl.hidden = scope !== 'domestic';
        googleEl.hidden = scope !== 'overseas';
        creditEl.textContent = scope === 'domestic' ? '지도: NAVER' : '지도: Google';
        closeSheet();
        if (scope === 'domestic') {
            initNaver();
            renderNaver(currentCat);
            updateRegions();
        } else {
            waitForGoogle(() => { initGoogle(); renderGoogle(currentCat); updateRegions(); });
        }
        tryInitialGeolocate();
    }

    // ===== 지역 칩 =====
    const regionsEl = document.getElementById('ppRegions');
    const regionsTitleEl = document.getElementById('ppRegionsTitle');
    const regionsListEl = document.getElementById('ppRegionsList');

    function currentFilteredPlaces() {
        const overseas = currentScope === 'overseas';
        return places.filter(p => (!!p.is_overseas) === overseas && (currentCat === 'all' || String(p.category_id) === String(currentCat)));
    }

    function updateRegions() {
        const filtered = currentFilteredPlaces();
        const buckets = new Map();
        filtered.forEach(p => {
            const r = regionOf(p);
            if (!r) return;
            if (!buckets.has(r)) buckets.set(r, []);
            buckets.get(r).push(p);
        });
        if (buckets.size < 2) { regionsEl.hidden = true; updateLocateBtnPosSafe(); return; }
        const sorted = [...buckets.entries()].sort((a, b) => b[1].length - a[1].length);
        const titleName = currentCat === 'all' ? USER_NAME : (CATEGORY_NAMES[currentCat] || '장소');
        regionsTitleEl.textContent = `${titleName}에 등록된 장소`;
        regionsListEl.innerHTML = sorted.map(([region, items]) =>
            `<button type="button" class="pp-map-regions__chip" data-region="${escapeHtml(region)}">${escapeHtml(region)}<span class="pp-map-regions__count">(${items.length})</span></button>`
        ).join('');
        regionsEl.hidden = false;
        updateLocateBtnPosSafe();
    }
    function updateLocateBtnPosSafe() {
        const btn = document.getElementById('ppMapLocate');
        if (!btn) return;
        const sheetEl = document.getElementById('ppMapSheet');
        let offset = 0;
        if (!sheetEl.hidden) {
            offset = sheetEl.offsetHeight + 10;
        } else if (!regionsEl.hidden) {
            offset = regionsEl.offsetHeight + 10;
        }
        btn.style.setProperty('--locate-offset', offset + 'px');
    }

    regionsListEl.addEventListener('click', (e) => {
        const btn = e.target.closest('.pp-map-regions__chip');
        if (!btn) return;
        const region = btn.dataset.region;
        regionsListEl.querySelectorAll('.pp-map-regions__chip').forEach(c => c.classList.remove('is-active'));
        btn.classList.add('is-active');
        const targets = currentFilteredPlaces().filter(p => regionOf(p) === region);
        if (!targets.length) return;
        if (currentScope === 'domestic' && nMap) {
            const bounds = new naver.maps.LatLngBounds();
            targets.forEach(p => bounds.extend(new naver.maps.LatLng(p.lat, p.lng)));
            nMap.fitBounds(bounds, { top: 110, right: 30, bottom: 120, left: 30 });
        } else if (currentScope === 'overseas' && gMap) {
            const bounds = new google.maps.LatLngBounds();
            targets.forEach(p => bounds.extend({ lat: p.lat, lng: p.lng }));
            gMap.fitBounds(bounds, { top: 110, right: 30, bottom: 120, left: 30 });
        }
    });

    const scopeEl = document.getElementById('ppScope');
    scopeEl.addEventListener('click', (e) => {
        const btn = e.target.closest('.yg-segtab__btn');
        if (!btn || btn.classList.contains('is-active')) return;
        scopeEl.querySelectorAll('.yg-segtab__btn').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        applyScope(btn.dataset.scope);
    });

    function fitToFilteredPlaces() {
        const targets = currentFilteredPlaces();
        if (!targets.length) return;
        if (currentScope === 'domestic' && nMap) {
            if (targets.length === 1) {
                nMap.setCenter(new naver.maps.LatLng(targets[0].lat, targets[0].lng));
                nMap.setZoom(15);
            } else {
                const bounds = new naver.maps.LatLngBounds();
                targets.forEach(p => bounds.extend(new naver.maps.LatLng(p.lat, p.lng)));
                nMap.fitBounds(bounds, { top: 110, right: 30, bottom: 120, left: 30 });
            }
        } else if (currentScope === 'overseas' && gMap) {
            if (targets.length === 1) {
                gMap.setCenter({ lat: targets[0].lat, lng: targets[0].lng });
                gMap.setZoom(15);
            } else {
                const bounds = new google.maps.LatLngBounds();
                targets.forEach(p => bounds.extend({ lat: p.lat, lng: p.lng }));
                gMap.fitBounds(bounds, { top: 110, right: 30, bottom: 120, left: 30 });
            }
        }
    }

    function regionCount() {
        const filtered = currentFilteredPlaces();
        const set = new Set();
        filtered.forEach(p => { const r = regionOf(p); if (r) set.add(r); });
        return set.size;
    }

    const tabs = document.getElementById('ppMapTabs');
    tabs.addEventListener('click', (e) => {
        const btn = e.target.closest('.pp-map-tab');
        if (!btn) return;
        tabs.querySelectorAll('.pp-map-tab').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        currentCat = btn.dataset.cat;
        sessionStorage.setItem('pp_map_cat', currentCat);
        if (currentScope === 'domestic') renderNaver(currentCat);
        else renderGoogle(currentCat);
        updateRegions();
        // 지역이 1개 이하일 땐 지역 칩이 안 나오므로 자동으로 장소 영역으로 이동
        if (regionCount() < 2) fitToFilteredPlaces();
    });

    // ===== Bottom sheet =====
    const sheet = document.getElementById('ppMapSheet');
    const msName = document.getElementById('ppMsName');
    const msMeta = document.getElementById('ppMsMeta');
    const msBadge = document.getElementById('ppMsBadge');
    const msCall = document.getElementById('ppMsCall');
    const msRoute = document.getElementById('ppMsRoute');
    const msDetail = document.getElementById('ppMsDetail');
    const msClose = document.getElementById('ppMapSheetClose');
    const msThumb = document.getElementById('ppMsThumb');

    const locateBtn = document.getElementById('ppMapLocate');
    function updateLocateBtnPos() {
        let offset = 0;
        if (!sheet.hidden) {
            offset = sheet.offsetHeight + 10;
        } else if (!regionsEl.hidden) {
            offset = regionsEl.offsetHeight + 10;
        }
        locateBtn.style.setProperty('--locate-offset', offset + 'px');
    }

    function openSheet(p) {
        if (p.thumb_url) { msThumb.src = p.thumb_url; msThumb.hidden = false; }
        else { msThumb.removeAttribute('src'); msThumb.hidden = true; }
        msName.textContent = p.name;
        msMeta.textContent = (p.category_name || '기타') + (p.address ? ' · ' + p.address : '');
        msBadge.textContent = p.status === 'visited' ? '방문완료' : '방문예정';
        msBadge.className = 'pp-badge pp-badge--' + p.status;
        if (p.phone) {
            msCall.href = 'tel:' + p.phone.replace(/[^0-9+]/g, '');
            msCall.classList.remove('is-disabled');
        } else {
            msCall.href = '#';
            msCall.classList.add('is-disabled');
        }
        msRoute.onclick = () => {
            const provider = p.is_overseas ? 'google' : 'naver';
            openRoute(provider, p.lat, p.lng, p.name);
        };
        msDetail.href = '/places/' + p.id;
        sheet.hidden = false;
        sheet.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => sheet.classList.add('is-open'));
        updateLocateBtnPos();
    }
    function closeSheet() {
        sheet.classList.remove('is-open');
        setTimeout(() => { sheet.hidden = true; sheet.setAttribute('aria-hidden', 'true'); updateLocateBtnPos(); }, 220);
        setNaverActive(null);
        setGoogleActive(null);
    }
    msClose.addEventListener('click', closeSheet);

    function openRoute(provider, lat, lng, name) {
        const ua = navigator.userAgent || '';
        const isMobile = /iPhone|iPad|iPod|Android/i.test(ua);
        const encName = encodeURIComponent(name);
        let webUrl, appUrl;
        if (provider === 'google') {
            appUrl = `comgooglemaps://?daddr=${lat},${lng}&directionsmode=driving`;
            webUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
        } else {
            appUrl = `nmap://route/car?dlat=${lat}&dlng=${lng}&dname=${encName}&appname=net.mypinpick`;
            webUrl = `https://map.naver.com/p/directions/-/${lng},${lat},${encName},,PLACE_POI/-/car`;
        }
        if (isMobile) {
            const t = Date.now();
            window.location.href = appUrl;
            setTimeout(() => { if (Date.now() - t < 1600 && !document.hidden) window.open(webUrl, '_blank'); }, 1200);
        } else {
            window.open(webUrl, '_blank');
        }
    }

    // ===== 현위치 =====
    let userLocMarker = null;
    locateBtn.addEventListener('click', () => {
        if (!navigator.geolocation) { alert('위치 기능을 지원하지 않는 브라우저입니다'); return; }
        locateBtn.classList.add('is-active');
        navigator.geolocation.getCurrentPosition((pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            if (currentScope === 'domestic' && nMap && typeof naver !== 'undefined') {
                const ll = new naver.maps.LatLng(lat, lng);
                nMap.setCenter(ll);
                nMap.setZoom(15);
                if (userLocMarker && userLocMarker.setMap) userLocMarker.setMap(null);
                userLocMarker = new naver.maps.Marker({
                    position: ll, map: nMap,
                    icon: { content: '<div class="pp-loc-dot"></div>', anchor: new naver.maps.Point(8, 8) },
                    zIndex: 1000,
                });
            } else if (currentScope === 'overseas' && gMap && typeof google !== 'undefined') {
                gMap.setCenter({ lat, lng });
                gMap.setZoom(15);
                if (userLocMarker && userLocMarker.setMap) userLocMarker.setMap(null);
                userLocMarker = new google.maps.Marker({
                    position: { lat, lng }, map: gMap,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 8, fillColor: '#2E7FFF', fillOpacity: 1,
                        strokeColor: '#fff', strokeWeight: 3,
                    },
                    zIndex: 1000,
                });
            }
        }, (err) => {
            locateBtn.classList.remove('is-active');
            alert('위치를 가져올 수 없어요');
        }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 });
    });

    // Initial — restore saved UI state
    scopeEl.querySelectorAll('.yg-segtab__btn').forEach(b => {
        b.classList.toggle('is-active', b.dataset.scope === currentScope);
    });
    if (currentCat !== 'all') {
        tabs.querySelectorAll('.pp-map-tab').forEach(b => {
            b.classList.toggle('is-active', b.dataset.cat === currentCat);
        });
    }
    applyScope(currentScope);
})();
</script>
@endpush
