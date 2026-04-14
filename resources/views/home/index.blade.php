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

    {{-- 세그먼트 탭: 사람들 / 내 장소 --}}
    <div class="yg-segtab">
        <button type="button" class="yg-segtab__btn is-active" data-pane="people">사람들</button>
        <button type="button" class="yg-segtab__btn" data-pane="mine">내 장소</button>
    </div>

    {{-- 상단 히어로 카드 (요약 + 카테고리 탭 + 최근 장소) --}}
    <div class="pp-hero2">
        <div class="pp-hero2__head">
            <div class="pp-hero2__titles">
                @auth
                    <h2 class="pp-hero2__title" id="ppHeroTitle" data-people="{{ auth()->user()->name }}님의 지도" data-mine="내 장소">{{ auth()->user()->name }}님의 지도</h2>
                @else
                    <h2 class="pp-hero2__title" id="ppHeroTitle">나만의 지도를 시작해보세요</h2>
                @endauth
                @auth
                <div class="pp-hero2__stats">
                    <span class="pp-hero2__stat">저장 {{ $savedCount }}</span>
                    <span class="pp-hero2__stat-dot">·</span>
                    <span class="pp-hero2__stat">카테고리 {{ $categories->count() }}</span>
                </div>
                @endauth
            </div>
            @auth
            <div class="pp-hero2__head-actions">
                <button type="button" class="yg-catorder__btn pp-hero2__edit-btn" id="catOrderEditBtnHero">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                    카테고리 추가/수정
                </button>
            </div>
            @endauth
        </div>

        <div class="pp-hero2__tabs" id="ppHeroTabs">
            <button type="button" class="pp-hero2__tab is-active" data-cat="all">전체</button>
            @foreach($categories as $c)
                <button type="button" class="pp-hero2__tab" data-cat="{{ $c->id }}">{{ $c->name }}</button>
            @endforeach
        </div>

        <div class="pp-hero2__hint">
            <span class="pp-hero2__hint-text">카테고리를 선택하면 해당 장소만 보여줘요</span>
        </div>

        <div class="pp-hero2__section-head">
            <h3 class="pp-hero2__section-title">최근 등록된 장소</h3>
            <a href="{{ route('map') }}" class="pp-hero2__more">전체보기 ›</a>
        </div>

        <div class="pp-hero2__slide" id="ppHeroSlide">
            @auth
                @php $emptyCreateUrl = route('places.create'); @endphp
                @forelse($recentPlaces as $p)
                    <a href="{{ route('places.show', $p) }}" class="pp-rspot" data-cat="{{ $p->category_id }}">
                        @php $thumbUrl = $p->images->first() ? asset('storage/' . $p->images->first()->path) : ($p->thumbnail ? asset('storage/' . $p->thumbnail) : null); @endphp
                        <div class="pp-rspot__thumb"@if($thumbUrl) style="background-image:url('{{ $thumbUrl }}');background-size:cover;background-position:center"@endif>
                            @unless($thumbUrl)<span class="pp-rspot__ph">{{ $p->category?->icon ?? '📌' }}</span>@endunless
                            <span class="pp-rspot__badge">{{ $p->status === 'visited' ? '방문완료' : '방문예정' }}</span>
                        </div>
                        <div class="pp-rspot__body">
                            <div class="pp-rspot__name">{{ $p->name }}</div>
                            <div class="pp-rspot__meta pp-rspot__meta--all">
                                {{ $p->category?->name ?? '기타' }}@if($p->road_address || $p->address) · {{ Str::limit(preg_replace('/^(\S+\s+\S+).*/', '$1', $p->road_address ?: $p->address), 10) }}@endif
                            </div>
                            <div class="pp-rspot__sub">
                                @if($p->road_address || $p->address)
                                    {{ Str::limit($p->road_address ?: $p->address, 18) }}
                                @else
                                    최근 저장 {{ $p->created_at->diffForHumans(null, true) }} 전
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <a href="{{ $emptyCreateUrl }}" class="pp-rspot pp-rspot--empty">
                        <div class="pp-rspot__plus">＋</div>
                        <div class="pp-rspot__empty-title">첫 장소를 추가해보세요</div>
                        <p class="pp-rspot__empty-desc">맛집, 카페, 여행지처럼 다시 찾고<br>싶은 장소를 저장하면 여기서<br>바로 꺼내볼 수 있어요.</p>
                        <span class="pp-rspot__empty-btn">장소 추가하기</span>
                    </a>
                @endforelse
            @else
                <a href="{{ route('places.create') }}" class="pp-rspot pp-rspot--empty">
                    <div class="pp-rspot__plus">＋</div>
                    <div class="pp-rspot__empty-title">첫 장소를 추가해보세요</div>
                    <p class="pp-rspot__empty-desc">맛집, 카페, 여행지처럼 다시 찾고<br>싶은 장소를 저장하면 여기서<br>바로 꺼내볼 수 있어요.</p>
                    <span class="pp-rspot__empty-btn">장소 추가하기</span>
                </a>
            @endauth
        </div>
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

    {{-- 내 장소 탭 — 통합 그리드 (카테고리 탭으로 필터) --}}
    <div class="yg-pane" data-pane="mine">
        {{-- 카테고리 관리 버튼은 히어로 헤드로 이동됨. 패널은 그대로 사용 --}}
        @auth
        <div class="pp-mine-sechead">
            <h3 class="pp-mine-sechead__title">전체 장소</h3>
            <div class="pp-sortfilter" id="ppSortFilter">
                <button type="button" class="pp-sortfilter__btn" id="ppSortFilterBtn" aria-expanded="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M3 6h18"/><path d="M7 12h10"/><path d="M11 18h2"/></svg>
                    <span id="ppSortFilterLabel">정렬/필터</span>
                </button>
                <div class="pp-sortfilter__menu" id="ppSortFilterMenu" hidden>
                    <div class="pp-sortfilter__group">
                        <div class="pp-sortfilter__gtitle">정렬</div>
                        <button type="button" class="pp-sortfilter__opt is-active" data-sort="created_desc">최근 등록순</button>
                        <button type="button" class="pp-sortfilter__opt" data-sort="created_asc">오래된 순</button>
                        <button type="button" class="pp-sortfilter__opt" data-sort="name">이름순</button>
                    </div>
                    <div class="pp-sortfilter__group">
                        <div class="pp-sortfilter__gtitle">방문 상태</div>
                        <button type="button" class="pp-sortfilter__opt is-active" data-status="all">전체</button>
                        <button type="button" class="pp-sortfilter__opt" data-status="planned">방문예정</button>
                        <button type="button" class="pp-sortfilter__opt" data-status="visited">방문완료</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="yg-catorder-panel" id="catOrderPanel" hidden>
            <div class="yg-catorder-panel__head">
                <span class="yg-catorder-panel__title">카테고리 관리</span>
                <div class="yg-catorder-panel__actions">
                    <button type="button" class="yg-catorder-panel__cancel" id="catOrderCancel">취소</button>
                    <button type="button" class="yg-catorder-panel__done" id="catOrderDone">저장</button>
                </div>
            </div>
            <ul class="yg-catorder-panel__list" id="catOrderList"></ul>
            <button type="button" class="yg-catorder-panel__add" id="catOrderAdd">＋ 새 카테고리 추가</button>
        </div>
        @endauth

        {{-- 카테고리 관리/게스트 JS 의존 히든 메타 소스 --}}
        <div class="yg-mycat-meta" hidden aria-hidden="true">
            @foreach($categories as $c)
                <section class="yg-mycat" data-cat-id="{{ $c->id }}" data-sort="{{ $c->sort_order }}">
                    <span class="yg-mycat__catname">{{ $c->name }}</span>
                </section>
            @endforeach
        </div>

        @auth
        <div class="pp-mine-grid" id="ppMineGrid">
            @forelse($myPlaces as $p)
                <a href="{{ route('places.show', $p) }}" class="pp-mine-grid__item" data-cat="{{ $p->category_id }}" data-name="{{ $p->name }}" data-created="{{ $p->created_at->timestamp }}" data-status="{{ $p->status }}">
                    @php $thumbUrl = $p->images->first() ? asset('storage/' . $p->images->first()->path) : ($p->thumbnail ? asset('storage/' . $p->thumbnail) : null); @endphp
                    <div class="pp-mine-grid__thumb"@if($thumbUrl) style="background-image:url('{{ $thumbUrl }}');background-size:cover;background-position:center"@endif>
                        @unless($thumbUrl)<span class="pp-mine-grid__ph">{{ $p->category?->icon ?? '📌' }}</span>@endunless
                        <span class="pp-mine-grid__badge">{{ $p->status === 'visited' ? '방문완료' : '방문예정' }}</span>
                    </div>
                    <div class="pp-mine-grid__body">
                        <div class="pp-mine-grid__name">{{ $p->name }}</div>
                        <div class="pp-mine-grid__meta">{{ $p->category?->name ?? '기타' }}@if($p->road_address || $p->address) · {{ Str::limit($p->road_address ?: $p->address, 12) }}@endif</div>
                    </div>
                </a>
            @empty
                <a href="{{ route('places.create') }}" class="pp-mine-grid__empty">
                    <div class="pp-rspot__plus">＋</div>
                    <div class="pp-rspot__empty-title">첫 장소를 추가해보세요</div>
                    <p class="pp-rspot__empty-desc">맛집, 카페, 여행지처럼 다시 찾고<br>싶은 장소를 저장하면 여기서<br>바로 꺼내볼 수 있어요.</p>
                    <span class="pp-rspot__empty-btn">장소 추가하기</span>
                </a>
            @endforelse
        </div>
        @else
        <div class="pp-mine-grid" id="ppMineGrid"></div>
        @endauth
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
(function() {
    const STORAGE_KEY = 'pp_home_pane';
    function setPane(target) {
        document.querySelectorAll('.yg-segtab__btn').forEach(b => b.classList.toggle('is-active', b.dataset.pane === target));
        document.querySelectorAll('.yg-pane').forEach(p => p.classList.toggle('is-active', p.dataset.pane === target));
        const tip = document.getElementById('ppFabTip');
        if (tip) tip.classList.toggle('is-visible', target === 'mine');
        const hero = document.querySelector('.pp-hero2');
        if (hero) {
            hero.classList.toggle('pp-hero2--compact', target === 'mine');
            hero.classList.toggle('pp-hero2--mine', target === 'mine');
        }
        const content = document.querySelector('.yg-content');
        if (content) content.classList.toggle('is-mine', target === 'mine');
        const title = document.getElementById('ppHeroTitle');
        if (title && title.dataset.people && title.dataset.mine) {
            title.textContent = target === 'mine' ? title.dataset.mine : title.dataset.people;
        }
    }
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved === 'people' || saved === 'mine') setPane(saved);

    // 히어로 카테고리 탭 필터 + 내장소 정렬/필터
    const heroTabs = document.getElementById('ppHeroTabs');
    const heroSlide = document.getElementById('ppHeroSlide');
    if (heroTabs && heroSlide) {
        const createUrlBase = @json(route('places.create'));
        const mineGrid = document.getElementById('ppMineGrid');
        let emptyEl = null;
        let mineEmptyEl = null;
        let currentCat = 'all';
        let currentCatName = '';
        function korParticle(word, withJong, withoutJong) {
            if (!word) return withoutJong;
            const code = word.charCodeAt(word.length - 1) - 0xAC00;
            if (code < 0 || code > 11171) return withoutJong;
            return (code % 28) !== 0 ? withJong : withoutJong;
        }
        let currentStatus = localStorage.getItem('pp_mine_status') || 'all';
        let currentSort = localStorage.getItem('pp_mine_sort') || 'created_desc';

        function applyMineFilters() {
            if (!mineGrid) return;
            let visible = 0, total = 0;
            mineGrid.querySelectorAll('.pp-mine-grid__item').forEach(el => {
                total++;
                const matchCat = currentCat === 'all' || el.dataset.cat === currentCat;
                const matchStatus = currentStatus === 'all' || el.dataset.status === currentStatus;
                const show = matchCat && matchStatus;
                el.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            if (mineEmptyEl) { mineEmptyEl.remove(); mineEmptyEl = null; }
            if (total > 0 && visible === 0) {
                mineEmptyEl = document.createElement('a');
                mineEmptyEl.className = 'pp-mine-grid__empty pp-mine-grid__empty--filter';
                mineEmptyEl.href = currentCat !== 'all'
                    ? createUrlBase + '?category=' + encodeURIComponent(currentCat)
                    : createUrlBase;
                let title, desc;
                if (currentCat !== 'all' && currentCatName) {
                    title = '아직 저장한 ' + currentCatName + korParticle(currentCatName, '이', '가') + ' 없어요';
                    desc = currentCatName + korParticle(currentCatName, '을', '를') + ' 추가하면 여기서 모아볼 수 있어요.';
                } else {
                    title = '조건에 맞는 장소가 없어요';
                    desc = '다른 카테고리나 방문상태를 선택해보거나<br>새 장소를 추가해보세요.';
                }
                mineEmptyEl.innerHTML = '<div class="pp-rspot__plus">＋</div>'
                    + '<div class="pp-rspot__empty-title">' + title + '</div>'
                    + '<p class="pp-rspot__empty-desc">' + desc + '</p>'
                    + '<span class="pp-rspot__empty-btn">장소 추가하기</span>';
                mineGrid.appendChild(mineEmptyEl);
            }
        }

        function applyMineSort() {
            if (!mineGrid) return;
            const items = Array.from(mineGrid.querySelectorAll('.pp-mine-grid__item'));
            items.sort((a, b) => {
                if (currentSort === 'name') {
                    return (a.dataset.name || '').localeCompare(b.dataset.name || '', 'ko');
                }
                const av = +a.dataset.created || 0, bv = +b.dataset.created || 0;
                return currentSort === 'created_asc' ? av - bv : bv - av;
            });
            items.forEach(el => mineGrid.appendChild(el));
            if (mineEmptyEl) mineGrid.appendChild(mineEmptyEl);
        }

        function applyCatFilter(cat, catName) {
            currentCat = cat;
            currentCatName = catName || '';
            let visibleCount = 0;
            heroSlide.querySelectorAll('.pp-rspot:not(.pp-rspot--empty-filter)').forEach(el => {
                const show = cat === 'all' || el.dataset.cat === cat;
                el.style.display = show ? '' : 'none';
                el.classList.toggle('is-all', cat === 'all');
                if (show) visibleCount++;
            });
            if (emptyEl) { emptyEl.remove(); emptyEl = null; }
            if (visibleCount === 0 && cat !== 'all') {
                emptyEl = document.createElement('a');
                emptyEl.className = 'pp-rspot pp-rspot--empty pp-rspot--empty-filter';
                emptyEl.href = createUrlBase + '?category=' + encodeURIComponent(cat);
                const emptyTitle = currentCatName
                    ? '아직 저장한 ' + currentCatName + korParticle(currentCatName, '이', '가') + ' 없어요'
                    : '첫 장소를 추가해보세요';
                const emptyDesc = currentCatName
                    ? currentCatName + korParticle(currentCatName, '을', '를') + ' 추가하면 여기서 모아볼 수 있어요.'
                    : '맛집, 카페, 여행지처럼 다시 찾고<br>싶은 장소를 저장하면 여기서<br>바로 꺼내볼 수 있어요.';
                emptyEl.innerHTML = '<div class="pp-rspot__plus">＋</div>'
                    + '<div class="pp-rspot__empty-title">' + emptyTitle + '</div>'
                    + '<p class="pp-rspot__empty-desc">' + emptyDesc + '</p>'
                    + '<span class="pp-rspot__empty-btn">장소 추가하기</span>';
                heroSlide.appendChild(emptyEl);
            }
            heroSlide.scrollLeft = 0;
            applyMineFilters();
        }
        heroTabs.addEventListener('click', (e) => {
            const btn = e.target.closest('.pp-hero2__tab');
            if (!btn) return;
            heroTabs.querySelectorAll('.pp-hero2__tab').forEach(b => b.classList.toggle('is-active', b === btn));
            const name = btn.dataset.cat === 'all' ? '' : (btn.textContent || '').trim();
            applyCatFilter(btn.dataset.cat, name);
        });

        // 정렬/필터 메뉴
        const sfBtn = document.getElementById('ppSortFilterBtn');
        const sfMenu = document.getElementById('ppSortFilterMenu');
        const sfLabel = document.getElementById('ppSortFilterLabel');
        const sortLabels = { 'created_desc':'최근 등록순', 'created_asc':'오래된 순', 'name':'이름순' };
        const statusLabels = { 'all':'', 'planned':'방문예정', 'visited':'방문완료' };
        function refreshSfLabel() {
            const parts = [sortLabels[currentSort]];
            if (statusLabels[currentStatus]) parts.push(statusLabels[currentStatus]);
            sfLabel.textContent = parts.join(' · ');
        }
        function markActive(container, attr, value) {
            container.querySelectorAll(`[data-${attr}]`).forEach(b => {
                b.classList.toggle('is-active', b.dataset[attr] === value);
            });
        }
        if (sfBtn && sfMenu) {
            // 초기 상태 반영
            markActive(sfMenu, 'sort', currentSort);
            markActive(sfMenu, 'status', currentStatus);
            refreshSfLabel();
            applyMineSort();
            applyMineFilters();

            sfBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const open = sfMenu.hidden;
                sfMenu.hidden = !open;
                sfBtn.setAttribute('aria-expanded', String(open));
            });
            document.addEventListener('click', (e) => {
                if (!sfMenu.hidden && !sfMenu.contains(e.target) && e.target !== sfBtn) {
                    sfMenu.hidden = true;
                    sfBtn.setAttribute('aria-expanded', 'false');
                }
            });
            sfMenu.addEventListener('click', (e) => {
                const sortBtn = e.target.closest('[data-sort]');
                const statusBtn = e.target.closest('[data-status]');
                if (sortBtn) {
                    currentSort = sortBtn.dataset.sort;
                    try { localStorage.setItem('pp_mine_sort', currentSort); } catch(_) {}
                    markActive(sfMenu, 'sort', currentSort);
                    applyMineSort();
                } else if (statusBtn) {
                    currentStatus = statusBtn.dataset.status;
                    try { localStorage.setItem('pp_mine_status', currentStatus); } catch(_) {}
                    markActive(sfMenu, 'status', currentStatus);
                    applyMineFilters();
                }
                refreshSfLabel();
            });
        }
    }

    document.querySelectorAll('.yg-segtab__btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.pane;
            setPane(target);
            try { localStorage.setItem(STORAGE_KEY, target); } catch(e) {}
        });
    });
})();

// ── 카테고리 관리 (순서 + 이름편집 + 추가 + 삭제) ──
(function() {
    const csrf = '{{ csrf_token() }}';
    const isGuest = {{ auth()->check() ? 'false' : 'true' }};

    function escHtml(s) { return String(s||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    if (isGuest) return;
    const orderPanel = document.getElementById('catOrderPanel');
    const orderList = document.getElementById('catOrderList');
    if (!orderPanel) return;

    // 상단 버튼으로 열기
    document.getElementById('catOrderEditBtnHero').addEventListener('click', openCatPanel);

    // 취소 버튼 — 저장 없이 패널 닫기
    document.getElementById('catOrderCancel').addEventListener('click', () => {
        orderPanel.hidden = true;
    });

    function openCatPanel() {
        // 사람들 탭에서 눌러도 동작하도록 먼저 내장소 탭으로 전환
        const mineBtn = document.querySelector('.yg-segtab__btn[data-pane="mine"]');
        if (mineBtn && !mineBtn.classList.contains('is-active')) {
            mineBtn.click();
        }
        orderPanel.hidden = false;
        renderCatOrderList();
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

// 게스트 localStorage 장소 hydrate → 통합 그리드에 렌더
@guest
(function() {
    const grid = document.getElementById('ppMineGrid');
    if (!grid) return;
    const list = JSON.parse(localStorage.getItem('pinpick_guest_places') || '[]');
    function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
    if (!list.length) {
        grid.innerHTML = '<a href="{{ route('places.create') }}" class="pp-mine-grid__empty">'
            + '<div class="pp-rspot__plus">＋</div>'
            + '<div class="pp-rspot__empty-title">첫 장소를 추가해보세요</div>'
            + '<p class="pp-rspot__empty-desc">맛집, 카페, 여행지처럼 다시 찾고<br>싶은 장소를 저장하면 여기서<br>바로 꺼내볼 수 있어요.</p>'
            + '<span class="pp-rspot__empty-btn">장소 추가하기</span>'
            + '</a>';
        return;
    }
    list.forEach(p => {
        const badge = p.status === 'visited' ? '방문완료' : '방문예정';
        const addr = (p.road_address || p.address || '').slice(0, 12);
        const card = document.createElement('div');
        card.className = 'pp-mine-grid__item';
        card.dataset.cat = p.category_id;
        card.innerHTML = `
            <div class="pp-mine-grid__thumb">
                <span class="pp-mine-grid__ph">📌</span>
                <span class="pp-mine-grid__badge">${badge}</span>
            </div>
            <div class="pp-mine-grid__body">
                <div class="pp-mine-grid__name">${escapeHtml(p.name)}</div>
                <div class="pp-mine-grid__meta">${escapeHtml(p.category_name || '')}${addr ? ' · ' + escapeHtml(addr) : ''}</div>
            </div>
        `;
        grid.appendChild(card);
    });
})();
@endguest
</script>
@endpush
