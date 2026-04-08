@extends('layouts.app')
@section('title', '장소 추가')

@section('header')
<header class="pp-header">
    <button class="pp-header__icon" onclick="history.back()" aria-label="뒤로">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </button>
    <div class="pp-header__title">장소 추가</div>
</header>
@endsection

@section('content')
<div class="pp-form">
    <div class="pp-field">
        <label class="pp-label">장소 검색 <span style="color:var(--pp-text-sub);font-weight:400">(카카오)</span></label>
        <input id="searchBox" class="pp-input" placeholder="예: 스타벅스 강남역점" autocomplete="off">
        <div id="searchResults" style="margin-top:8px"></div>
    </div>

    <form method="POST" action="{{ route('places.store') }}" id="placeForm">
        @csrf
        <div class="pp-field">
            <label class="pp-label">장소명 *</label>
            <input class="pp-input" name="name" id="f_name" required>
        </div>

        <div class="pp-field">
            <label class="pp-label">카테고리</label>
            <select class="pp-select" name="category_id" id="f_category">
                <option value="">선택 안 함</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->icon }} {{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="pp-field">
            <label class="pp-label">전화번호</label>
            <input class="pp-input" name="phone" id="f_phone" inputmode="tel">
        </div>

        <div class="pp-field">
            <label class="pp-label">주소</label>
            <input class="pp-input" name="road_address" id="f_road">
            <input type="hidden" name="address" id="f_addr">
            <input type="hidden" name="lat" id="f_lat">
            <input type="hidden" name="lng" id="f_lng">
            <input type="hidden" name="kakao_place_id" id="f_kpid">
        </div>

        <div class="pp-field">
            <label class="pp-label">메모</label>
            <textarea class="pp-textarea" name="memo" maxlength="500" placeholder="한 줄 메모 (예: 솥밥 꼭 먹기)"></textarea>
        </div>

        <div class="pp-field">
            <label class="pp-label">방문 상태</label>
            <div class="pp-seg">
                <button type="button" class="is-active" data-status="planned">방문예정</button>
                <button type="button" data-status="visited">방문완료</button>
            </div>
            <input type="hidden" name="status" id="f_status" value="planned">
        </div>

        <div class="pp-field" id="visitedDateField" style="display:none">
            <label class="pp-label">방문 날짜</label>
            <input type="date" class="pp-input" name="visited_at">
        </div>

        <button class="pp-btn" type="submit">저장하기</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
const box = document.getElementById('searchBox');
const results = document.getElementById('searchResults');
let t;
box.addEventListener('input', () => {
    clearTimeout(t);
    const q = box.value.trim();
    if (q.length < 2) { results.innerHTML=''; return; }
    t = setTimeout(async () => {
        const r = await fetch('/api/search?q=' + encodeURIComponent(q));
        const data = await r.json();
        const docs = data.documents || [];
        results.innerHTML = docs.map((d, i) => `
            <div class="pp-card" style="padding:10px 12px;margin-bottom:6px;cursor:pointer" data-i="${i}">
                <div style="font-weight:700;font-size:14px">${d.place_name}</div>
                <div style="font-size:12px;color:var(--pp-text-sub);margin-top:2px">${d.road_address_name || d.address_name || ''}</div>
                ${d.phone ? `<div style="font-size:12px;color:var(--pp-text-sub)">${d.phone}</div>` : ''}
            </div>
        `).join('');
        results.querySelectorAll('[data-i]').forEach(el => {
            el.addEventListener('click', () => {
                const d = docs[+el.dataset.i];
                document.getElementById('f_name').value = d.place_name || '';
                document.getElementById('f_phone').value = d.phone || '';
                document.getElementById('f_road').value = d.road_address_name || d.address_name || '';
                document.getElementById('f_addr').value = d.address_name || '';
                document.getElementById('f_lat').value = d.y || '';
                document.getElementById('f_lng').value = d.x || '';
                document.getElementById('f_kpid').value = d.id || '';
                results.innerHTML = '';
                box.value = d.place_name;
                // 카테고리 자동 추천
                const cat = d.category_group_name || '';
                const catSel = document.getElementById('f_category');
                const map = {'음식점':'맛집','카페':'카페','숙박':'숙소','병원':'병원·약국','약국':'병원·약국'};
                const target = map[cat];
                if (target) {
                    for (const opt of catSel.options) {
                        if (opt.text.includes(target)) { catSel.value = opt.value; break; }
                    }
                }
            });
        });
    }, 250);
});

document.querySelectorAll('.pp-seg button').forEach(b => {
    b.addEventListener('click', () => {
        document.querySelectorAll('.pp-seg button').forEach(x => x.classList.remove('is-active'));
        b.classList.add('is-active');
        const s = b.dataset.status;
        document.getElementById('f_status').value = s;
        document.getElementById('visitedDateField').style.display = s === 'visited' ? 'block' : 'none';
    });
});
</script>
@endpush
