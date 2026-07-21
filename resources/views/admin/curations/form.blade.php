@extends('admin.layouts.app')
@section('title', $curation ? '큐레이션 수정' : '큐레이션 생성')

@push('head')
<style>
.cur-form { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 900px) { .cur-form { grid-template-columns: 1fr; } }
.cur-left, .cur-right { display: flex; flex-direction: column; gap: 16px; }
.cur-cover { width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px; margin-top: 8px; }
.cur-places { display: flex; flex-direction: column; gap: 8px; }
.cur-place { background: var(--ad-card); border: 1px solid var(--ad-border); border-radius: 8px; padding: 12px 14px; display: flex; gap: 12px; align-items: flex-start; }
.cur-place__num { width: 24px; height: 24px; border-radius: 50%; background: var(--ad-primary); color: #fff; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px; }
.cur-place__body { flex: 1; min-width: 0; }
.cur-place__name { font-weight: 600; font-size: 14px; }
.cur-place__addr { font-size: 12px; color: var(--ad-text-sub); margin-top: 2px; }
.cur-place__source { font-size: 12px; color: var(--ad-primary); margin-top: 4px; }
.cur-place__meta { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px; }
.cur-place__meta input { font-size: 12px; padding: 3px 8px; border: 1px solid var(--ad-border); border-radius: 4px; }
.cur-place__meta input.short { width: 120px; }
.cur-place__meta input.url { width: 200px; }
.cur-place__actions { display: flex; gap: 4px; flex-shrink: 0; }
.cur-search-wrap { position: relative; }
.cur-search-results { position: absolute; top: 100%; left: 0; right: 0; background: var(--ad-card); border: 1px solid var(--ad-border); border-radius: 8px; max-height: 300px; overflow-y: auto; z-index: 10; display: none; box-shadow: 0 4px 12px rgba(0,0,0,.1); }
.cur-search-results.is-open { display: block; }
.cur-sr { padding: 10px 14px; cursor: pointer; border-bottom: 1px solid var(--ad-border); font-size: 13px; }
.cur-sr:hover { background: #f8fafc; }
.cur-sr:last-child { border-bottom: none; }
.cur-sr__name { font-weight: 600; }
.cur-sr__addr { color: var(--ad-text-sub); font-size: 12px; }
.cur-preview-link { display: inline-flex; align-items: center; gap: 4px; }
textarea.ad-input { min-height: 80px; resize: vertical; }
.cur-place__photos { display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; align-items: center; }
.cur-place__photo { position: relative; width: 56px; height: 56px; border-radius: 6px; overflow: hidden; }
.cur-place__photo img { width: 100%; height: 100%; object-fit: cover; }
.cur-place__photo-del { position: absolute; top: 1px; right: 1px; width: 18px; height: 18px; border-radius: 50%; background: rgba(0,0,0,.6); color: #fff; font-size: 12px; line-height: 18px; text-align: center; border: none; cursor: pointer; padding: 0; }
.cur-place__photo-add { width: 56px; height: 56px; border-radius: 6px; border: 1.5px dashed var(--ad-border); background: #f8fafc; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--ad-text-sub); cursor: pointer; flex-shrink: 0; }
.cur-place__photo-add:hover { border-color: var(--ad-primary); color: var(--ad-primary); }
</style>
@endpush

@section('content')
<form method="POST" action="{{ $curation ? route('admin.curations.update', $curation) : route('admin.curations.store') }}" enctype="multipart/form-data" id="curForm">
    @csrf
    @if($curation) @method('PUT') @endif

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
        <div style="display:flex;gap:8px">
            <button type="submit" class="ad-btn ad-btn--primary">저장</button>
            @if($curation)
                <button type="button" class="ad-btn {{ $curation->status === 'published' ? 'ad-btn--danger' : '' }}" id="togglePublishBtn">
                    {{ $curation->status === 'published' ? '발행 취소' : '발행하기' }}
                </button>
                @if($curation->status === 'published')
                    <a href="{{ route('curation.show', $curation->id) }}" target="_blank" class="ad-btn cur-preview-link">미리보기 ↗</a>
                @endif
            @endif
        </div>
        @if($curation)
            <button type="button" class="ad-btn ad-btn--danger ad-btn--sm" id="deleteCurBtn">삭제</button>
        @endif
    </div>

    @if($errors->any())
        <div class="ad-alert ad-alert--error">{{ $errors->first() }}</div>
    @endif

    <div class="cur-form">
        <div class="cur-left">
            <div class="ad-card">
                <div class="ad-form-group">
                    <label>제목 *</label>
                    <input class="ad-input" name="title" value="{{ old('title', $curation?->title) }}" required>
                </div>
                <div class="ad-form-group">
                    <label>타입</label>
                    <select class="ad-input" name="type" id="curType">
                        <option value="list" {{ old('type', $curation?->type) === 'list' ? 'selected' : '' }}>리스트 (맛지도형)</option>
                        <option value="course" {{ old('type', $curation?->type) === 'course' ? 'selected' : '' }}>코스 (여행코스형)</option>
                    </select>
                </div>
                <div class="ad-form-group">
                    <label>설명</label>
                    <textarea class="ad-input" name="description">{{ old('description', $curation?->description) }}</textarea>
                </div>
                <div class="ad-form-group">
                    <label>지역 라벨</label>
                    <input class="ad-input" name="region_label" value="{{ old('region_label', $curation?->region_label) }}" placeholder="예: 서울, 분당, 맛집 (쉼표로 구분)">
                    <small style="color:var(--ad-text-sub);font-size:11px">쉼표로 구분하면 개별 칩으로 표시됩니다</small>
                </div>
                <div class="ad-form-group">
                    <label>커버 이미지</label>
                    <input type="file" class="ad-input" name="cover_image" accept="image/*">
                    @if($curation?->cover_image)
                        <img src="{{ asset('storage/' . $curation->cover_image) }}" class="cur-cover" alt="">
                    @endif
                </div>
            </div>

            @if($curation)
            <div class="ad-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                    <strong>통계</strong>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;font-size:13px">
                    <div>조회수 <strong>{{ number_format($curation->view_count) }}</strong></div>
                    <div>담기수 <strong>{{ number_format($curation->save_count) }}</strong></div>
                    <div>장소수 <strong>{{ $curation->places->count() }}</strong></div>
                </div>
            </div>
            @endif
        </div>

        <div class="cur-right">
            @if($curation)
            <div class="ad-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                    <strong>장소 관리</strong>
                    <span class="ad-badge ad-badge--gray" id="placeCount">{{ $curation->places->count() }}개</span>
                </div>

                <div style="display:flex;gap:6px;margin-bottom:8px">
                    <button type="button" class="ad-btn ad-btn--sm" id="searchTabDomestic" style="background:var(--ad-primary);color:#fff;border-color:var(--ad-primary)">국내</button>
                    <button type="button" class="ad-btn ad-btn--sm" id="searchTabOverseas">해외</button>
                </div>
                <div class="cur-search-wrap" style="margin-bottom:12px">
                    <input class="ad-input" id="placeSearch" placeholder="장소명 검색 (국내 · 카카오)" autocomplete="off">
                    <div class="cur-search-results" id="searchResults"></div>
                </div>

                <div class="cur-places" id="placeList">
                    @foreach($curation->places as $i => $p)
                    <div class="cur-place" data-place-id="{{ $p->id }}">
                        <div class="cur-place__num">{{ $i + 1 }}</div>
                        <div class="cur-place__body">
                            <div class="cur-place__name">{{ $p->place_name }}</div>
                            <div class="cur-place__addr">{{ $p->address }}</div>
                            @if($p->source_channel)
                            <div class="cur-place__source">{{ $p->source_channel }}@if($p->source_date) ({{ $p->source_date->format('Y.m.d') }})@endif</div>
                            @endif
                            <div class="cur-place__photos" data-pid="{{ $p->id }}">
                                @if($p->photos)
                                    @foreach($p->photos as $pi => $photo)
                                    <div class="cur-place__photo">
                                        <img src="{{ asset('storage/' . \App\Services\ImageProcessor::thumbPathFor($photo)) }}" alt="">
                                        <button type="button" class="cur-place__photo-del" onclick="deletePhoto({{ $p->id }}, {{ $pi }}, this)">✕</button>
                                    </div>
                                    @endforeach
                                @endif
                                @if(!$p->photos || count($p->photos) < 3)
                                <label class="cur-place__photo-add">
                                    +
                                    <input type="file" accept="image/*" multiple hidden onchange="uploadPhotos({{ $p->id }}, this)">
                                </label>
                                @endif
                            </div>
                            <div class="cur-place__meta">
                                <input class="short" data-field="source_channel" value="{{ $p->source_channel }}" placeholder="출처 채널">
                                <input class="url" data-field="source_url" value="{{ $p->source_url }}" placeholder="출처 URL">
                                <input class="short" data-field="source_date" type="date" value="{{ $p->source_date?->format('Y-m-d') }}">
                                @if($curation->type === 'course')
                                <input style="width:60px" data-field="day_number" type="number" min="1" value="{{ $p->day_number }}" placeholder="Day">
                                @endif
                                <input class="short" data-field="editor_note" value="{{ $p->editor_note }}" placeholder="코멘트">
                            </div>
                        </div>
                        <div class="cur-place__actions">
                            <button type="button" class="ad-btn ad-btn--sm" onclick="savePlace({{ $p->id }}, this)">저장</button>
                            <button type="button" class="ad-btn ad-btn--sm ad-btn--danger" onclick="removePlace({{ $p->id }}, this)">삭제</button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <div class="ad-card" style="text-align:center;padding:40px;color:var(--ad-text-sub)">
                큐레이션을 먼저 저장하면 장소를 추가할 수 있습니다.
            </div>
            @endif
        </div>
    </div>
</form>
@endsection

@if($curation)
@push('scripts')
<script>
const csrf = '{{ csrf_token() }}';
const curationId = {{ $curation->id }};
const cType = '{{ $curation->type }}';
let searchTimer;
let searchMode = 'domestic';

const searchInput = document.getElementById('placeSearch');
const searchResults = document.getElementById('searchResults');
const tabDomestic = document.getElementById('searchTabDomestic');
const tabOverseas = document.getElementById('searchTabOverseas');

function setSearchTab(mode) {
    searchMode = mode;
    searchResults.classList.remove('is-open');
    searchInput.value = '';
    if (mode === 'domestic') {
        tabDomestic.style.cssText = 'background:var(--ad-primary);color:#fff;border-color:var(--ad-primary)';
        tabOverseas.style.cssText = '';
        searchInput.placeholder = '장소명 검색 (국내 · 카카오)';
    } else {
        tabOverseas.style.cssText = 'background:var(--ad-primary);color:#fff;border-color:var(--ad-primary)';
        tabDomestic.style.cssText = '';
        searchInput.placeholder = '장소명 검색 (해외 · Google)';
    }
    searchInput.focus();
}
tabDomestic.addEventListener('click', () => setSearchTab('domestic'));
tabOverseas.addEventListener('click', () => setSearchTab('overseas'));

searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const q = searchInput.value.trim();
    if (q.length < 2) { searchResults.classList.remove('is-open'); return; }
    searchTimer = setTimeout(() => doSearch(q), 500);
});

searchInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(searchTimer);
        const q = searchInput.value.trim();
        if (q.length >= 1) doSearch(q);
    }
});

async function doSearch(q) {
    const endpoint = searchMode === 'overseas'
        ? '/api/search/overseas?q=' + encodeURIComponent(q)
        : '/api/search?q=' + encodeURIComponent(q);
    try {
        searchResults.innerHTML = '<div class="cur-sr" style="color:var(--ad-text-sub);cursor:default">검색 중...</div>';
        searchResults.classList.add('is-open');

        const r = await fetch(endpoint);
        if (!r.ok) {
            searchResults.innerHTML = '<div class="cur-sr" style="color:#c00;cursor:default">API 오류 (HTTP ' + r.status + ')</div>';
            return;
        }
        const data = await r.json();
        if (data.error === 'no_key') {
            searchResults.innerHTML = '<div class="cur-sr" style="color:#c00;cursor:default">API 키가 설정되지 않았습니다.</div>';
            return;
        }
        const docs = data.documents || [];
        if (!docs.length) {
            searchResults.innerHTML = '<div class="cur-sr" style="color:var(--ad-text-sub);cursor:default">검색 결과가 없습니다.</div>';
            return;
        }
        const isOverseas = searchMode === 'overseas';
        searchResults.innerHTML = docs.slice(0, 10).map((d, i) => {
            return '<div class="cur-sr" data-idx="' + i + '">'
                + '<div class="cur-sr__name">' + esc(d.place_name) + ' <small>' + esc(d.category_group_name || '') + '</small></div>'
                + '<div class="cur-sr__addr">' + esc(d.road_address_name || d.address_name || '') + '</div>'
                + '</div>';
        }).join('');
        const docsRef = docs.slice(0, 10);
        searchResults.querySelectorAll('.cur-sr').forEach(el => {
            el.addEventListener('click', () => {
                const d = docsRef[parseInt(el.dataset.idx)];
                addPlaceFromSearch(d, isOverseas);
                searchResults.classList.remove('is-open');
                searchInput.value = '';
            });
        });
    } catch(e) {
        searchResults.innerHTML = '<div class="cur-sr" style="color:#c00;cursor:default">네트워크 오류: ' + esc(e.message) + '</div>';
    }
}

function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

async function addPlaceFromSearch(d, isOverseas) {
    const body = {
        place_name: d.place_name,
        address: d.road_address_name || d.address_name || '',
        latitude: d.y,
        longitude: d.x,
        category_label: d.category_group_name || '',
        external_place_id: d.id || '',
        phone: d.phone || '',
        is_overseas: isOverseas,
    };
    if (isOverseas && d.id) body.google_place_id = d.id;
    if (d.opening_hours) body.opening_hours = d.opening_hours;
    try {
        const r = await fetch('/admin/curations/' + curationId + '/places', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(body),
        });
        const data = await r.json();
        if (data.success) {
            appendPlaceCard(data.place);
        } else {
            alert(data.message || '추가 실패');
        }
    } catch(e) { alert('추가 실패: ' + e.message); }
}

function appendPlaceCard(p) {
    const list = document.getElementById('placeList');
    const idx = list.querySelectorAll('.cur-place').length;
    const dayInput = cType === 'course'
        ? '<input style="width:60px" data-field="day_number" type="number" min="1" value="" placeholder="Day">'
        : '';
    const html = '<div class="cur-place" data-place-id="' + p.id + '">'
        + '<div class="cur-place__num">' + (idx + 1) + '</div>'
        + '<div class="cur-place__body">'
        + '<div class="cur-place__name">' + esc(p.place_name) + '</div>'
        + '<div class="cur-place__addr">' + esc(p.address || '') + '</div>'
        + '<div class="cur-place__meta">'
        + '<input class="short" data-field="source_channel" value="" placeholder="출처 채널">'
        + '<input class="url" data-field="source_url" value="" placeholder="출처 URL">'
        + '<input class="short" data-field="source_date" type="date" value="">'
        + dayInput
        + '<input class="short" data-field="editor_note" value="" placeholder="코멘트">'
        + '</div></div>'
        + '<div class="cur-place__actions">'
        + '<button type="button" class="ad-btn ad-btn--sm" onclick="savePlace(' + p.id + ', this)">저장</button>'
        + '<button type="button" class="ad-btn ad-btn--sm ad-btn--danger" onclick="removePlace(' + p.id + ', this)">삭제</button>'
        + '</div></div>';
    list.insertAdjacentHTML('beforeend', html);
    renumber();
}

async function savePlace(placeId, btn) {
    const card = btn.closest('.cur-place');
    const fields = {};
    card.querySelectorAll('[data-field]').forEach(inp => {
        fields[inp.dataset.field] = inp.value;
    });
    try {
        const r = await fetch('/admin/curations/places/' + placeId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(fields),
        });
        if ((await r.json()).success) {
            btn.textContent = '완료';
            setTimeout(() => { btn.textContent = '저장'; }, 1000);
        }
    } catch(e) { alert('저장 실패'); }
}

async function removePlace(placeId, btn) {
    if (!confirm('이 장소를 삭제할까요?')) return;
    try {
        const r = await fetch('/admin/curations/places/' + placeId, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        });
        if ((await r.json()).success) {
            btn.closest('.cur-place').remove();
            renumber();
        }
    } catch(e) { alert('삭제 실패'); }
}

function renumber() {
    document.querySelectorAll('.cur-place__num').forEach((el, i) => el.textContent = i + 1);
    document.getElementById('placeCount').textContent = document.querySelectorAll('.cur-place').length + '개';
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.cur-search-wrap')) searchResults.classList.remove('is-open');
});

// 사진 업로드
async function uploadPhotos(placeId, input) {
    const files = input.files;
    if (!files.length) return;
    const fd = new FormData();
    for (let i = 0; i < Math.min(files.length, 3); i++) fd.append('photos[]', files[i]);
    try {
        const r = await fetch('/admin/curations/places/' + placeId + '/photos', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: fd,
        });
        const data = await r.json();
        if (data.success) {
            const wrap = document.querySelector('.cur-place__photos[data-pid="'+placeId+'"]');
            wrap.innerHTML = '';
            data.photos.forEach((p, i) => {
                wrap.innerHTML += '<div class="cur-place__photo"><img src="'+p.thumb+'" alt=""><button type="button" class="cur-place__photo-del" onclick="deletePhoto('+placeId+','+i+',this)">✕</button></div>';
            });
            if (data.photos.length < 3) {
                wrap.innerHTML += '<label class="cur-place__photo-add">+<input type="file" accept="image/*" multiple hidden onchange="uploadPhotos('+placeId+',this)"></label>';
            }
        } else {
            alert(data.message || '업로드 실패');
        }
    } catch(e) { alert('업로드 실패: ' + e.message); }
    input.value = '';
}

async function deletePhoto(placeId, idx, btn) {
    if (!confirm('이 사진을 삭제할까요?')) return;
    try {
        const r = await fetch('/admin/curations/places/' + placeId + '/photos', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ index: idx }),
        });
        if ((await r.json()).success) location.reload();
    } catch(e) { alert('삭제 실패'); }
}

// 발행 토글
const toggleBtn = document.getElementById('togglePublishBtn');
if (toggleBtn) {
    toggleBtn.addEventListener('click', async () => {
        toggleBtn.disabled = true;
        try {
            const r = await fetch('/admin/curations/' + curationId + '/toggle-publish', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            location.reload();
        } catch(e) { alert('처리 실패'); toggleBtn.disabled = false; }
    });
}

// 삭제
const delBtn = document.getElementById('deleteCurBtn');
if (delBtn) {
    delBtn.addEventListener('click', async () => {
        if (!confirm('정말 삭제할까요?')) return;
        try {
            const r = await fetch('/admin/curations/' + curationId, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            location.href = '/admin/curations';
        } catch(e) { alert('삭제 실패'); }
    });
}
</script>
@endpush
@endif
