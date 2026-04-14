@php $editMode = isset($place); @endphp
@extends('layouts.app')
@section('title', $editMode ? '장소 수정' : '장소 추가')

@section('header')
<header class="pp-header">
    <button class="pp-header__icon" onclick="history.back()" aria-label="뒤로">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
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
                <button type="button" class="pp-cat-manage" id="catTrigger">
                    <span>＋ 카테고리 추가</span>
                </button>
            </div>
            <input type="hidden" name="category_id" id="f_category" value="{{ $editMode ? $place->category_id : '' }}">
            <div class="pp-chips pp-chips--scroll" id="catChips"></div>
            <div class="pp-cat-selected" id="catSelected" hidden>
                <span class="pp-cat-selected__name" id="catSelectedName"></span>
                <button type="button" class="pp-cat-selected__edit" id="catSelectedEdit">수정</button>
            </div>
        </div>

        <div class="pp-field">
            <label class="pp-label">전화번호</label>
            <input class="pp-input" name="phone" id="f_phone" inputmode="tel" value="{{ $editMode ? $place->phone : '' }}">
        </div>

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
            <input type="hidden" name="is_overseas" id="f_overseas" value="{{ $editMode && $place->is_overseas ? '1' : '0' }}">
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
                        <img src="{{ asset('storage/' . $img->path) }}" alt="">
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

        <div class="pp-field">
            <label class="pp-label">방문 상태</label>
            <div class="pp-seg">
                <button type="button" class="{{ ($editMode ? $place->status : 'planned') === 'planned' ? 'is-active' : '' }}" data-status="planned">방문예정</button>
                <button type="button" class="{{ ($editMode ? $place->status : '') === 'visited' ? 'is-active' : '' }}" data-status="visited">방문완료</button>
            </div>
            <input type="hidden" name="status" id="f_status" value="{{ $editMode ? $place->status : 'planned' }}">
        </div>

        <div class="pp-field" id="visitedDateField" style="{{ ($editMode && $place->status === 'visited') ? '' : 'display:none' }}">
            <label class="pp-label">방문 날짜</label>
            <input type="date" class="pp-input" name="visited_at" value="{{ $editMode && $place->visited_at ? $place->visited_at->format('Y-m-d') : '' }}">
        </div>

        <button class="pp-btn" type="submit">{{ $editMode ? '수정하기' : '저장하기' }}</button>
    </form>
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
            {{-- 자동완성 결과 리스트 --}}
            <div class="sl__ac" id="slAc" hidden>
                <div class="sl__ac-list" id="slAcList"></div>
            </div>
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
            </div>
            {{-- 하단 주소 + 저장 --}}
            <div class="sl__mappin-bottom">
                <div class="sl__mappin-addr" id="slMappinAddr">지도를 움직여 위치를 지정하세요</div>
                <button type="button" class="sl__mappin-save" id="slMappinSave">이 위치로 저장</button>
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

{{-- 카테고리 관리 레이어 --}}
<div class="pp-catmgr" id="catmgrLayer" hidden>
    <div class="pp-catmgr__backdrop" data-catmgr-close></div>
    <div class="pp-catmgr__sheet">
        <div class="pp-catmgr__head">
            <h3>카테고리 관리</h3>
            <button type="button" class="pp-catmgr__done" data-catmgr-close>완료</button>
        </div>
        <div class="pp-catmgr__body">
            <ul class="pp-catmgr__list" id="catmgrList"></ul>
            <button type="button" class="pp-catmgr__add" id="catmgrAdd">＋ 새 카테고리 추가</button>
        </div>
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
}
function closeSL() {
    SL.classList.remove('is-open');
    document.body.style.overflow = '';
    slInput.value = '';
    slClear.hidden = true;
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
    slRecent.hidden = false;
    renderRecent();
}

// 카테고리 아이콘 매핑 (국내 카카오)
function getCategoryIcon(cat) {
    if (!cat) return '📍';
    if (/음식점|맛집/.test(cat)) return '🍽';
    if (/카페/.test(cat)) return '☕';
    if (/숙박|호텔|모텔|펜션/.test(cat)) return '🏨';
    if (/관광|여행|문화/.test(cat)) return '🎭';
    if (/쇼핑|마트|백화점/.test(cat)) return '🛍';
    if (/병원|약국|의료/.test(cat)) return '🏥';
    if (/주차|주유/.test(cat)) return '🅿️';
    if (/편의점/.test(cat)) return '🏪';
    if (/지하철|버스|교통/.test(cat)) return '🚇';
    return '📍';
}

// 해외 Google 타입 아이콘 매핑
function getGoogleTypeIcon(types) {
    if (!types || !types.length) return '📍';
    const t = types.join(' ');
    if (/restaurant|food|meal|bakery/.test(t)) return '🍽';
    if (/cafe|coffee/.test(t)) return '☕';
    if (/lodging|hotel|motel|resort/.test(t)) return '🏨';
    if (/museum|tourist|amusement|landmark/.test(t)) return '🎭';
    if (/shopping|store|mall|market/.test(t)) return '🛍';
    if (/hospital|pharmacy|doctor|health/.test(t)) return '🏥';
    if (/airport|station|transit/.test(t)) return '🚇';
    return '📍';
}

async function doSearch(q) {
    saveRecent(q);
    const isOverseas = currentRegion === 'overseas';

    try {
        if (isOverseas) {
            // 해외: Google Autocomplete
            const r = await fetch('/api/search/overseas/autocomplete?q=' + encodeURIComponent(q));
            const data = await r.json();
            const suggestions = data.suggestions || [];
            if (!suggestions.length) {
                slRecent.hidden = true; slAc.hidden = true; slEmpty.hidden = false;
                return;
            }
            slRecent.hidden = true; slEmpty.hidden = true; slAc.hidden = false;
            renderAcOverseas(suggestions);
        } else {
            // 국내: Kakao 키워드 검색
            const r = await fetch('/api/search?q=' + encodeURIComponent(q));
            const data = await r.json();
            const docs = data.documents || [];
            if (!docs.length) {
                slRecent.hidden = true; slAc.hidden = true; slEmpty.hidden = false;
                return;
            }
            slRecent.hidden = true; slEmpty.hidden = true; slAc.hidden = false;
            renderAcDomestic(docs);
        }
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

// 해외 자동완성 렌더링 (Google Autocomplete 결과)
function renderAcOverseas(suggestions) {
    slAcList.innerHTML = suggestions.map((s, i) => `
        <div class="sl__ac-item" data-i="${i}">
            <div class="sl__ac-icon">${getGoogleTypeIcon(s.types)}</div>
            <div class="sl__ac-body">
                <div class="sl__ac-name">${escapeHtml(s.name)}</div>
                <div class="sl__ac-desc">${escapeHtml(s.description)}</div>
            </div>
        </div>
    `).join('');
    slAcList.querySelectorAll('.sl__ac-item').forEach(el => {
        el.addEventListener('click', async () => {
            const s = suggestions[+el.dataset.i];
            // 로딩 표시
            el.classList.add('is-loading');
            try {
                const r = await fetch('/api/place/detail?place_id=' + encodeURIComponent(s.place_id));
                const d = await r.json();
                if (d.error) { alert('장소 정보를 불러올 수 없어요'); return; }
                pickPlace(d);
            } catch (e) {
                alert('네트워크 오류가 발생했어요');
            } finally {
                el.classList.remove('is-loading');
            }
        });
    });
}

function pickPlace(d) {
    document.getElementById('f_name').value = d.place_name || '';
    document.getElementById('f_phone').value = d.phone || '';
    document.getElementById('f_road').value = d.road_address_name || d.address_name || '';
    document.getElementById('f_addr').value = d.address_name || '';
    document.getElementById('f_lat').value = d.y || '';
    document.getElementById('f_lng').value = d.x || '';
    document.getElementById('f_kpid').value = d.id || '';
    document.getElementById('f_overseas').value = currentRegion === 'overseas' ? '1' : '0';
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
        const r = await fetch(endpoint + '?q=' + encodeURIComponent(q));
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
    return `
        <div class="pp-mappin">
            <div class="pp-mappin__bubble">
                <span class="pp-mappin__pin"></span>
                <span class="pp-mappin__label">${label}</span>
            </div>
        </div>
    `;
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
                color: '#1a1a1a',
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

    const center = initialLoc
        ? new naver.maps.LatLng(initialLoc.lat, initialLoc.lng)
        : new naver.maps.LatLng(37.5665, 126.9780);
    mappinMap = new naver.maps.Map('slMappinMap', {
        center: center, zoom: 15,
        zoomControl: false, scaleControl: false, mapDataControl: false,
    });
    if (!initialLoc) moveToCurrentLocation('naver');
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
        const center = initialLoc
            ? { lat: initialLoc.lat, lng: initialLoc.lng }
            : { lat: 37.5665, lng: 126.9780 };
        gMappinMap = new google.maps.Map(document.getElementById('slMappinMapGoogle'), {
            center: center, zoom: 15, disableDefaultUI: true,
        });
        if (!initialLoc) moveToCurrentLocation('google');
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
}

function reverseGeocodeGoogle() {
    if (!gMappinMap) return;
    const c = gMappinMap.getCenter();
    doReverseGeocode(c.lat(), c.lng(), 'google');
}

// =========================================
// 4) 카테고리 관리 (기존 로직 유지)
// =========================================
const catHidden = document.getElementById('f_category');
const catTrigger = document.getElementById('catTrigger');
const catChipsBox = document.getElementById('catChips');
const catLayer = document.getElementById('catmgrLayer');
const catList = document.getElementById('catmgrList');
const catAddBtn = document.getElementById('catmgrAdd');

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

function openCatLayer() {
    renderCatList();
    catLayer.hidden = false;
    document.body.style.overflow = 'hidden';
}
function closeCatLayer() {
    catLayer.hidden = true;
    document.body.style.overflow = '';
    renderChips();
}

catTrigger.addEventListener('click', openCatLayer);
document.getElementById('catSelectedEdit').addEventListener('click', openCatLayer);
renderChips();
if (catHidden.value) selectCategory(catHidden.value);
catLayer.querySelectorAll('[data-catmgr-close]').forEach(el => el.addEventListener('click', closeCatLayer));

function renderCatList() {
    catList.innerHTML = '';
    catState.forEach(c => catList.appendChild(buildRow(c)));
}

function buildRow(c) {
    const li = document.createElement('li');
    li.className = 'pp-catmgr__row';
    li.dataset.id = c.id;
    if (String(catHidden.value) === String(c.id)) li.classList.add('is-selected');
    li.innerHTML = `
        <div class="pp-catmgr__grab">≡</div>
        <div class="pp-catmgr__main">
            <input class="pp-catmgr__input" type="text" value="${escapeHtml(c.name)}" maxlength="30">
            <div class="pp-catmgr__caption">${c.is_default ? '기본 카테고리 · 이름 수정 가능' : '사용자 카테고리'}</div>
        </div>
        <div class="pp-catmgr__actions">
            <button type="button" class="pp-catmgr__save">저장</button>
            ${c.is_default ? '' : '<button type="button" class="pp-catmgr__del">삭제</button>'}
        </div>
    `;
    li.querySelector('.pp-catmgr__save').addEventListener('click', async () => {
        if (isGuest) return guestAlert();
        const name = li.querySelector('.pp-catmgr__input').value.trim();
        if (!name || name === c.name) return;
        try {
            const r = await fetch(`/api/categories/${c.id}`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ name }) });
            const j = await r.json();
            if (!j.ok) { alert(j.error || '저장 실패'); return; }
            c.name = name;
            const sb = li.querySelector('.pp-catmgr__save');
            sb.textContent = '저장됨'; setTimeout(() => sb.textContent = '저장', 1200);
        } catch (e) { alert('네트워크 오류'); }
    });
    const delBtn = li.querySelector('.pp-catmgr__del');
    if (delBtn) {
        delBtn.addEventListener('click', async () => {
            if (isGuest) return guestAlert();
            if (!confirm(`'${c.name}' 카테고리를 삭제할까요?`)) return;
            try {
                const r = await fetch(`/api/categories/${c.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });
                const j = await r.json();
                if (!j.ok) { alert(j.error || '삭제 실패'); return; }
                catState = catState.filter(x => x.id != c.id);
                if (String(catHidden.value) === String(c.id)) selectCategory('');
                renderCatList();
            } catch (e) { alert('네트워크 오류'); }
        });
    }
    return li;
}

function guestAlert() {
    if (confirm('카테고리 편집은 로그인 후 사용할 수 있어요. 로그인하시겠어요?')) location.href = '{{ route('login') }}';
}

catAddBtn.addEventListener('click', async () => {
    if (isGuest) return guestAlert();
    const name = prompt('새 카테고리 이름을 입력하세요');
    if (!name || !name.trim()) return;
    try {
        const r = await fetch('{{ route('api.categories.store') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ name: name.trim() }) });
        const j = await r.json();
        if (!j.ok) { alert('추가 실패'); return; }
        catState.push({ id: j.item.id, name: j.item.name, is_default: false });
        renderCatList();
        selectCategory(j.item.id);
    } catch (e) { alert('네트워크 오류'); }
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
