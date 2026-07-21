@extends('layouts.app')
@section('page_title', '장소 둘러보기 | 핀픽')
@section('meta_description', '다양한 맛집, 카페, 여행지를 둘러보고 나만의 지도에 저장하세요. 카테고리별 큐레이션으로 새로운 장소를 발견합니다.')
@section('app_class', 'pp-app--home')

@section('header')
<header class="ph-header">
    <div class="ph-header__top">
        <div class="ph-brand">
            <small>직접 찾아보는</small>
            <div class="ph-brand__name">탐색</div>
        </div>
    </div>
</header>
@endsection

@section('content')
<div class="ph-content">
    <div class="ph-section" style="padding-top:6px">
        <div class="ph-section__title"><strong>큐레이션</strong></div>
    </div>

    <div class="pp-cur-regions" id="curRegions">
        <button type="button" class="pp-cur-region-chip is-active" data-region="">전체</button>
    </div>

    <div class="pp-cur-cards" id="curCards">
        <div class="pp-cur-empty" id="curLoading">큐레이션을 불러오는 중...</div>
    </div>
</div>

<script>
(function() {
    let allCurations = [];
    let activeRegion = '';

    async function loadCurations() {
        try {
            const [curRes, regRes] = await Promise.all([
                fetch('/api/curations'),
                fetch('/api/curations/regions'),
            ]);
            allCurations = await curRes.json();
            const regions = await regRes.json();

            const regWrap = document.getElementById('curRegions');
            regions.forEach(r => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'pp-cur-region-chip';
                btn.dataset.region = r;
                btn.textContent = r;
                regWrap.appendChild(btn);
            });

            regWrap.addEventListener('click', e => {
                const chip = e.target.closest('.pp-cur-region-chip');
                if (!chip) return;
                regWrap.querySelectorAll('.pp-cur-region-chip').forEach(c => c.classList.remove('is-active'));
                chip.classList.add('is-active');
                activeRegion = chip.dataset.region;
                renderCards();
            });

            renderCards();
        } catch (e) {
            document.getElementById('curLoading').textContent = '큐레이션을 불러올 수 없습니다.';
        }
    }

    function renderCards() {
        const wrap = document.getElementById('curCards');
        const filtered = activeRegion
            ? allCurations.filter(c => c.region_label === activeRegion)
            : allCurations;

        if (!filtered.length) {
            wrap.innerHTML = '<div class="pp-cur-empty">아직 큐레이션이 없습니다.</div>';
            return;
        }

        wrap.innerHTML = filtered.map(c => {
            const thumb = c.cover_url
                ? '<img src="' + esc(c.cover_url) + '" alt="" loading="lazy">'
                : '<span class="pp-cur-card__emoji">📍</span>';
            const typeBadge = c.type === 'course' ? '<span class="pp-cur-card__badge">코스</span>' : '';
            const regionBadge = c.region_label ? '<span class="pp-cur-card__badge">' + esc(c.region_label) + '</span>' : '';
            return '<a href="/c/' + c.id + '" class="pp-cur-card">'
                + '<div class="pp-cur-card__thumb">' + thumb + '</div>'
                + '<div class="pp-cur-card__body">'
                + '<h3 class="pp-cur-card__title">' + esc(c.title) + '</h3>'
                + (c.description ? '<p class="pp-cur-card__desc">' + esc(c.description) + '</p>' : '')
                + '<div class="pp-cur-card__meta">' + typeBadge + regionBadge
                + '<span>장소 ' + (c.places_count || 0) + '곳</span>'
                + (c.save_count > 0 ? '<span>' + c.save_count + '명 담기</span>' : '')
                + '</div></div></a>';
        }).join('');
    }

    function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    loadCurations();
})();
</script>
@endsection
