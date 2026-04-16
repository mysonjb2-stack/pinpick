@extends('layouts.app')
@section('title', '전체보기')
@section('app_class', 'pp-app--xp')

@section('content')
<div class="pp-xp" data-default-region="{{ request('region', '') }}">
    <header class="pp-xp__header">
        <button type="button" class="pp-xp__back" aria-label="뒤로">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>
        <h1 class="pp-xp__title" id="ppXpTitle">전체보기</h1>
        <button type="button" class="pp-xp__sort" id="ppXpSort" data-sort="popular">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 7 6"></polyline><polyline points="3 12 11 12"></polyline><polyline points="3 18 15 18"></polyline></svg>
            <span id="ppXpSortLabel">인기순</span>
        </button>
    </header>

    {{-- 테마 탭 (type=theme 일 때만 노출) --}}
    <div class="pp-xp__themes" id="ppXpThemes" hidden>
        <button type="button" class="pp-xp__theme is-active" data-theme="food">맛집</button>
        <button type="button" class="pp-xp__theme" data-theme="cafe">카페</button>
        <button type="button" class="pp-xp__theme" data-theme="travel">여행</button>
        <button type="button" class="pp-xp__theme" data-theme="beauty">뷰티/케어</button>
        <button type="button" class="pp-xp__theme" data-theme="stay">숙소</button>
        <button type="button" class="pp-xp__theme" data-theme="culture">문화/여가</button>
    </div>

    {{-- 지역 탭 (type=travel 일 때만 노출 — 국내=도시, 해외=나라) --}}
    <div class="pp-xp__themes" id="ppXpRegions" hidden></div>

    <div class="pp-xp__grid" id="ppXpGrid">
        @for ($i = 0; $i < 6; $i++)
        <div class="pp-xp-card pp-xp-card--skel">
            <div class="pp-xp-card__thumb pp-skel"></div>
            <div class="pp-xp-card__body">
                <div class="pp-xp-card__name pp-skel pp-skel--line"></div>
                <div class="pp-xp-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
            </div>
        </div>
        @endfor
    </div>

    <div class="pp-xp__sentinel" id="ppXpSentinel"></div>

    <div class="pp-xp__loading" id="ppXpLoading" hidden>
        <span class="pp-xp__spinner"></span>
    </div>

    <div class="pp-xp__end" id="ppXpEnd" hidden>모든 장소를 확인했어요</div>

    <div class="pp-xp__empty" id="ppXpEmpty" hidden>
        <div class="pp-xp__empty-emoji">📍</div>
        <p class="pp-xp__empty-title">아직 데이터가 부족해요</p>
        <p class="pp-xp__empty-sub">조건을 바꾸거나 다른 섹션을 둘러보세요.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const $ = (s, r) => (r||document).querySelector(s);
    const $$ = (s, r) => Array.from((r||document).querySelectorAll(s));

    // 깨진 이미지 → noimg placeholder 로 교체 (저장수 뱃지는 유지)
    window.ppXpImgFail = function(img) {
        const noimg = document.createElement('div');
        noimg.className = 'pp-xp-card__noimg';
        noimg.textContent = '📍';
        img.replaceWith(noimg);
    };

    const params = new URLSearchParams(location.search);
    const STATE = {
        type: params.get('type') || 'weekly',
        theme: params.get('theme') || 'food',
        region: params.get('region') || '',
        scope: params.get('scope') || 'domestic',  // travel 전용
        sort: 'popular',
        page: 1,
        loading: false,
        done: false,
    };

    const THEME_LABELS = {
        food:'맛집', cafe:'카페', travel:'여행',
        beauty:'뷰티/케어', stay:'숙소', culture:'문화/여가',
        medical:'병원/약국', shopping:'쇼핑', etc:'기타',
    };
    const TITLES = {
        weekly: '이번주 핫한 저장',
        local: (r) => r ? `${r}에서 많이 저장한 장소` : '많이 저장한 장소',
        theme: (slug) => `${THEME_LABELS[slug] || ''} 인기 장소`.trim(),
        travel: (scope, region) => {
            const base = scope === 'overseas' ? '해외 여행자들의 핀픽' : '국내 여행자들의 핀픽';
            return region ? `${region} ${scope === 'overseas' ? '여행' : ''}`.trim() + ' 핀픽' : base;
        },
    };

    const titleEl = $('#ppXpTitle');
    const sortBtn = $('#ppXpSort');
    const sortLabel = $('#ppXpSortLabel');
    const themesEl = $('#ppXpThemes');
    const regionsEl = $('#ppXpRegions');
    const gridEl = $('#ppXpGrid');
    const loadingEl = $('#ppXpLoading');
    const endEl = $('#ppXpEnd');
    const emptyEl = $('#ppXpEmpty');
    const sentinel = $('#ppXpSentinel');

    function applyTitle() {
        if (STATE.type === 'local') {
            titleEl.textContent = TITLES.local(STATE.region);
        } else if (STATE.type === 'theme') {
            titleEl.textContent = TITLES.theme(STATE.theme);
        } else if (STATE.type === 'travel') {
            titleEl.textContent = TITLES.travel(STATE.scope, STATE.region);
        } else {
            titleEl.textContent = TITLES[STATE.type] || '전체보기';
        }
    }

    function setupThemes() {
        if (STATE.type !== 'theme') { themesEl.hidden = true; return; }
        themesEl.hidden = false;
        $$('.pp-xp__theme', themesEl).forEach(b => {
            b.classList.toggle('is-active', b.dataset.theme === STATE.theme);
            b.addEventListener('click', () => {
                if (STATE.loading) return;
                STATE.theme = b.dataset.theme;
                $$('.pp-xp__theme', themesEl).forEach(x => x.classList.toggle('is-active', x === b));
                applyTitle();
                resetAndLoad();
            });
        });
    }

    function renderRegionTabs(regions) {
        if (STATE.type !== 'travel' || !regions || !regions.length) {
            regionsEl.hidden = true;
            regionsEl.innerHTML = '';
            return;
        }
        regionsEl.hidden = false;
        const tabs = ['<button type="button" class="pp-xp__theme' + (STATE.region === '' ? ' is-active' : '') + '" data-region="">전체</button>'];
        regions.forEach(r => {
            tabs.push('<button type="button" class="pp-xp__theme' + (STATE.region === r ? ' is-active' : '') + '" data-region="' + escapeHtml(r) + '">' + escapeHtml(r) + '</button>');
        });
        regionsEl.innerHTML = tabs.join('');
        $$('.pp-xp__theme', regionsEl).forEach(b => {
            b.addEventListener('click', () => {
                if (STATE.loading) return;
                STATE.region = b.dataset.region || '';
                $$('.pp-xp__theme', regionsEl).forEach(x => x.classList.toggle('is-active', x === b));
                applyTitle();
                resetAndLoad(true); // travel 탭 변경 시 regions 재요청 X
            });
        });
    }

    function setupSort() {
        sortBtn.addEventListener('click', () => {
            if (STATE.loading) return;
            STATE.sort = STATE.sort === 'popular' ? 'recent' : 'popular';
            sortBtn.dataset.sort = STATE.sort;
            sortLabel.textContent = STATE.sort === 'popular' ? '인기순' : '최신순';
            resetAndLoad();
        });
    }

    function setupBack() {
        $('.pp-xp__back').addEventListener('click', () => {
            if (history.length > 1) history.back();
            else location.href = '/';
        });
    }

    function escapeHtml(s) {
        return String(s||'').replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[c]));
    }

    function buildCard(it) {
        const thumb = it.thumbnail
            ? `<img src="${escapeHtml(it.thumbnail)}" alt="" loading="lazy" onerror="window.ppXpImgFail&&window.ppXpImgFail(this)">`
            : `<div class="pp-xp-card__noimg">📍</div>`;

        // 좌하단: 노란 저장수 뱃지 (총 저장수 또는 이번주 저장수)
        const saveCount = it.total_count || it.period_count || it.week_count || 0;
        const saveBadge = saveCount > 0
            ? `<span class="pp-xp-card__save">${saveCount.toLocaleString('ko-KR')} 저장</span>`
            : '';

        const themePart = it.theme_name
            ? `<span class="pp-xp-card__theme">#${escapeHtml(it.theme_name)}</span>`
            : '';
        const addrPart = it.address
            ? `<span class="pp-xp-card__addr">${escapeHtml(it.address)}</span>`
            : '';
        const sep = (themePart && addrPart)
            ? `<span class="pp-xp-card__dot">·</span>`
            : '';
        const metaLine = (themePart || addrPart)
            ? `<div class="pp-xp-card__meta">${themePart}${sep}${addrPart}</div>`
            : '';

        return `
        <a class="pp-xp-card" href="/place/${it.id}">
            <div class="pp-xp-card__thumb">
                ${thumb}
                ${saveBadge}
            </div>
            <div class="pp-xp-card__body">
                <div class="pp-xp-card__name">${escapeHtml(it.name)}</div>
                ${metaLine}
            </div>
        </a>`;
    }

    async function fetchPage() {
        const qp = new URLSearchParams({
            type: STATE.type,
            sort: STATE.sort,
            page: String(STATE.page),
            limit: '20',
        });
        if (STATE.type === 'theme') qp.set('theme', STATE.theme);
        if (STATE.type === 'local' && STATE.region) qp.set('region', STATE.region);
        if (STATE.type === 'travel') {
            qp.set('scope', STATE.scope);
            if (STATE.region) qp.set('region', STATE.region);
        }

        const res = await fetch(`/api/places/trending/list?${qp.toString()}`, {
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error('http ' + res.status);
        return res.json();
    }

    function renderItems(items, append) {
        if (!append) gridEl.innerHTML = '';
        const html = items.map(buildCard).join('');
        gridEl.insertAdjacentHTML('beforeend', html);
    }

    async function loadMore(opts) {
        opts = opts || {};
        if (STATE.loading || STATE.done) return;
        STATE.loading = true;
        loadingEl.hidden = false;
        try {
            const data = await fetchPage();
            const items = data.items || [];
            const append = STATE.page > 1;

            // 첫 페이지 응답이면 travel 지역 탭 갱신 (skipRegionTabs 옵션 시 생략)
            if (!append && STATE.type === 'travel' && !opts.skipRegionTabs) {
                renderRegionTabs(data.regions || []);
            }

            if (!append) {
                gridEl.innerHTML = '';
                if (!items.length) {
                    emptyEl.hidden = false;
                    endEl.hidden = true;
                    STATE.done = true;
                    return;
                }
                emptyEl.hidden = true;
            }
            renderItems(items, append);
            STATE.done = !data.has_more;
            // 한 번이라도 추가 로드가 있었던 경우에만 "모든 장소를 확인했어요" 노출
            if (STATE.done && append) endEl.hidden = false;
            STATE.page += 1;
        } catch (e) {
            console.warn('[trending list] fail', e);
            STATE.done = true;
            if (STATE.page === 1) emptyEl.hidden = false;
        } finally {
            STATE.loading = false;
            loadingEl.hidden = true;
        }
    }

    function resetAndLoad(skipRegionTabs) {
        STATE.page = 1;
        STATE.done = false;
        emptyEl.hidden = true;
        endEl.hidden = true;
        gridEl.innerHTML = Array(6).fill(0).map(() =>
            `<div class="pp-xp-card pp-xp-card--skel">
                <div class="pp-xp-card__thumb pp-skel"></div>
                <div class="pp-xp-card__body">
                    <div class="pp-xp-card__name pp-skel pp-skel--line"></div>
                    <div class="pp-xp-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
                </div>
            </div>`
        ).join('');
        loadMore({ skipRegionTabs: !!skipRegionTabs });
    }

    function setupObserver() {
        const io = new IntersectionObserver((entries) => {
            entries.forEach(en => {
                if (en.isIntersecting) loadMore();
            });
        }, { rootMargin: '200px' });
        io.observe(sentinel);
    }

    // ── 초기 ──
    applyTitle();
    setupThemes();
    setupSort();
    setupBack();
    setupObserver();
    loadMore();
})();
</script>
@endpush
