@extends('layouts.app')
@section('title', '핀픽')
@section('app_class', 'pp-app--home')

@section('header')
<header class="yg-header">
    <div class="yg-header__brand">
        <span class="yg-header__logo">핀픽</span>
        <span class="yg-header__sub">나만의 지도</span>
    </div>
    <div class="yg-header__actions">
        <a href="#" class="yg-header__icon" aria-label="알림">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
            <span class="yg-dot"></span>
        </a>
        @auth
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button type="submit" class="yg-header__icon" aria-label="로그아웃">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="yg-header__icon" aria-label="로그인">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
            </a>
        @endauth
    </div>
</header>
@endsection

@section('content')
<div class="yg-content">

    {{-- 히어로 카드 + 카테고리 가로 슬라이더 --}}
    <div class="yg-hero">
        <div class="yg-hero__head">
            <div>
                @auth
                    <div class="yg-hero__title">{{ auth()->user()->name }}님의 지도</div>
                    <div class="yg-hero__desc">카테고리별로 저장한 장소를 한눈에 모아보세요</div>
                @else
                    <div class="yg-hero__title">나만의 지도를 시작해보세요</div>
                    <div class="yg-hero__desc">로그인 없이 5개까지 저장 가능.<br>저장한 장소는 로그인하면 그대로 이어집니다.</div>
                @endauth
            </div>
        </div>
        <div class="yg-catslide">
            @foreach($categories as $c)
                @php $latest = ($categoryLatest ?? collect())->get($c->id); @endphp
                @if($latest)
                    <a href="{{ route('map', ['category' => $c->id]) }}" class="yg-catcard2 yg-catcard2--filled">
                        <div class="yg-catcard2__thumb"@if($latest->images->first()) style="background-image:url('{{ asset('storage/' . $latest->images->first()->path) }}');background-size:cover;background-position:center"@endif>
                            @unless($latest->images->first())<span style="font-size:18px">{{ $c->icon ?? '📌' }}</span>@endunless
                        </div>
                        <div class="yg-catcard2__body">
                            <div class="yg-catcard2__cat">{{ $c->name }}</div>
                            <div class="yg-catcard2__name">{{ Str::limit($latest->name, 12) }}</div>
                            <div class="yg-catcard2__meta">{{ Str::limit($latest->road_address ?: $latest->address ?: '주소 없음', 16) }}</div>
                        </div>
                    </a>
                @else
                    <a href="{{ route('places.create', ['category' => $c->id]) }}" class="yg-catcard2 yg-catcard2--empty">
                        <div class="yg-catcard2__plus">＋</div>
                        <div class="yg-catcard2__cat">{{ $c->name }}</div>
                        <div class="yg-catcard2__meta">새 장소 저장하기</div>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    {{-- 세그먼트 탭: 사람들 / 내 장소 --}}
    <div class="yg-segtab">
        <button type="button" class="yg-segtab__btn is-active" data-pane="people">사람들</button>
        <button type="button" class="yg-segtab__btn" data-pane="mine">내 장소</button>
    </div>

    {{-- 사람들 탭 (큐레이션) --}}
    <div class="yg-pane is-active" data-pane="people">
        <div class="yg-section">
            <div class="yg-section__head">
                <div class="yg-section__title">이번주 <em>핫한 저장</em></div>
                <a href="#" class="yg-section__more">더보기 ›</a>
            </div>
            <div class="yg-hscroll">
                @foreach($curation['weekly'] as $p)
                    <div class="yg-prod">
                        <div class="yg-prod__thumb" style="background-image:url('{{ $p['thumb'] }}')">
                            <span class="yg-prod__rate">{{ number_format($p['saves']) }}</span>
                        </div>
                        <div class="yg-prod__name">{{ $p['name'] }}</div>
                        <div class="yg-prod__meta">{{ $p['category'] }} · {{ $p['area'] }}</div>
                        <div class="yg-prod__price">{{ number_format($p['saves']) }} 저장</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="yg-section">
            <div class="yg-section__head">
                <div class="yg-section__title">요즘 많이 찾는 <em>맛집</em></div>
                <a href="#" class="yg-section__more">더보기 ›</a>
            </div>
            <div class="yg-rank">
                @foreach(array_slice($curation['trending_food'], 0, 5) as $i => $p)
                    <div class="yg-rank__item">
                        <div class="yg-rank__num">{{ $i + 1 }}</div>
                        <div class="yg-rank__main">
                            <strong>{{ $p['name'] }}</strong>
                            <p>{{ $p['category'] }} · {{ $p['area'] }}</p>
                        </div>
                        <div class="yg-rank__chip">저장 많음</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="yg-section">
            <div class="yg-section__head">
                <div class="yg-section__title">여행자들의 <em>서울 핀픽</em></div>
                <a href="#" class="yg-section__more">더보기 ›</a>
            </div>
            <div class="yg-hscroll">
                @foreach($curation['seoul_trip'] as $p)
                    <div class="yg-prod">
                        <div class="yg-prod__thumb" style="background-image:url('{{ $p['thumb'] }}')"></div>
                        <div class="yg-prod__name">{{ $p['name'] }}</div>
                        <div class="yg-prod__meta">{{ $p['category'] }} · {{ $p['area'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 내 장소 탭 — 카테고리별 가로 슬라이더 --}}
    <div class="yg-pane" data-pane="mine">
        {{-- 카테고리 관리 --}}
        @auth
        <div class="yg-catorder" id="catOrderBar">
            <button type="button" class="yg-catorder__btn" id="catOrderEditBtn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                카테고리 추가/수정
            </button>
        </div>
        <div class="yg-catorder-panel" id="catOrderPanel" hidden>
            <div class="yg-catorder-panel__head">
                <span class="yg-catorder-panel__title">카테고리 관리</span>
                <button type="button" class="yg-catorder-panel__done" id="catOrderDone">완료</button>
            </div>
            <ul class="yg-catorder-panel__list" id="catOrderList"></ul>
            <button type="button" class="yg-catorder-panel__add" id="catOrderAdd">＋ 새 카테고리 추가</button>
        </div>
        @endauth

        @php $authPlacesByCat = $myPlaces->groupBy('category_id'); @endphp
        @foreach($categories as $c)
            <section class="yg-mycat" data-cat-id="{{ $c->id }}" data-sort="{{ $c->sort_order }}">
                <div class="yg-mycat__head">
                    <h3 class="yg-mycat__title"><span class="yg-mycat__catname">{{ $c->name }}</span></h3>
                    <button type="button" class="yg-mycat__edit" aria-label="카테고리 이름 편집" data-cat-id="{{ $c->id }}" data-cat-name="{{ $c->name }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                        <span>편집</span>
                    </button>
                </div>
                <div class="yg-mycat__list">
                    @auth
                        @foreach(($authPlacesByCat[$c->id] ?? collect()) as $p)
                            <a href="{{ route('places.show', $p) }}" class="yg-myspot">
                                <div class="yg-myspot__thumb"@if($p->images->first()) style="background-image:url('{{ asset('storage/' . $p->images->first()->path) }}');background-size:cover;background-position:center"@endif>
                                    <span class="yg-myspot__badge">{{ $p->status === 'visited' ? '방문완료' : '방문예정' }}</span>
                                </div>
                                <div class="yg-myspot__body">
                                    <div class="yg-myspot__name">{{ $p->name }}</div>
                                    <div class="yg-myspot__meta">{{ $c->name }}@if($p->road_address || $p->address) · {{ Str::limit($p->road_address ?: $p->address, 12) }}@endif</div>
                                    @if($p->memo)<div class="yg-myspot__memo">{{ $p->memo }}</div>@endif
                                </div>
                            </a>
                        @endforeach
                    @endauth
                    {{-- 게스트 카드는 JS가 여기 앞에 삽입 --}}
                    <a href="{{ route('places.create', ['category' => $c->id]) }}" class="yg-myspot yg-myspot--add">
                        <div class="yg-myspot__plus">＋</div>
                        <div class="yg-myspot__addtxt">장소 추가하기</div>
                    </a>
                </div>
            </section>
        @endforeach
    </div>

    {{-- 프로모 배너 --}}
    <div class="yg-promo">
        <div>
            <div class="yg-promo__title">나만의 지도를 시작해보세요</div>
            <div class="yg-promo__big">로그인 없이도<br>5개까지 저장 OK</div>
        </div>
    </div>

    {{-- 푸터 사업자정보 --}}
    <footer class="pp-footer">
        <div class="pp-footer__logo">핀픽</div>
        <address class="pp-footer__biz-content">
            <span>(주) 맵큐브</span>
            <p>주소 : 경기도 성남시 분당구 황새울로 354 8층</p>
            <p>대표이사 : 이학영 | 사업자등록번호: 230-81-13255 | 통신판매번호 : 2023-성남분당A-0360 | 전화번호 : 1670-1376 | 전자우편주소 : help.mamap@gmail.com</p>
        </address>
        <div class="pp-footer__policy">
            <a href="#">이용약관</a> ㅣ <a href="#">개인정보 수집이용</a> ㅣ <a href="#">위치정보 이용약관</a>
        </div>
        <div class="pp-footer__notice">(주)맵큐브는 통신판매중개자로서 통신판매의 당사자가 아니며, 상품예약, 이용 및 환불 등과 관련한 의무와 책임은 각 판매자에게 있습니다.</div>
        <div class="pp-footer__copy">&copy; {{ date('Y') }} 핀픽. All rights reserved.</div>
    </footer>

</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.yg-segtab__btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const target = btn.dataset.pane;
        document.querySelectorAll('.yg-segtab__btn').forEach(b => b.classList.toggle('is-active', b.dataset.pane === target));
        document.querySelectorAll('.yg-pane').forEach(p => p.classList.toggle('is-active', p.dataset.pane === target));
    });
});

// ── 카테고리 관리 (순서 + 이름편집 + 추가 + 삭제) ──
(function() {
    const csrf = '{{ csrf_token() }}';
    const isGuest = {{ auth()->check() ? 'false' : 'true' }};

    function escHtml(s) { return String(s||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    // 각 카테고리 섹션의 "편집" 버튼 → 패널 열기로 연결
    document.querySelectorAll('.yg-mycat__edit').forEach(btn => {
        btn.addEventListener('click', () => {
            if (isGuest) {
                if (confirm('카테고리 편집은 로그인 후 사용할 수 있어요. 로그인하시겠어요?')) location.href = '{{ route('login') }}';
                return;
            }
            openCatPanel();
        });
    });

    if (isGuest) return;
    const orderPanel = document.getElementById('catOrderPanel');
    const orderList = document.getElementById('catOrderList');
    if (!orderPanel) return;

    // 상단 버튼으로 열기
    document.getElementById('catOrderEditBtn').addEventListener('click', openCatPanel);

    function openCatPanel() {
        orderPanel.hidden = false;
        renderCatOrderList();
        orderPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // 완료 버튼
    document.getElementById('catOrderDone').addEventListener('click', async () => {
        // 1) 이름 변경 사항 수집 + 저장
        const items = Array.from(orderList.querySelectorAll('.yg-catorder-item'));
        for (const li of items) {
            const input = li.querySelector('.yg-catorder-item__input');
            const origName = li.dataset.origName;
            const newName = input.value.trim();
            if (newName && newName !== origName) {
                try {
                    const r = await fetch(`/api/categories/${li.dataset.id}`, {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ name: newName })
                    });
                    const j = await r.json();
                    if (!j.ok) { alert(j.error || '이름 변경 실패: ' + origName); }
                } catch (e) { alert('네트워크 오류'); }
            }
        }

        // 2) 순서 저장
        const ids = items.map(li => +li.dataset.id);
        try {
            const r = await fetch('/api/categories/reorder', {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ order: ids })
            });
            const j = await r.json();
            if (!j.ok) { alert('순서 저장 실패'); return; }
        } catch (e) { alert('네트워크 오류'); return; }

        // 3) DOM 반영: 카테고리명 + 순서 재배치
        const pane = document.querySelector('[data-pane="mine"]');
        const sections = Array.from(pane.querySelectorAll('.yg-mycat'));
        items.forEach(li => {
            const id = +li.dataset.id;
            const newName = li.querySelector('.yg-catorder-item__input').value.trim();
            const sec = sections.find(s => +s.dataset.catId === id);
            if (!sec) return;
            if (newName) {
                const titleSpan = sec.querySelector('.yg-mycat__catname');
                if (titleSpan) titleSpan.textContent = newName;
                const editBtn = sec.querySelector('.yg-mycat__edit');
                if (editBtn) editBtn.dataset.catName = newName;
                sec.querySelectorAll('.yg-myspot__meta').forEach(m => {
                    const txt = m.textContent;
                    const dotIdx = txt.indexOf(' · ');
                    m.textContent = dotIdx >= 0 ? `${newName}${txt.slice(dotIdx)}` : newName;
                });
            }
            pane.appendChild(sec);
        });
        orderPanel.hidden = true;
    });

    // 카테고리 추가
    document.getElementById('catOrderAdd').addEventListener('click', async () => {
        const name = prompt('새 카테고리 이름을 입력하세요');
        if (!name || !name.trim()) return;
        try {
            const r = await fetch('/api/categories', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ name: name.trim() })
            });
            const j = await r.json();
            if (!j.ok) { alert('추가 실패'); return; }
            // 페이지 새로고침으로 새 섹션 반영
            location.reload();
        } catch (e) { alert('네트워크 오류'); }
    });

    function renderCatOrderList() {
        const sections = Array.from(document.querySelectorAll('[data-pane="mine"] .yg-mycat'));
        orderList.innerHTML = '';
        sections.forEach((sec, i) => {
            const id = sec.dataset.catId;
            const name = sec.querySelector('.yg-mycat__catname').textContent;
            const li = document.createElement('li');
            li.className = 'yg-catorder-item';
            li.dataset.id = id;
            li.dataset.origName = name;
            li.innerHTML = `
                <span class="yg-catorder-item__grip">☰</span>
                <input class="yg-catorder-item__input" type="text" value="${escHtml(name)}" maxlength="30">
                <div class="yg-catorder-item__btns">
                    <button type="button" class="yg-catorder-item__up" ${i === 0 ? 'disabled' : ''} aria-label="위로">↑</button>
                    <button type="button" class="yg-catorder-item__down" ${i === sections.length - 1 ? 'disabled' : ''} aria-label="아래로">↓</button>
                    <button type="button" class="yg-catorder-item__del" aria-label="삭제">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </div>
            `;
            li.querySelector('.yg-catorder-item__up').addEventListener('click', () => moveCatItem(li, -1));
            li.querySelector('.yg-catorder-item__down').addEventListener('click', () => moveCatItem(li, 1));
            li.querySelector('.yg-catorder-item__del').addEventListener('click', () => deleteCatItem(li));
            orderList.appendChild(li);
        });
    }

    function moveCatItem(li, dir) {
        const items = Array.from(orderList.children);
        const idx = items.indexOf(li);
        const swapIdx = idx + dir;
        if (swapIdx < 0 || swapIdx >= items.length) return;
        if (dir === -1) orderList.insertBefore(li, items[swapIdx]);
        else orderList.insertBefore(items[swapIdx], li);
        refreshUpDown();
    }

    function refreshUpDown() {
        Array.from(orderList.children).forEach((item, i, arr) => {
            item.querySelector('.yg-catorder-item__up').disabled = i === 0;
            item.querySelector('.yg-catorder-item__down').disabled = i === arr.length - 1;
        });
    }

    async function deleteCatItem(li) {
        const id = li.dataset.id;
        const name = li.querySelector('.yg-catorder-item__input').value;
        if (!confirm(`'${name}' 카테고리를 삭제할까요?`)) return;
        try {
            const r = await fetch(`/api/categories/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            });
            const j = await r.json();
            if (!j.ok) { alert(j.error || '삭제 실패'); return; }
            li.remove();
            refreshUpDown();
            // DOM에서도 해당 카테고리 섹션 제거
            const sec = document.querySelector(`.yg-mycat[data-cat-id="${id}"]`);
            if (sec) sec.remove();
        } catch (e) { alert('네트워크 오류'); }
    }
})();

// 게스트 localStorage 장소 hydrate
@guest
(function() {
    const list = JSON.parse(localStorage.getItem('pinpick_guest_places') || '[]');
    if (!list.length) return;
    document.querySelectorAll('.yg-mycat').forEach(sec => {
        const cid = +sec.dataset.catId;
        const items = list.filter(p => p.category_id === cid);
        if (!items.length) return;
        const listEl = sec.querySelector('.yg-mycat__list');
        const addBtn = listEl.querySelector('.yg-myspot--add');
        items.forEach(p => {
            const card = document.createElement('div');
            card.className = 'yg-myspot';
            const badge = p.status === 'visited' ? '방문완료' : '방문예정';
            const addr = (p.road_address || p.address || '').slice(0, 12);
            card.innerHTML = `
                <div class="yg-myspot__thumb"><span class="yg-myspot__badge">${badge}</span></div>
                <div class="yg-myspot__body">
                    <div class="yg-myspot__name">${escapeHtml(p.name)}</div>
                    <div class="yg-myspot__meta">${escapeHtml(p.category_name || '')}${addr ? ' · ' + escapeHtml(addr) : ''}</div>
                    ${p.memo ? `<div class="yg-myspot__memo">${escapeHtml(p.memo)}</div>` : ''}
                </div>
            `;
            listEl.insertBefore(card, addBtn);
        });
    });
    function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
})();
@endguest
</script>
@endpush
