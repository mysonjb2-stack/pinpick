@php $editMode = isset($place); @endphp
@extends('layouts.app')
@section('title', $editMode ? '장소 수정' : '장소 추가')
@section('app_class', 'pp-app--form')

@section('header')
<header class="pp-header">
    <button class="pp-header__icon pp-header__back" onclick="history.back()" aria-label="뒤로">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </button>
    <div class="pp-header__title">{{ $editMode ? '장소 수정' : '장소 추가' }}</div>
</header>
@endsection

@section('content')
<div class="pp-form">
    <div class="pp-field">
        <label class="pp-label">장소 검색</label>
        <button type="button" class="pp-search-trigger {{ $editMode ? 'is-filled' : '' }}" id="searchTrigger">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <span>{{ $editMode ? $place->name : '예: 스타벅스 강남역점' }}</span>
        </button>
    </div>

    <form method="POST" action="{{ $editMode ? route('places.update', $place) : route('places.store') }}" id="placeForm" enctype="multipart/form-data">
        @csrf
        @if($editMode) @method('PUT') @endif
        <div class="pp-field">
            <label class="pp-label">장소명 *</label>
            <input class="pp-input" name="name" id="f_name" required value="{{ $editMode ? $place->name : '' }}">
        </div>

        <div class="pp-field">
            <div class="pp-label-row">
                <label class="pp-label">카테고리</label>
                <button type="button" class="yg-catorder__btn" id="catTrigger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                    카테고리 추가/수정
                </button>
            </div>
            <input type="hidden" name="category_id" id="f_category" value="{{ $editMode ? $place->category_id : '' }}">
            <div class="pp-chips pp-chips--scroll" id="catChips"></div>
            <div class="pp-cat-selected" id="catSelected" hidden>
                <span class="pp-cat-selected__name" id="catSelectedName"></span>
                <button type="button" class="pp-cat-selected__edit" id="catSelectedEdit">수정</button>
            </div>

            {{-- 카테고리 관리 패널 (홈과 동일) --}}
            <div class="yg-catorder-panel" id="catOrderPanel" hidden>
                <div class="yg-catorder-panel__head">
                    <span class="yg-catorder-panel__title">카테고리 관리</span>
                    <div class="yg-catorder-panel__actions">
                        <button type="button" class="yg-catorder-panel__cancel" id="catOrderCancel">취소</button>
                        <button type="button" class="yg-catorder-panel__done" id="catOrderDone">저장</button>
                    </div>
                </div>
                <button type="button" class="yg-catorder-panel__add" id="catOrderAdd">＋ 새 카테고리 추가</button>
                <ul class="yg-catorder-panel__list" id="catOrderList"></ul>
            </div>
        </div>

        @php
            $selectedThemeIds = $editMode ? $place->themes->pluck('id')->all() : [];
        @endphp
        <div class="pp-field">
            <label class="pp-label">테마 <span class="pp-label-sub">(복수 선택 가능, 최대 2개)</span></label>
            <div class="pp-chips pp-chips--wrap" id="themeChips" data-max="2">
                @foreach($themes as $t)
                    @php $on = in_array($t->id, $selectedThemeIds, true); @endphp
                    <button type="button" class="pp-chip{{ $on ? ' is-active' : '' }}" data-theme-id="{{ $t->id }}">{{ $t->name }}</button>
                @endforeach
            </div>
            <div id="themeHidden">
                @foreach($selectedThemeIds as $tid)
                    <input type="hidden" name="theme_ids[]" value="{{ $tid }}">
                @endforeach
            </div>
        </div>

        <div class="pp-field">
            <label class="pp-label">전화번호</label>
            <input class="pp-input" name="phone" id="f_phone" inputmode="tel" value="{{ $editMode ? $place->phone : '' }}">
        </div>

        @if($editMode)
        <div class="pp-field" id="naverUrlField" style="{{ $place->is_overseas ? 'display:none' : '' }}">
            <label class="pp-label">
                네이버 예약 URL
                <span class="pp-label-sub">(자동 매칭 수정)</span>
            </label>
            <input class="pp-input" name="naver_url" id="f_naver_url" placeholder="네이버에서 공유 → 링크 복사 후 붙여넣기" value="{{ $place->naver_place_id ? 'https://m.place.naver.com/place/' . $place->naver_place_id : '' }}">
            <p class="pp-help-text">자동으로 연결된 네이버 페이지가 잘못되었거나 비어있을 때만 수동으로 붙여넣으세요. 네이버 앱에서 업체 페이지 → 공유 → 링크 복사.</p>
        </div>
        @endif

        <div class="pp-field">
            <label class="pp-label">주소</label>
            <div class="pp-addr-wrap">
                <input class="pp-input pp-addr-input" name="road_address" id="f_road" placeholder="주소 또는 건물명 검색" value="{{ $editMode ? $place->road_address : '' }}" readonly>
                <button type="button" class="pp-addr-search-btn" id="addrSearchBtn" aria-label="주소 검색">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                </button>
            </div>
            <input type="hidden" name="address" id="f_addr" value="{{ $editMode ? $place->address : '' }}">
            <input type="hidden" name="lat" id="f_lat" value="{{ $editMode ? $place->lat : '' }}">
            <input type="hidden" name="lng" id="f_lng" value="{{ $editMode ? $place->lng : '' }}">
            <input type="hidden" name="kakao_place_id" id="f_kpid" value="{{ $editMode ? $place->kakao_place_id : '' }}">
            <input type="hidden" name="google_place_id" id="f_gpid" value="{{ $editMode ? $place->google_place_id : '' }}">
            <input type="hidden" name="is_overseas" id="f_overseas" value="{{ $editMode && $place->is_overseas ? '1' : '0' }}">
            <input type="hidden" name="opening_hours" id="f_hours" value="{{ $editMode && $place->opening_hours ? json_encode($place->opening_hours) : '' }}">
        </div>

        <div class="pp-field">
            <label class="pp-label">메모</label>
            <textarea class="pp-textarea" name="memo" maxlength="500" placeholder="한 줄 메모 (예: 솥밥 꼭 먹기)">{{ $editMode ? $place->memo : '' }}</textarea>
        </div>

        <div class="pp-field">
            <label class="pp-label">사진 <span class="pp-label-sub" id="imgCount">({{ $editMode ? $place->images->count() : 0 }}/5)</span></label>
            <input type="file" id="imgFileInput" accept="image/jpeg,image/png,image/webp,image/heic" multiple hidden>
            <div class="pp-images" id="imgPreview">
                @if($editMode)
                    @foreach($place->images as $img)
                    <div class="pp-images__item" data-existing-id="{{ $img->id }}">
                        <img src="{{ $img->thumb_url }}" alt="">
                        <button type="button" class="pp-images__del pp-images__del--existing" data-img-id="{{ $img->id }}" aria-label="삭제">&times;</button>
                    </div>
                    @endforeach
                @endif
                <button type="button" class="pp-images__add" id="imgAddBtn" aria-label="사진 추가"
                    @if($editMode && $place->images->count() >= 5) style="display:none" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2" width="24" height="24"><path d="M12 5v14M5 12h14"/></svg>
                    <span>사진 추가</span>
                </button>
            </div>
        </div>

        <div class="pp-field" id="visitedDateField" style="{{ ($editMode && $place->status === 'visited') ? '' : 'display:none' }}">
            <label class="pp-label">방문 날짜</label>
            <input type="date" class="pp-input" name="visited_at" value="{{ $editMode && $place->visited_at ? $place->visited_at->format('Y-m-d') : '' }}">
        </div>

        <div class="pp-field">
            <label class="pp-label">방문 상태</label>
            <div class="pp-seg">
                <button type="button" class="{{ ($editMode ? $place->status : 'planned') === 'planned' ? 'is-active' : '' }}" data-status="planned">방문예정</button>
                <button type="button" class="{{ ($editMode ? $place->status : '') === 'visited' ? 'is-active' : '' }}" data-status="visited">방문완료</button>
            </div>
            <input type="hidden" name="status" id="f_status" value="{{ $editMode ? $place->status : 'planned' }}">
        </div>

    </form>
</div>

<div class="pp-form-submit">
    <button class="pp-btn pp-btn--block" type="submit" form="placeForm" id="placeSubmitBtn" disabled>{{ $editMode ? '수정하기' : '저장하기' }}</button>
</div>

{{-- ===== 장소 검색 레이어 (풀스크린 슬라이드업) ===== --}}
<div class="sl" id="searchLayer">
    <div class="sl__inner">
        {{-- 상단 헤더 --}}
        <div class="sl__head">
            <button type="button" class="sl__back" id="slBack" aria-label="뒤로">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <span class="sl__title">장소 검색</span>
        </div>

        {{-- 국내/해외 토글 --}}
        <div class="sl__region">
            <button type="button" class="sl__region-btn is-active" data-region="domestic">🇰🇷 국내</button>
            <button type="button" class="sl__region-btn" data-region="overseas">🌍 해외</button>
        </div>

        {{-- 검색창 --}}
        <div class="sl__search">
            <svg class="sl__search-icon" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="text" id="slInput" class="sl__search-input" placeholder="장소명을 입력하세요" autocomplete="off">
            <button type="button" class="sl__search-clear" id="slClear" hidden aria-label="지우기">&times;</button>
        </div>

        {{-- 탭 --}}
        <div class="sl__tabs">
            <button type="button" class="sl__tab is-active" data-tab="keyword">🔍 키워드 검색</button>
            <button type="button" class="sl__tab" data-tab="mappin">🗺 지도에서 찍기</button>
        </div>

        {{-- 키워드 검색 탭 --}}
        <div class="sl__pane is-active" data-pane="keyword">
            {{-- 최근 검색어 --}}
            <div class="sl__recent" id="slRecent">
                <div class="sl__recent-head">최근 검색어</div>
                <ul class="sl__recent-list" id="slRecentList"></ul>
            </div>
            {{-- 자동완성 결과 리스트 (리스트 뷰) --}}
            <div class="sl__ac" id="slAc" hidden>
                <div class="sl__ac-list" id="slAcList"></div>
            </div>
            {{-- 검색 결과 지도 뷰 --}}
            <div class="sl__result" id="slResult" hidden>
                <div class="sl__result-map-area">
                    <div class="sl__result-map" id="slResultMapNaver"></div>
                    <div class="sl__result-map" id="slResultMapGoogle" style="display:none"></div>
                    <div class="sl__result-top" id="slResultTop" hidden>
                        <div class="sl__result-notice" id="slResultNotice">결과가 많아요. 내 주변부터 보여드려요</div>
                        <div class="sl__region-chips" id="slRegionChips"></div>
                    </div>
                </div>
                <div class="sl__result-sheet" id="slResultSheet">
                    <div class="sl__result-sheet__bar" id="slSheetBar">
                        <div class="sl__result-sheet__handle"></div>
                    </div>
                    <div class="sl__result-sheet__scroll" id="slSheetScroll"></div>
                </div>
                <div class="sl__result-iw" id="slIw" hidden>
                    <button type="button" class="sl__result-iw__close" id="slIwClose" aria-label="닫기">&times;</button>
                    <div class="sl__result-iw__body">
                        <div class="sl__result-iw__name" id="slIwName"></div>
                        <div class="sl__result-iw__meta">
                            <span class="sl__result-iw__dist" id="slIwDist"></span>
                            <span class="sl__result-iw__addr" id="slIwAddr"></span>
                        </div>
                    </div>
                    <button type="button" class="sl__result-iw__save" id="slIwSave">저장하기</button>
                </div>
            </div>
            {{-- 뷰 토글 버튼 (지도↔리스트) --}}
            <button type="button" class="sl__view-toggle" id="slViewToggle" hidden>
                <svg id="slViewToggleIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                <span id="slViewToggleText">리스트</span>
            </button>
            {{-- 결과 없음 --}}
            <div class="sl__empty" id="slEmpty" hidden>
                <div class="sl__empty-icon">🔍</div>
                <p class="sl__empty-msg">검색 결과가 없어요</p>
                <button type="button" class="sl__empty-btn" id="slManual">직접 입력하기</button>
            </div>
        </div>

        {{-- 지도에서 찍기 탭 --}}
        <div class="sl__pane" data-pane="mappin">
            <div class="sl__mappin-wrap">
                <div class="sl__mappin-map" id="slMappinMap"></div>
                <div class="sl__mappin-map" id="slMappinMapGoogle" style="display:none"></div>
                {{-- 중앙 고정 핀 --}}
                <div class="sl__mappin-pin">
                    <svg viewBox="0 0 24 36" width="32" height="48">
                        <path d="M12 0C5.4 0 0 5.4 0 12c0 9 12 24 12 24s12-15 12-24C24 5.4 18.6 0 12 0z" fill="#FF4B6E"/>
                        <circle cx="12" cy="11" r="5" fill="#fff"/>
                    </svg>
                </div>
                {{-- 현위치 버튼 --}}
                <button type="button" class="sl__mappin-gps" id="slGps" aria-label="현위치">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" width="20" height="20"><circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/><circle cx="12" cy="12" r="8"/></svg>
                </button>
                {{-- 하단 주소 + 주변 장소 + 저장 --}}
                <div class="sl__mappin-bottom">
                    <div class="sl__mappin-addr" id="slMappinAddr">지도를 움직여 위치를 지정하세요</div>
                    <div class="sl__mappin-nearby" id="slMappinNearby"></div>
                    <button type="button" class="sl__mappin-save" id="slMappinSave">이 위치로 저장</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 주소 검색 임베드 레이어 --}}
<div id="addrSearchLayer" class="pp-addr-layer" style="display:none">
    <div class="pp-addr-layer__panel">
        <div class="pp-addr-layer__head">
            <span class="pp-addr-layer__title">주소 검색</span>
            <button type="button" class="pp-addr-layer__close" id="addrSearchClose" aria-label="닫기">&times;</button>
        </div>
        <div class="pp-addr-layer__body" id="addrSearchLayerInner"></div>
    </div>
</div>

<script id="initialCategories" type="application/json">@json($categories->map(fn($c) => ['id'=>$c->id,'name'=>$c->name,'is_default'=>(bool)$c->is_default]))</script>
@endsection

@push('head')
@if($naverClientId)
<script src="https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId={{ $naverClientId }}&submodules=geocoder"></script>
@endif
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
@if($googleMapsKey)
<script>
var _gmReady = new Promise(function(resolve) { window.__gmcb = resolve; });
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&callback=__gmcb" async defer></script>
@endif
@endpush

@push('scripts')
<script>
// =========================================
// 테마 칩 선택 (최대 2개)
// =========================================
(function() {
    const wrap = document.getElementById('themeChips');
    const hidden = document.getElementById('themeHidden');
    if (!wrap || !hidden) return;
    const MAX = parseInt(wrap.dataset.max, 10) || 2;

    function syncHidden() {
        const selected = Array.from(wrap.querySelectorAll('.pp-chip.is-active[data-theme-id]'))
            .map(el => el.dataset.themeId);
        hidden.innerHTML = selected.map(id =>
            `<input type="hidden" name="theme_ids[]" value="${id}">`
        ).join('');
    }

    wrap.addEventListener('click', (e) => {
        const chip = e.target.closest('.pp-chip[data-theme-id]');
        if (!chip) return;
        if (chip.classList.contains('is-active')) {
            chip.classList.remove('is-active');
        } else {
            const active = wrap.querySelectorAll('.pp-chip.is-active[data-theme-id]');
            if (active.length >= MAX) {
                wrap.classList.remove('is-shake');
                void wrap.offsetWidth;
                wrap.classList.add('is-shake');
                return;
            }
            chip.classList.add('is-active');
        }
        syncHidden();
    });
})();

// =========================================
// 저장 버튼 활성화 (장소명/카테고리/테마/주소 모두 입력 시)
// =========================================
(function() {
    const btn = document.getElementById('placeSubmitBtn');
    const nameEl = document.getElementById('f_name');
    const catEl = document.getElementById('f_category');
    const roadEl = document.getElementById('f_road');
    const addrEl = document.getElementById('f_addr');
    const themeWrap = document.getElementById('themeChips');
    if (!btn) return;

    function check() {
        const hasName  = (nameEl?.value || '').trim() !== '';
        const hasCat   = (catEl?.value || '').trim() !== '';
        const hasAddr  = ((roadEl?.value || '').trim() !== '') || ((addrEl?.value || '').trim() !== '');
        const hasTheme = themeWrap
            ? themeWrap.querySelectorAll('.pp-chip.is-active[data-theme-id]').length > 0
            : false;
        btn.disabled = !(hasName && hasCat && hasTheme && hasAddr);
    }

    [nameEl, catEl, roadEl, addrEl].forEach(el => {
        if (!el) return;
        el.addEventListener('input', check);
        el.addEventListener('change', check);
    });
    if (themeWrap) themeWrap.addEventListener('click', () => setTimeout(check, 0));

    // 프로그램적으로 값 변경되는 hidden input도 감지 (MutationObserver)
    [catEl, roadEl, addrEl].forEach(el => {
        if (!el) return;
        new MutationObserver(check).observe(el, { attributes: true, attributeFilter: ['value'] });
    });
    // hidden input의 value 프로퍼티 변경도 폴링으로 감지 (확실성)
    let snapshot = '';
    setInterval(() => {
        const s = [nameEl?.value, catEl?.value, roadEl?.value, addrEl?.value].join('|');
        if (s !== snapshot) { snapshot = s; check(); }
    }, 300);

    check();
})();

// =========================================
// 공통 유틸
// =========================================
function escapeHtml(s) { return String(s||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
const csrfToken = '{{ csrf_token() }}';
const isGuest = {{ auth()->check() ? 'false' : 'true' }};
const editMode = {{ $editMode ? 'true' : 'false' }};
let currentRegion = '{{ ($editMode && $place->is_overseas) ? 'overseas' : 'domestic' }}';

// =========================================
// 1) 장소 검색 레이어 — 열기/닫기
// =========================================
const SL = document.getElementById('searchLayer');
const slInput = document.getElementById('slInput');
const slClear = document.getElementById('slClear');
const slRecent = document.getElementById('slRecent');
const slRecentList = document.getElementById('slRecentList');
const slAc = document.getElementById('slAc');
const slAcList = document.getElementById('slAcList');
const slEmpty = document.getElementById('slEmpty');

let searchTimer;

function openSL() {
    SL.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => slInput.focus(), 350);
    renderRecent();
    showKeywordInit();
    refreshGeo();
}
function closeSL() {
    SL.classList.remove('is-open');
    document.body.style.overflow = '';
    slInput.value = '';
    slClear.hidden = true;
    resultViewMode = 'map';
    showKeywordInit();
}

document.getElementById('searchTrigger').addEventListener('click', openSL);
document.getElementById('slBack').addEventListener('click', closeSL);

// 수정모드 해외 장소 시 토글 초기화
if (currentRegion === 'overseas') {
    document.querySelectorAll('.sl__region-btn').forEach(b => {
        b.classList.toggle('is-active', b.dataset.region === 'overseas');
    });
}

// 검색창 X 버튼
slInput.addEventListener('input', () => {
    slClear.hidden = !slInput.value;
    clearTimeout(searchTimer);
    const q = slInput.value.trim();
    if (q.length < 2) { showKeywordInit(); return; }
    searchTimer = setTimeout(() => doSearch(q), 300);
});
slClear.addEventListener('click', () => {
    slInput.value = '';
    slClear.hidden = true;
    slInput.focus();
    showKeywordInit();
});

// 국내/해외 토글
document.querySelectorAll('.sl__region-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        currentRegion = btn.dataset.region;
        document.querySelectorAll('.sl__region-btn').forEach(b => b.classList.toggle('is-active', b === btn));
        document.getElementById('f_overseas').value = currentRegion === 'overseas' ? '1' : '0';
        const nuf = document.getElementById('naverUrlField');
        if (nuf) nuf.style.display = currentRegion === 'overseas' ? 'none' : '';
        // 전환 시 검색 초기화
        showKeywordInit();
        slInput.value = '';
        slClear.hidden = true;
        // 지도 탭 열려있으면 재초기화
        const activePane = document.querySelector('.sl__pane.is-active');
        if (activePane && activePane.dataset.pane === 'mappin') {
            switchMappinMap();
        }
    });
});

// 탭 전환
document.querySelectorAll('.sl__tab').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.sl__tab').forEach(b => b.classList.toggle('is-active', b === btn));
        document.querySelectorAll('.sl__pane').forEach(p => p.classList.toggle('is-active', p.dataset.pane === btn.dataset.tab));
        if (btn.dataset.tab === 'mappin') switchMappinMap();
    });
});

// =========================================
// 2) 키워드 검색 탭
// =========================================
const RECENT_KEY = 'pinpick_recent_search';
const RECENT_MAX = 5;

function getRecent() { return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]'); }
function saveRecent(q) {
    let list = getRecent().filter(x => x !== q);
    list.unshift(q);
    if (list.length > RECENT_MAX) list = list.slice(0, RECENT_MAX);
    localStorage.setItem(RECENT_KEY, JSON.stringify(list));
}
function removeRecent(q) {
    localStorage.setItem(RECENT_KEY, JSON.stringify(getRecent().filter(x => x !== q)));
}

function renderRecent() {
    const list = getRecent();
    if (!list.length) { slRecent.hidden = true; return; }
    slRecent.hidden = false;
    slRecentList.innerHTML = list.map(q => `
        <li class="sl__recent-item">
            <button type="button" class="sl__recent-keyword" data-q="${escapeHtml(q)}">${escapeHtml(q)}</button>
            <button type="button" class="sl__recent-del" data-q="${escapeHtml(q)}" aria-label="삭제">&times;</button>
        </li>
    `).join('');
    slRecentList.querySelectorAll('.sl__recent-keyword').forEach(el => {
        el.addEventListener('click', () => { slInput.value = el.dataset.q; slClear.hidden = false; doSearch(el.dataset.q); });
    });
    slRecentList.querySelectorAll('.sl__recent-del').forEach(el => {
        el.addEventListener('click', () => { removeRecent(el.dataset.q); renderRecent(); });
    });
}

function showKeywordInit() {
    slAc.hidden = true;
    slEmpty.hidden = true;
    document.getElementById('slResult').hidden = true;
    document.getElementById('slViewToggle').hidden = true;
    document.getElementById('slIw').hidden = true;
    document.getElementById('slResultTop').hidden = true;
    slRecent.hidden = false;
    renderRecent();
}

// 카테고리 아이콘 매핑 (국내 카카오)
function getCategoryIcon(cat) {
    if (!cat) return '📍';
    if (/음식점|맛집|restaurant|food|bakery|meal_delivery/.test(cat)) return '🍽';
    if (/카페|cafe|coffee/.test(cat)) return '☕';
    if (/숙박|호텔|모텔|펜션|lodging|hotel|resort/.test(cat)) return '🏨';
    if (/관광|여행|문화|museum|tourist|amusement|landmark/.test(cat)) return '🎭';
    if (/쇼핑|마트|백화점|shopping|store|mall|market/.test(cat)) return '🛍';
    if (/병원|약국|의료|hospital|pharmacy|doctor|health/.test(cat)) return '🏥';
    if (/주차|주유/.test(cat)) return '🅿️';
    if (/편의점|convenience/.test(cat)) return '🏪';
    if (/지하철|버스|교통|airport|station|transit/.test(cat)) return '🚇';
    return '📍';
}


async function doSearch(q) {
    const isOverseas = currentRegion === 'overseas';
    try {
        const params = new URLSearchParams({ q });
        if (userLat !== null) { params.set('lat', userLat); params.set('lng', userLng); }
        const url = (isOverseas ? '/api/search/overseas?' : '/api/search?') + params.toString();
        const r = await fetch(url);
        const data = await r.json();
        const docs = data.documents || [];
        if (!docs.length) {
            slRecent.hidden = true; slAc.hidden = true;
            document.getElementById('slResult').hidden = true;
            document.getElementById('slViewToggle').hidden = true;
            slEmpty.hidden = false;
            return;
        }
        renderAcDomestic(docs);
        resultViewMode = 'map';
        updateViewToggleBtn();
        showResultMapView(docs);
    } catch (e) {
        slAcList.innerHTML = '<div class="sl__error">검색 중 오류가 발생했어요</div>';
        slAc.hidden = false;
    }
}

// 국내 자동완성 렌더링 (카카오 키워드 결과)
function renderAcDomestic(docs) {
    slAcList.innerHTML = docs.map((d, i) => `
        <div class="sl__ac-item" data-i="${i}">
            <div class="sl__ac-icon">${getCategoryIcon(d.category_group_name)}</div>
            <div class="sl__ac-body">
                <div class="sl__ac-name">${escapeHtml(d.place_name)}</div>
                <div class="sl__ac-desc">${escapeHtml(d.road_address_name || d.address_name || '')}</div>
            </div>
        </div>
    `).join('');
    slAcList.querySelectorAll('.sl__ac-item').forEach(el => {
        el.addEventListener('click', () => pickPlace(docs[+el.dataset.i]));
    });
}


function pickPlace(d) {
    const _q = (slInput.value || '').trim();
    if (_q) saveRecent(_q);
    document.getElementById('f_name').value = d.place_name || '';
    document.getElementById('f_phone').value = d.phone || '';
    document.getElementById('f_hours').value = d.opening_hours ? JSON.stringify(d.opening_hours) : '';
    document.getElementById('f_road').value = d.road_address_name || d.address_name || '';
    document.getElementById('f_addr').value = d.address_name || '';
    document.getElementById('f_lat').value = d.y || '';
    document.getElementById('f_lng').value = d.x || '';
    // 국내=카카오 id, 해외=구글 place_id로 분기 저장
    if (currentRegion === 'overseas') {
        document.getElementById('f_kpid').value = '';
        document.getElementById('f_gpid').value = d.id || '';
    } else {
        document.getElementById('f_kpid').value = d.id || '';
        document.getElementById('f_gpid').value = '';
    }
    document.getElementById('f_overseas').value = currentRegion === 'overseas' ? '1' : '0';
    // 카카오에 전화번호/영업시간 없으면 구글로 폴백 조회 (국내만)
    if (currentRegion === 'domestic' && d.place_name) {
        const phoneEl = document.getElementById('f_phone');
        const hoursEl = document.getElementById('f_hours');
        if (!phoneEl.value) phoneEl.placeholder = '전화번호 조회 중…';
        const params = new URLSearchParams({ name: d.place_name, address: d.road_address_name || d.address_name || '' });
        fetch('/api/phone/fallback?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(j => {
                if (j && j.phone && !phoneEl.value) phoneEl.value = j.phone;
                if (j && j.opening_hours && !hoursEl.value) hoursEl.value = JSON.stringify(j.opening_hours);
            })
            .catch(() => {})
            .finally(() => { phoneEl.placeholder = ''; });
    }
    const sp = document.getElementById('searchTrigger').querySelector('span');
    if (sp) sp.textContent = d.place_name || '';
    document.getElementById('searchTrigger').classList.add('is-filled');
    // 카테고리 자동 추천 (국내만)
    if (currentRegion === 'domestic') {
        const cat = d.category_group_name || '';
        const catAutoMap = {'음식점':'맛집','카페':'카페','숙박':'여행','병원':'병원/약국','약국':'병원/약국'};
        const target = catAutoMap[cat];
        if (target) { const found = catState.find(c => c.name === target); if (found) selectCategory(found.id); }
    }
    closeSL();
}

document.getElementById('slManual').addEventListener('click', () => { closeSL(); document.getElementById('f_name').focus(); });

// =========================================
// 2-1) 검색 결과 지도 뷰
// =========================================
let resultMapNaver = null, resultMapGoogle = null;
let resultNaverInited = false, resultGoogleInited = false;
let rMarkers = [], grMarkers = [];
let clusterMarkers = [], gClusterMarkers = [];
let lastDocs = [];
let lastAllDocs = [];
let resultViewMode = 'map';
let selIdx = -1;
let userLat = null, userLng = null;
let activeRegion = null;
let regionGroups = [];

// localStorage 캐시에서 즉시 복원
try {
    const cached = JSON.parse(localStorage.getItem('pp_last_geo') || 'null');
    if (cached && cached.lat && cached.lng) { userLat = cached.lat; userLng = cached.lng; }
} catch(e) {}

function refreshGeo() {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(p => {
        userLat = p.coords.latitude; userLng = p.coords.longitude;
        try { localStorage.setItem('pp_last_geo', JSON.stringify({ lat: userLat, lng: userLng })); } catch(e) {}
    }, () => {}, { enableHighAccuracy: false, timeout: 5000 });
}
refreshGeo();

function haversine(lat1, lng1, lat2, lng2) {
    const R = 6371, toRad = v => v * Math.PI / 180;
    const dLat = toRad(lat2 - lat1), dLng = toRad(lng2 - lng1);
    const a = Math.sin(dLat/2)**2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}
function fmtDist(km) { return km < 1 ? Math.round(km * 1000) + 'm' : km.toFixed(1) + 'km'; }

const PIN_GRAY = encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="34" viewBox="0 0 24 34"><path d="M12 1C5.9 1 1 5.9 1 12c0 8 11 20 11 20s11-12 11-20C23 5.9 18.1 1 12 1z" fill="#9CA3AF" stroke="#fff" stroke-width="1.5"/><circle cx="12" cy="12" r="4" fill="#fff"/></svg>');
const PIN_GOLD = encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="28" height="38" viewBox="0 0 28 38"><path d="M14 1C7 1 1 7 1 14c0 9.5 13 22 13 22s13-12.5 13-22C27 7 21 1 14 1z" fill="#C9A13A" stroke="#fff" stroke-width="2"/><circle cx="14" cy="14" r="5" fill="#fff"/></svg>');

function buildRPin(name, active) {
    const color = active ? '#C9A13A' : '#9CA3AF';
    return `<div class="sl__rpin-anchor"><div class="sl__rpin${active ? ' is-active' : ''}">
        <svg viewBox="0 0 24 34" width="24" height="34"><path d="M12 1C5.9 1 1 5.9 1 12c0 8 11 20 11 20s11-12 11-20C23 5.9 18.1 1 12 1z" fill="${color}" stroke="#fff" stroke-width="1.5"/><circle cx="12" cy="12" r="4" fill="#fff"/></svg>
        <span class="sl__rpin__name">${escapeHtml(name || '')}</span>
    </div></div>`;
}

function extractRegion(d) {
    const addr = d.road_address_name || d.address_name || '';
    const m = addr.match(/^(서울|부산|대구|인천|광주|대전|울산|세종|경기|충북|충남|전북|전남|경북|경남|강원|제주)/);
    if (m) return m[1];
    const p = addr.match(/^(\S+?)(특별시|광역시|특별자치시|특별자치도|도)\s/);
    if (p) return p[1];
    const first = addr.split(/[\s,]+/)[0];
    return first || '기타';
}

function buildRegionGroups(docs) {
    const groups = {};
    docs.forEach(d => {
        const r = extractRegion(d);
        if (!groups[r]) groups[r] = [];
        groups[r].push(d);
    });
    return Object.entries(groups).map(([name, items]) => {
        const cLat = items.reduce((s, d) => s + (+d.y), 0) / items.length;
        const cLng = items.reduce((s, d) => s + (+d.x), 0) / items.length;
        const dist = userLat !== null ? haversine(userLat, userLng, cLat, cLng) : 9999;
        return { name, items, cLat, cLng, dist };
    }).sort((a, b) => a.dist - b.dist);
}

function showResultMapView(docs) {
    const slResult = document.getElementById('slResult');
    slResult.hidden = false;
    slRecent.hidden = true;
    slEmpty.hidden = true;
    slAc.hidden = true;
    document.getElementById('slViewToggle').hidden = false;

    lastAllDocs = docs;
    selIdx = -1;
    activeRegion = null;
    document.getElementById('slIw').hidden = true;
    document.getElementById('slResultSheet').classList.remove('is-expanded');

    const isOverseas = currentRegion === 'overseas';
    document.getElementById('slResultMapNaver').style.display = isOverseas ? 'none' : 'block';
    document.getElementById('slResultMapGoogle').style.display = isOverseas ? 'block' : 'none';

    const THRESHOLD = 10;
    const isClustered = docs.length >= THRESHOLD && userLat !== null;

    if (isClustered) {
        regionGroups = buildRegionGroups(docs);
        showClusteredUI(docs, isOverseas);
    } else {
        regionGroups = [];
        document.getElementById('slResultTop').hidden = true;
        lastDocs = docs;
        if (isOverseas) {
            initResultGMap();
            setTimeout(() => renderGRMarkers(docs), 100);
        } else {
            initResultNMap();
            setTimeout(() => renderNRMarkers(docs), 100);
        }
        renderSheet(docs);
    }
}

function showClusteredUI(allDocs, isOverseas) {
    document.getElementById('slResultTop').hidden = false;
    renderRegionChips();

    if (isOverseas) {
        initResultGMap();
    } else {
        initResultNMap();
    }

    activeRegion = 'nearby';
    applyRegionFilter(isOverseas);
}

function renderRegionChips() {
    const box = document.getElementById('slRegionChips');
    let html = `<button type="button" class="sl__region-chip is-active" data-region="nearby">📍 내 주변</button>`;
    regionGroups.forEach(g => {
        html += `<button type="button" class="sl__region-chip" data-region="${escapeHtml(g.name)}">${escapeHtml(g.name)} <span style="opacity:.6">${g.items.length}</span></button>`;
    });
    box.innerHTML = html;
    box.querySelectorAll('.sl__region-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            box.querySelectorAll('.sl__region-chip').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            activeRegion = btn.dataset.region;
            document.getElementById('slIw').hidden = true;
            selIdx = -1;
            applyRegionFilter(currentRegion === 'overseas');
        });
    });
}

function applyRegionFilter(isOverseas) {
    clearClusterMarkers();

    let filteredDocs;
    if (activeRegion === 'nearby') {
        filteredDocs = [...lastAllDocs].sort((a, b) =>
            haversine(userLat, userLng, +a.y, +a.x) - haversine(userLat, userLng, +b.y, +b.x)
        ).slice(0, 5);
    } else {
        const group = regionGroups.find(g => g.name === activeRegion);
        filteredDocs = group ? [...group.items].sort((a, b) =>
            haversine(userLat, userLng, +a.y, +a.x) - haversine(userLat, userLng, +b.y, +b.x)
        ) : lastAllDocs;
    }
    lastDocs = filteredDocs;
    renderSheet(filteredDocs);

    if (isOverseas) {
        setTimeout(() => {
            renderGRMarkers(filteredDocs);
            if (activeRegion === 'nearby') renderGClusterMarkers();
        }, 100);
    } else {
        setTimeout(() => {
            renderNRMarkers(filteredDocs);
            if (activeRegion === 'nearby') renderNClusterMarkers();
        }, 100);
    }
}

function clearClusterMarkers() {
    clusterMarkers.forEach(m => m.setMap(null));
    clusterMarkers = [];
    gClusterMarkers.forEach(m => m.setMap(null));
    gClusterMarkers = [];
}

function buildClusterHtml(name, count) {
    return `<div class="sl__cluster-anchor"><div class="sl__cluster">
        <div class="sl__cluster__bubble">${count}</div>
        <div class="sl__cluster__name">${escapeHtml(name)}</div>
    </div></div>`;
}

function renderNClusterMarkers() {
    if (!resultMapNaver) return;
    regionGroups.forEach(g => {
        const nearestGroup = regionGroups[0];
        if (g === nearestGroup) return;
        const pos = new naver.maps.LatLng(g.cLat, g.cLng);
        const m = new naver.maps.Marker({
            position: pos, map: resultMapNaver,
            icon: { content: buildClusterHtml(g.name, g.items.length), anchor: new naver.maps.Point(0, 0) },
            zIndex: 50,
        });
        naver.maps.Event.addListener(m, 'click', () => {
            const chip = document.querySelector(`.sl__region-chip[data-region="${g.name}"]`);
            if (chip) chip.click();
        });
        clusterMarkers.push(m);
    });
}

function renderGClusterMarkers() {
    if (!resultMapGoogle) return;
    regionGroups.forEach(g => {
        const nearestGroup = regionGroups[0];
        if (g === nearestGroup) return;
        const pos = { lat: g.cLat, lng: g.cLng };
        const canvas = document.createElement('canvas');
        canvas.width = 48; canvas.height = 48;
        const ctx = canvas.getContext('2d');
        ctx.beginPath(); ctx.arc(24, 24, 20, 0, Math.PI * 2);
        ctx.fillStyle = '#F2B544'; ctx.fill();
        ctx.strokeStyle = 'rgba(242,181,68,0.3)'; ctx.lineWidth = 4; ctx.stroke();
        ctx.fillStyle = '#fff'; ctx.font = 'bold 15px sans-serif'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText(String(g.items.length), 24, 24);
        const m = new google.maps.Marker({
            position: pos, map: resultMapGoogle,
            icon: { url: canvas.toDataURL(), scaledSize: new google.maps.Size(48, 48), anchor: new google.maps.Point(24, 24) },
            label: { text: g.name, color: '#2b211e', fontSize: '10px', fontWeight: '700', className: 'pp-gmarker-label' },
            zIndex: 50,
        });
        m.addListener('click', () => {
            const chip = document.querySelector(`.sl__region-chip[data-region="${g.name}"]`);
            if (chip) chip.click();
        });
        gClusterMarkers.push(m);
    });
}

function initResultNMap() {
    if (typeof naver === 'undefined') return;
    if (resultNaverInited) {
        if (resultMapNaver) setTimeout(() => naver.maps.Event.trigger(resultMapNaver, 'resize'), 50);
        return;
    }
    resultNaverInited = true;
    resultMapNaver = new naver.maps.Map('slResultMapNaver', {
        center: new naver.maps.LatLng(37.5665, 126.978),
        zoom: 15, zoomControl: false, scaleControl: false, mapDataControl: false,
    });
}

async function initResultGMap() {
    if (typeof google === 'undefined') return;
    if (resultGoogleInited) {
        if (resultMapGoogle) setTimeout(() => google.maps.event.trigger(resultMapGoogle, 'resize'), 50);
        return;
    }
    resultGoogleInited = true;
    try {
        await _gmReady;
        resultMapGoogle = new google.maps.Map(document.getElementById('slResultMapGoogle'), {
            center: { lat: 37.5665, lng: 126.978 }, zoom: 15, disableDefaultUI: true,
        });
    } catch (e) { console.error('Result GMap init:', e); }
}

function clearNRMarkers() { rMarkers.forEach(m => m.setMap(null)); rMarkers = []; }
function clearGRMarkers() { grMarkers.forEach(m => m.setMap(null)); grMarkers = []; }

function renderNRMarkers(docs) {
    clearNRMarkers();
    if (!docs.length || !resultMapNaver) return;
    const bounds = new naver.maps.LatLngBounds();
    docs.forEach((d, i) => {
        const pos = new naver.maps.LatLng(+d.y, +d.x);
        const m = new naver.maps.Marker({
            position: pos, map: resultMapNaver,
            icon: { content: buildRPin(d.place_name, false), anchor: new naver.maps.Point(0, 0) },
            zIndex: 100,
        });
        m._idx = i;
        naver.maps.Event.addListener(m, 'click', () => selectResult(i));
        rMarkers.push(m);
        bounds.extend(pos);
    });
    if (docs.length === 1) {
        resultMapNaver.setCenter(new naver.maps.LatLng(+docs[0].y, +docs[0].x));
        resultMapNaver.setZoom(16);
    } else {
        resultMapNaver.fitBounds(bounds, { top: 40, right: 40, bottom: 180, left: 40 });
    }
}

function renderGRMarkers(docs) {
    clearGRMarkers();
    if (!docs.length || !resultMapGoogle) return;
    const bounds = new google.maps.LatLngBounds();
    docs.forEach((d, i) => {
        const pos = { lat: +d.y, lng: +d.x };
        const m = new google.maps.Marker({
            position: pos, map: resultMapGoogle, title: d.place_name || '',
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + PIN_GRAY,
                scaledSize: new google.maps.Size(24, 34),
                anchor: new google.maps.Point(12, 34),
                labelOrigin: new google.maps.Point(12, -8),
            },
            label: { text: d.place_name || '', color: '#2b211e', fontSize: '11px', fontWeight: '600', className: 'pp-gmarker-label' },
        });
        m._idx = i;
        m.addListener('click', () => selectResult(i));
        grMarkers.push(m);
        bounds.extend(pos);
    });
    if (docs.length === 1) {
        resultMapGoogle.setCenter({ lat: +docs[0].y, lng: +docs[0].x });
        resultMapGoogle.setZoom(16);
    } else {
        resultMapGoogle.fitBounds(bounds, { top: 40, right: 40, bottom: 180, left: 40 });
    }
}

function renderSheet(docs) {
    const scroll = document.getElementById('slSheetScroll');
    scroll.innerHTML = docs.map((d, i) => {
        const icon = getCategoryIcon(d.category_group_name);
        let dist = '';
        if (userLat !== null && d.y && d.x) {
            dist = `<span class="sl__sheet-dist">${fmtDist(haversine(userLat, userLng, +d.y, +d.x))}</span>`;
        }
        const region = regionGroups.length ? `<span class="sl__sheet-region">${escapeHtml(extractRegion(d))}</span>` : '';
        return `<div class="sl__sheet-item" data-idx="${i}">
            <div class="sl__sheet-icon">${icon}</div>
            <div class="sl__sheet-body">
                <div class="sl__sheet-name">${escapeHtml(d.place_name)}</div>
                <div class="sl__sheet-addr">${region}${escapeHtml(d.road_address_name || d.address_name || '')}</div>
            </div>
            ${dist}
        </div>`;
    }).join('');
    scroll.querySelectorAll('.sl__sheet-item').forEach(el => {
        el.addEventListener('click', () => selectResult(+el.dataset.idx));
    });
}

function selectResult(idx) {
    if (idx < 0 || idx >= lastDocs.length) return;
    const d = lastDocs[idx];
    const prev = selIdx;
    selIdx = idx;
    const isOverseas = currentRegion === 'overseas';

    if (isOverseas && resultMapGoogle) {
        grMarkers.forEach((m, i) => {
            const sel = i === idx;
            m.setIcon({
                url: 'data:image/svg+xml;charset=UTF-8,' + (sel ? PIN_GOLD : PIN_GRAY),
                scaledSize: new google.maps.Size(sel ? 28 : 24, sel ? 38 : 34),
                anchor: new google.maps.Point(sel ? 14 : 12, sel ? 38 : 34),
                labelOrigin: new google.maps.Point(sel ? 14 : 12, -8),
            });
            m.setZIndex(sel ? 200 : 100);
        });
        resultMapGoogle.panTo({ lat: +d.y, lng: +d.x });
    } else if (resultMapNaver) {
        rMarkers.forEach((m, i) => {
            m.setIcon({
                content: buildRPin(lastDocs[i].place_name, i === idx),
                anchor: new naver.maps.Point(0, 0),
            });
            m.setZIndex(i === idx ? 200 : 100);
        });
        resultMapNaver.panTo(new naver.maps.LatLng(+d.y, +d.x));
    }

    document.querySelectorAll('.sl__sheet-item').forEach((el, i) => {
        el.classList.toggle('is-active', i === idx);
    });
    const item = document.querySelector(`.sl__sheet-item[data-idx="${idx}"]`);
    if (item) item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    showIw(d);
}

function showIw(d) {
    document.getElementById('slIwName').textContent = d.place_name || '';
    document.getElementById('slIwAddr').textContent = d.road_address_name || d.address_name || '';
    const distEl = document.getElementById('slIwDist');
    distEl.textContent = (userLat !== null && d.y && d.x) ? fmtDist(haversine(userLat, userLng, +d.y, +d.x)) : '';
    document.getElementById('slIw').hidden = false;
    document.getElementById('slIw')._doc = d;
}

document.getElementById('slIwClose').addEventListener('click', () => {
    document.getElementById('slIw').hidden = true;
});
document.getElementById('slIwSave').addEventListener('click', () => {
    const d = document.getElementById('slIw')._doc;
    if (d) pickPlace(d);
});

function updateViewToggleBtn() {
    const label = document.getElementById('slViewToggleText');
    const icon = document.getElementById('slViewToggleIcon');
    if (resultViewMode === 'map') {
        label.textContent = '리스트';
        icon.innerHTML = '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>';
    } else {
        label.textContent = '지도';
        icon.innerHTML = '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0z"/><circle cx="12" cy="10" r="3"/>';
    }
}

document.getElementById('slViewToggle').addEventListener('click', () => {
    if (resultViewMode === 'map') {
        resultViewMode = 'list';
        document.getElementById('slResult').hidden = true;
        slAc.hidden = false;
    } else {
        resultViewMode = 'map';
        slAc.hidden = true;
        document.getElementById('slResult').hidden = false;
        if (currentRegion === 'overseas' && resultMapGoogle) {
            google.maps.event.trigger(resultMapGoogle, 'resize');
        } else if (resultMapNaver) {
            naver.maps.Event.trigger(resultMapNaver, 'resize');
        }
    }
    updateViewToggleBtn();
});

// 바텀시트 드래그
(function() {
    const bar = document.getElementById('slSheetBar');
    const sheet = document.getElementById('slResultSheet');
    let dragging = false, startY = 0, startH = 0;

    bar.addEventListener('touchstart', e => {
        dragging = true;
        startY = e.touches[0].clientY;
        startH = sheet.offsetHeight;
        sheet.style.transition = 'none';
    }, { passive: true });

    document.addEventListener('touchmove', e => {
        if (!dragging) return;
        const dy = startY - e.touches[0].clientY;
        const parent = sheet.parentElement;
        if (!parent) return;
        const maxH = parent.offsetHeight * 0.85;
        const minH = 80;
        sheet.style.height = Math.max(minH, Math.min(maxH, startH + dy)) + 'px';
    }, { passive: true });

    document.addEventListener('touchend', () => {
        if (!dragging) return;
        dragging = false;
        sheet.style.transition = '';
        const parent = sheet.parentElement;
        if (!parent) return;
        const threshold = parent.offsetHeight * 0.5;
        if (sheet.offsetHeight > threshold) {
            sheet.classList.add('is-expanded');
        } else {
            sheet.classList.remove('is-expanded');
        }
        sheet.style.height = '';
    });
})();

// =========================================
// 주소 직접입력 / 다음 우편번호 검색
// =========================================
const fRoad = document.getElementById('f_road');
const fAddr = document.getElementById('f_addr');
const fLat = document.getElementById('f_lat');
const fLng = document.getElementById('f_lng');
const fKpid = document.getElementById('f_kpid');

let addrLayerOpen = false;
function openAddressSearch() {
    if (currentRegion === 'overseas') {
        alert('해외 주소 검색은 지원하지 않아요. 상단 "장소 검색"을 이용해주세요.');
        return;
    }
    if (typeof daum === 'undefined' || !daum.Postcode) {
        alert('주소 검색을 불러오지 못했어요. 잠시 후 다시 시도해주세요.');
        return;
    }
    if (addrLayerOpen) return;
    addrLayerOpen = true;

    const wrap = document.getElementById('addrSearchLayer');
    const inner = document.getElementById('addrSearchLayerInner');
    inner.innerHTML = '';
    wrap.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    new daum.Postcode({
        oncomplete: function (data) {
            const road = data.roadAddress || '';
            const jibun = data.jibunAddress || data.autoJibunAddress || '';
            const addr = road || jibun;
            fRoad.value = road || jibun;
            fAddr.value = jibun;
            fKpid.value = '';
            fLat.value = '';
            fLng.value = '';
            fetch('/api/geocode/forward?q=' + encodeURIComponent(addr) + '&overseas=0')
                .then(r => r.json())
                .then(d => {
                    if (d.lat && d.lng) {
                        fLat.value = d.lat;
                        fLng.value = d.lng;
                    }
                })
                .catch(() => {});
            closeAddressSearch();
        },
        width: '100%',
        height: '100%',
    }).embed(inner);
}
function closeAddressSearch() {
    document.getElementById('addrSearchLayer').style.display = 'none';
    document.body.style.overflow = '';
    addrLayerOpen = false;
    fRoad.blur();
}
document.getElementById('addrSearchBtn').addEventListener('click', openAddressSearch);
fRoad.addEventListener('mousedown', (e) => { e.preventDefault(); openAddressSearch(); });
fRoad.style.cursor = 'pointer';
document.getElementById('addrSearchClose').addEventListener('click', closeAddressSearch);
document.getElementById('addrSearchLayer').addEventListener('click', (e) => {
    if (e.target.id === 'addrSearchLayer') closeAddressSearch();
});

// =========================================
// 3) 지도에서 찍기 탭
// =========================================
let mappinMap = null;      // 네이버 (국내)
let mappinInited = false;
let gMappinMap = null;     // 구글 (해외)
let gMappinInited = false;
let geoTimer = null;
let mappinMarkers = [];    // 네이버 검색 결과 마커
let gMappinMarkers = [];   // 구글 검색 결과 마커

// 검색창 키워드로 검색 결과 전체를 가져오는 헬퍼
async function getQueryResults() {
    const q = slInput.value.trim();
    if (q.length < 2) return [];
    const isOverseas = currentRegion === 'overseas';
    const endpoint = isOverseas ? '/api/search/overseas' : '/api/search';
    try {
        const params = new URLSearchParams({ q });
        if (userLat !== null) { params.set('lat', userLat); params.set('lng', userLng); }
        const r = await fetch(endpoint + '?' + params.toString());
        const data = await r.json();
        return (data.documents || []).filter(d => d.x && d.y);
    } catch (e) {}
    return [];
}

async function switchMappinMap() {
    const isOverseas = currentRegion === 'overseas';
    document.getElementById('slMappinMap').style.display = isOverseas ? 'none' : 'block';
    document.getElementById('slMappinMapGoogle').style.display = isOverseas ? 'block' : 'none';

    const docs = await getQueryResults();
    const loc = docs.length ? { lat: +docs[0].y, lng: +docs[0].x } : null;

    if (isOverseas) {
        await initGoogleMappinMap(loc);
        if (gMappinMap) renderGMappinMarkers(docs);
    } else {
        initNaverMappinMap(loc);
        if (mappinMap) renderNaverMappinMarkers(docs);
    }
}

function clearNaverMarkers() {
    mappinMarkers.forEach(m => m.setMap(null));
    mappinMarkers = [];
}

function buildPinpickMarkerHtml(name) {
    const label = escapeHtml(name || '');
    return `<div class="pp-mappin-anchor"><div class="pp-mappin">
            <div class="pp-mappin__bubble">
                <span class="pp-mappin__pin"></span>
                <span class="pp-mappin__label">${label}</span>
            </div>
        </div></div>`;
}

function renderNaverMappinMarkers(docs) {
    clearNaverMarkers();
    if (!docs.length) return;
    const bounds = new naver.maps.LatLngBounds();
    docs.forEach(d => {
        const pos = new naver.maps.LatLng(+d.y, +d.x);
        const marker = new naver.maps.Marker({
            position: pos,
            map: mappinMap,
            title: d.place_name || '',
            icon: {
                content: buildPinpickMarkerHtml(d.place_name),
                anchor: new naver.maps.Point(0, 0),
            },
            zIndex: 100,
        });
        naver.maps.Event.addListener(marker, 'click', () => pickPlace(d));
        mappinMarkers.push(marker);
        bounds.extend(pos);
    });
    if (docs.length === 1) {
        mappinMap.setCenter(new naver.maps.LatLng(+docs[0].y, +docs[0].x));
        mappinMap.setZoom(16);
    } else {
        mappinMap.fitBounds(bounds);
    }
}

function clearGMarkers() {
    gMappinMarkers.forEach(m => m.setMap(null));
    gMappinMarkers = [];
}

const PP_PIN_SVG = encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" width="32" height="42" viewBox="0 0 32 42"><defs><filter id="s" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="2" stdDeviation="1.5" flood-color="#000" flood-opacity="0.25"/></filter></defs><path filter="url(#s)" d="M16 2C8.27 2 2 8.27 2 16c0 9.5 14 24 14 24s14-14.5 14-24c0-7.73-6.27-14-14-14z" fill="#FF3D77" stroke="#fff" stroke-width="2"/><circle cx="16" cy="16" r="5" fill="#fff"/></svg>`);

function renderGMappinMarkers(docs) {
    clearGMarkers();
    if (!docs.length) return;
    const bounds = new google.maps.LatLngBounds();
    docs.forEach(d => {
        const pos = { lat: +d.y, lng: +d.x };
        const marker = new google.maps.Marker({
            position: pos, map: gMappinMap, title: d.place_name || '',
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + PP_PIN_SVG,
                scaledSize: new google.maps.Size(32, 42),
                anchor: new google.maps.Point(16, 42),
                labelOrigin: new google.maps.Point(16, -8),
            },
            label: {
                text: d.place_name || '',
                color: '#2b211e',
                fontSize: '12px',
                fontWeight: '600',
                className: 'pp-gmarker-label',
            },
        });
        marker.addListener('click', () => pickPlace(d));
        gMappinMarkers.push(marker);
        bounds.extend(pos);
    });
    if (docs.length === 1) {
        gMappinMap.setCenter({ lat: +docs[0].y, lng: +docs[0].x });
        gMappinMap.setZoom(16);
    } else {
        gMappinMap.fitBounds(bounds);
    }
}

function initNaverMappinMap(initialLoc) {
    if (typeof naver === 'undefined') return;
    if (mappinInited) {
        if (mappinMap) setTimeout(() => naver.maps.Event.trigger(mappinMap, 'resize'), 50);
        return;
    }
    mappinInited = true;

    const loc = initialLoc
        || (userLat !== null ? { lat: userLat, lng: userLng } : null);
    const center = loc
        ? new naver.maps.LatLng(loc.lat, loc.lng)
        : new naver.maps.LatLng(37.5665, 126.9780);
    mappinMap = new naver.maps.Map('slMappinMap', {
        center: center, zoom: 15,
        zoomControl: false, scaleControl: false, mapDataControl: false,
    });
    if (!loc) moveToCurrentLocation('naver');
    naver.maps.Event.addListener(mappinMap, 'idle', () => {
        clearTimeout(geoTimer);
        geoTimer = setTimeout(() => reverseGeocodeNaver(), 200);
    });
    setTimeout(() => naver.maps.Event.trigger(mappinMap, 'resize'), 100);
}

async function initGoogleMappinMap(initialLoc) {
    if (gMappinInited) {
        if (gMappinMap) {
            setTimeout(() => google.maps.event.trigger(gMappinMap, 'resize'), 50);
        }
        return;
    }
    gMappinInited = true;
    try {
        await _gmReady;
        await new Promise(r => setTimeout(r, 50));
        const loc = initialLoc
            || (userLat !== null ? { lat: userLat, lng: userLng } : null);
        const center = loc
            ? { lat: loc.lat, lng: loc.lng }
            : { lat: 37.5665, lng: 126.9780 };
        gMappinMap = new google.maps.Map(document.getElementById('slMappinMapGoogle'), {
            center: center, zoom: 15, disableDefaultUI: true,
        });
        if (!loc) moveToCurrentLocation('google');
        gMappinMap.addListener('idle', () => {
            clearTimeout(geoTimer);
            geoTimer = setTimeout(() => reverseGeocodeGoogle(), 200);
        });
    } catch (e) {
        console.error('Google Map init error:', e);
    }
}

// 현위치 버튼
document.getElementById('slGps').addEventListener('click', () => {
    moveToCurrentLocation(currentRegion === 'overseas' ? 'google' : 'naver');
});

function moveToCurrentLocation(type) {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(pos => {
        const lat = pos.coords.latitude, lng = pos.coords.longitude;
        if (type === 'google' && gMappinMap) {
            gMappinMap.setCenter({ lat, lng });
        } else if (type === 'naver' && mappinMap) {
            mappinMap.setCenter(new naver.maps.LatLng(lat, lng));
        }
    }, () => {}, { enableHighAccuracy: true, timeout: 5000 });
}

// 저장 버튼
document.getElementById('slMappinSave').addEventListener('click', () => {
    let lat, lng;
    if (currentRegion === 'overseas' && gMappinMap) {
        const c = gMappinMap.getCenter();
        lat = c.lat(); lng = c.lng();
    } else if (mappinMap) {
        const c = mappinMap.getCenter();
        lat = c.lat(); lng = c.lng();
    }
    if (lat !== undefined) {
        document.getElementById('f_lat').value = lat;
        document.getElementById('f_lng').value = lng;
        document.getElementById('f_road').value = document.getElementById('slMappinAddr').textContent || '';
        document.getElementById('f_addr').value = '';
        document.getElementById('f_name').value = '';
        document.getElementById('f_kpid').value = '';
        document.getElementById('f_gpid').value = '';
        document.getElementById('f_overseas').value = currentRegion === 'overseas' ? '1' : '0';
    }
    closeSL();
    document.getElementById('f_name').focus();
});

// 역지오코딩 — 백엔드 프록시 통합 (provider=naver|google)
async function doReverseGeocode(lat, lng, provider) {
    const el = document.getElementById('slMappinAddr');
    el.textContent = '주소 확인 중...';
    try {
        const r = await fetch(`/api/geocode/reverse?lat=${lat}&lng=${lng}&provider=${provider}`);
        const j = await r.json();
        el.textContent = j.address || '주소를 확인할 수 없어요';
    } catch (e) {
        el.textContent = '주소를 확인할 수 없어요';
    }
}

function reverseGeocodeNaver() {
    if (!mappinMap) return;
    const c = mappinMap.getCenter();
    doReverseGeocode(c.lat(), c.lng(), 'naver');
    fetchNearbyPlaces(c.lat(), c.lng());
}

function reverseGeocodeGoogle() {
    if (!gMappinMap) return;
    const c = gMappinMap.getCenter();
    doReverseGeocode(c.lat(), c.lng(), 'google');
    fetchNearbyPlaces(c.lat(), c.lng());
}

async function fetchNearbyPlaces(lat, lng) {
    const box = document.getElementById('slMappinNearby');
    box.innerHTML = '<div class="sl__nearby-loading">주변 장소 검색 중...</div>';
    try {
        const isOverseas = currentRegion === 'overseas';
        const endpoint = isOverseas ? '/api/search/nearby-overseas' : '/api/search/nearby';
        const r = await fetch(`${endpoint}?lat=${lat}&lng=${lng}`);
        const data = await r.json();
        const docs = data.documents || [];
        if (!docs.length) {
            box.innerHTML = '<div class="sl__nearby-empty">이 근처에 등록된 장소가 없어요</div>';
            return;
        }
        box.innerHTML = '<div class="sl__nearby-title">📍 주변 장소</div>' +
            docs.map(d => {
                const dist = d.distance ? `<span class="sl__nearby-dist">${d.distance >= 1000 ? (d.distance/1000).toFixed(1)+'km' : d.distance+'m'}</span>` : '';
                const cat = d.category_group_name || '';
                return `<button type="button" class="sl__nearby-item" data-doc='${JSON.stringify(d).replace(/'/g,"&#39;")}'>
                    <div class="sl__nearby-info">
                        <span class="sl__nearby-name">${escapeHtml(d.place_name)}</span>
                        ${cat ? `<span class="sl__nearby-cat">${escapeHtml(cat)}</span>` : ''}
                    </div>
                    <div class="sl__nearby-meta">
                        <span class="sl__nearby-addr">${escapeHtml(d.road_address_name || d.address_name || '')}</span>
                        ${dist}
                    </div>
                </button>`;
            }).join('');
        box.querySelectorAll('.sl__nearby-item').forEach(btn => {
            btn.addEventListener('click', () => {
                const d = JSON.parse(btn.dataset.doc);
                pickPlace(d);
            });
        });
    } catch(e) {
        box.innerHTML = '<div class="sl__nearby-empty">장소를 불러올 수 없어요</div>';
    }
}

// =========================================
// 4) 카테고리 관리 (홈과 동일한 yg-catorder-panel 패턴)
// =========================================
function ppToast(msg, isError) {
    const el = document.createElement('div');
    el.className = 'pp-flash' + (isError ? ' pp-flash--error' : '');
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => {
        el.style.transition = 'opacity .4s, transform .4s';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-6px)';
        setTimeout(() => el.remove(), 450);
    }, 2000);
}

const catHidden = document.getElementById('f_category');
const catTrigger = document.getElementById('catTrigger');
const catChipsBox = document.getElementById('catChips');
const catPanel = document.getElementById('catOrderPanel');
const catList = document.getElementById('catOrderList');
const catAddBtn = document.getElementById('catOrderAdd');
const catCancelBtn = document.getElementById('catOrderCancel');
const catDoneBtn = document.getElementById('catOrderDone');

let catState = JSON.parse(document.getElementById('initialCategories').textContent);

function renderChips() {
    catChipsBox.innerHTML = '';
    catState.forEach(c => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pp-chip';
        btn.dataset.cid = c.id;
        if (String(catHidden.value) === String(c.id)) btn.classList.add('is-active');
        btn.innerHTML = `<span class="pp-chip__label">${escapeHtml(c.name)}</span>`;
        btn.addEventListener('click', () => selectCategory(c.id));
        catChipsBox.appendChild(btn);
    });
}

function selectCategory(id) {
    catHidden.value = id || '';
    catChipsBox.querySelectorAll('.pp-chip').forEach(c => {
        c.classList.toggle('is-active', String(c.dataset.cid) === String(id));
    });
    const sel = document.getElementById('catSelected');
    const selName = document.getElementById('catSelectedName');
    const found = catState.find(c => String(c.id) === String(id));
    if (found) { selName.textContent = found.name; sel.hidden = false; }
    else { sel.hidden = true; }
}

function openCatPanel() {
    renderCatOrderList();
    catPanel.hidden = false;
}
function closeCatPanel() {
    catPanel.hidden = true;
}

catTrigger.addEventListener('click', openCatPanel);
document.getElementById('catSelectedEdit').addEventListener('click', openCatPanel);
catCancelBtn.addEventListener('click', closeCatPanel);

// ?category=ID 쿼리 → 신규 작성 시 카테고리 기본 선택
if (!editMode && !catHidden.value) {
    const qCat = new URLSearchParams(location.search).get('category');
    if (qCat && catState.some(c => String(c.id) === String(qCat))) {
        catHidden.value = qCat;
    }
}
renderChips();
if (catHidden.value) selectCategory(catHidden.value);

function guestAlert() {
    if (confirm('카테고리 편집은 로그인 후 사용할 수 있어요. 로그인하시겠어요?')) location.href = '{{ route('login') }}';
}

function renderCatOrderList() {
    catList.innerHTML = '';
    catState.forEach((c, i) => {
        const li = document.createElement('li');
        li.className = 'yg-catorder-item';
        li.dataset.id = c.id;
        li.dataset.origName = c.name;
        li.innerHTML = `
            <span class="yg-catorder-item__grip">☰</span>
            <input class="yg-catorder-item__input" type="text" value="${escapeHtml(c.name)}" maxlength="30">
            <div class="yg-catorder-item__btns">
                <button type="button" class="yg-catorder-item__up" ${i === 0 ? 'disabled' : ''} aria-label="위로">↑</button>
                <button type="button" class="yg-catorder-item__down" ${i === catState.length - 1 ? 'disabled' : ''} aria-label="아래로">↓</button>
                <button type="button" class="yg-catorder-item__del" aria-label="삭제">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
            </div>
        `;
        li.querySelector('.yg-catorder-item__up').addEventListener('click', () => moveCatItem(li, -1));
        li.querySelector('.yg-catorder-item__down').addEventListener('click', () => moveCatItem(li, 1));
        li.querySelector('.yg-catorder-item__del').addEventListener('click', () => deleteCatItem(li));
        catList.appendChild(li);
    });
}

function moveCatItem(li, dir) {
    const items = Array.from(catList.children);
    const idx = items.indexOf(li);
    const swapIdx = idx + dir;
    if (swapIdx < 0 || swapIdx >= items.length) return;
    // 모바일 터치 잔상 방지 — 눌린 버튼의 포커스 해제
    if (document.activeElement) document.activeElement.blur();
    if (dir === -1) catList.insertBefore(li, items[swapIdx]);
    else catList.insertBefore(items[swapIdx], li);
    refreshUpDown();
    // 이동된 항목에 잠시 하이라이트
    li.classList.add('yg-catorder-item--moved');
    setTimeout(() => li.classList.remove('yg-catorder-item--moved'), 600);
}

function refreshUpDown() {
    Array.from(catList.children).forEach((item, i, arr) => {
        item.querySelector('.yg-catorder-item__up').disabled = i === 0;
        item.querySelector('.yg-catorder-item__down').disabled = i === arr.length - 1;
    });
}

async function deleteCatItem(li) {
    if (isGuest) return guestAlert();
    const id = li.dataset.id;
    const name = li.querySelector('.yg-catorder-item__input').value;
    if (!confirm(`'${name}' 카테고리를 삭제할까요?`)) return;
    try {
        const r = await fetch(`/api/categories/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        const j = await r.json();
        if (!j.ok) { ppToast(j.error || '삭제 실패', true); return; }
        li.remove();
        refreshUpDown();
        catState = catState.filter(x => String(x.id) !== String(id));
        if (String(catHidden.value) === String(id)) selectCategory('');
        renderChips();
        ppToast('카테고리가 삭제되었어요');
    } catch (e) { ppToast('네트워크 오류', true); }
}

catDoneBtn.addEventListener('click', async () => {
    if (isGuest) return guestAlert();
    const items = Array.from(catList.querySelectorAll('.yg-catorder-item'));

    // 1) 이름 변경
    for (const li of items) {
        const input = li.querySelector('.yg-catorder-item__input');
        const origName = li.dataset.origName;
        const newName = input.value.trim();
        if (newName && newName !== origName) {
            try {
                const r = await fetch(`/api/categories/${li.dataset.id}`, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ name: newName })
                });
                const j = await r.json();
                if (!j.ok) { ppToast(j.error || '이름 변경 실패: ' + origName, true); continue; }
                const found = catState.find(x => String(x.id) === String(li.dataset.id));
                if (found) found.name = newName;
            } catch (e) { ppToast('네트워크 오류', true); }
        }
    }

    // 2) 순서 저장
    const ids = items.map(li => +li.dataset.id);
    try {
        const r = await fetch('/api/categories/reorder', {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ order: ids })
        });
        const j = await r.json();
        if (!j.ok) { ppToast('순서 저장 실패', true); return; }
    } catch (e) { ppToast('네트워크 오류', true); return; }

    // 3) 로컬 state 순서 반영
    catState.sort((a, b) => ids.indexOf(+a.id) - ids.indexOf(+b.id));

    renderChips();
    closeCatPanel();
    ppToast('카테고리가 저장되었어요');
});

catAddBtn.addEventListener('click', async () => {
    if (isGuest) return guestAlert();
    const name = prompt('새 카테고리 이름을 입력하세요');
    if (!name || !name.trim()) return;
    try {
        const r = await fetch('{{ route('api.categories.store') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ name: name.trim() }) });
        const j = await r.json();
        if (!j.ok) { ppToast('추가 실패', true); return; }
        catState.unshift({ id: j.item.id, name: j.item.name, is_default: false });
        renderCatOrderList();
        renderChips();
        ppToast('새 카테고리가 추가되었어요');
    } catch (e) { ppToast('네트워크 오류', true); }
});

// =========================================
// 5) 이미지 첨부 (최대 5장)
// =========================================
const IMG_MAX = 5;
const imgFileInput = document.getElementById('imgFileInput');
const imgPreview = document.getElementById('imgPreview');
const imgAddBtn = document.getElementById('imgAddBtn');
const imgCountEl = document.getElementById('imgCount');
let imgFiles = []; // 신규 파일
let existingImgCount = imgPreview.querySelectorAll('.pp-images__item[data-existing-id]').length;

function totalImgCount() { return existingImgCount + imgFiles.length; }

function updateImgCount() {
    imgCountEl.textContent = `(${totalImgCount()}/${IMG_MAX})`;
    imgAddBtn.style.display = totalImgCount() >= IMG_MAX ? 'none' : '';
}

function renderNewImgPreviews() {
    imgPreview.querySelectorAll('.pp-images__item:not([data-existing-id])').forEach(el => el.remove());
    imgFiles.forEach((file, i) => {
        const div = document.createElement('div');
        div.className = 'pp-images__item';
        div.innerHTML = `
            <img src="${URL.createObjectURL(file)}" alt="">
            <button type="button" class="pp-images__del pp-images__del--new" data-idx="${i}" aria-label="삭제">&times;</button>
        `;
        imgPreview.insertBefore(div, imgAddBtn);
    });
    updateImgCount();
    syncFileInput();
}

function syncFileInput() {
    const dt = new DataTransfer();
    imgFiles.forEach(f => dt.items.add(f));
    imgFileInput.files = dt.files;
}

imgAddBtn.addEventListener('click', () => {
    if (totalImgCount() >= IMG_MAX) return;
    imgFileInput.click();
});

imgFileInput.addEventListener('change', () => {
    const files = Array.from(imgFileInput.files);
    const remain = IMG_MAX - totalImgCount();
    if (remain <= 0) return;
    const toAdd = files.slice(0, remain);
    for (const f of toAdd) {
        if (f.size > 10 * 1024 * 1024) {
            alert('이미지는 10MB 이하만 첨부할 수 있어요.');
            return;
        }
    }
    imgFiles.push(...toAdd);
    renderNewImgPreviews();
});

imgPreview.addEventListener('click', async (e) => {
    const delBtn = e.target.closest('.pp-images__del');
    if (!delBtn) return;

    // 기존 이미지 삭제 (서버에 API 호출)
    if (delBtn.classList.contains('pp-images__del--existing')) {
        const imgId = delBtn.dataset.imgId;
        if (!confirm('이 사진을 삭제할까요?')) return;
        try {
            const r = await fetch(`/api/place-images/${imgId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const j = await r.json();
            if (j.ok) {
                delBtn.closest('.pp-images__item').remove();
                existingImgCount--;
                updateImgCount();
            }
        } catch (err) { alert('삭제 실패'); }
        return;
    }

    // 신규 이미지 삭제
    const idx = +delBtn.dataset.idx;
    imgFiles.splice(idx, 1);
    renderNewImgPreviews();
});

imgFileInput.setAttribute('name', 'images[]');
updateImgCount();

// =========================================
// 6) 방문 상태 토글
// =========================================
document.querySelectorAll('.pp-seg button').forEach(b => {
    b.addEventListener('click', () => {
        document.querySelectorAll('.pp-seg button').forEach(x => x.classList.remove('is-active'));
        b.classList.add('is-active');
        const s = b.dataset.status;
        document.getElementById('f_status').value = s;
        document.getElementById('visitedDateField').style.display = s === 'visited' ? 'block' : 'none';
    });
});

// =========================================
// 7) 비로그인 게스트 localStorage 저장
// =========================================
@guest
const GUEST_KEY = 'pinpick_guest_places';
const GUEST_MAX = 5;
const guestCatMap = { @foreach($categories as $c) {{ $c->id }}: { name: @json($c->name), icon: @json($c->icon) }, @endforeach };

document.getElementById('placeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const list = JSON.parse(localStorage.getItem(GUEST_KEY) || '[]');
    if (list.length >= GUEST_MAX) {
        alert('비로그인 상태에서는 최대 ' + GUEST_MAX + '개까지 저장할 수 있어요.\n더 저장하려면 로그인해주세요.');
        return;
    }
    const fd = new FormData(this);
    const cid = fd.get('category_id') || null;
    const cinfo = cid && guestCatMap[cid] ? guestCatMap[cid] : { name: '기타', icon: '📌' };
    const item = {
        id: 'g' + Date.now(),
        name: fd.get('name'),
        category_id: cid ? +cid : null,
        category_name: cinfo.name,
        category_icon: cinfo.icon,
        phone: fd.get('phone') || '',
        road_address: fd.get('road_address') || '',
        address: fd.get('address') || '',
        lat: fd.get('lat') || '',
        lng: fd.get('lng') || '',
        memo: fd.get('memo') || '',
        status: fd.get('status'),
        visited_at: fd.get('visited_at') || '',
        is_overseas: currentRegion === 'overseas',
        created_at: Date.now(),
    };
    list.unshift(item);
    localStorage.setItem(GUEST_KEY, JSON.stringify(list));
    location.href = '/?saved=1';
});
@endguest
</script>
@endpush
