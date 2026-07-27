@extends('layouts.app')
@section('page_title', '장소 둘러보기 | 핀픽')
@section('meta_description', '다양한 맛집, 카페, 여행지를 둘러보고 나만의 지도에 저장하세요. 카테고리별 추천 리스트로 새로운 장소를 발견합니다.')
@section('app_class', 'pp-app--explore')

@section('header')
<header class="pp-expl-header">
    <small class="pp-expl-header__sub">직접 찾아보는</small>
    <h1 class="pp-expl-header__title">탐색</h1>
</header>
@endsection

@section('content')
<div class="pp-expl">
    <div class="pp-expl-chips" id="curChips">
        <div class="pp-expl-chips__track">
            <button type="button" class="pp-expl-chip is-active" data-cat="">전체</button>
        </div>
        <div class="pp-expl-chips__fade"></div>
    </div>

    <div class="pp-expl-feed" id="curFeed">
        <div class="pp-expl-feed__loading" id="curLoading">리스트를 불러오는 중...</div>
    </div>
</div>

<script>
(function() {
    const PLACE_MAX = 4;
    const GRADIENTS = [
        'linear-gradient(135deg, #e8ddd4 0%, #d4c8bc 100%)',
        'linear-gradient(150deg, #dcd3c9 0%, #c9bfb4 100%)',
        'linear-gradient(120deg, #e2d8ce 0%, #cec3b7 100%)',
        'linear-gradient(160deg, #ddd4ca 0%, #d0c5b8 100%)',
    ];
    const IS_LOGGED_IN = {{ auth()->check() ? 'true' : 'false' }};
    let allCurations = [];
    let activeCat = '';

    async function init() {
        try {
            const [curRes, catRes] = await Promise.all([
                fetch('/api/curations'),
                fetch('/api/curations/categories'),
            ]);
            allCurations = await curRes.json();
            const categories = await catRes.json();

            const track = document.querySelector('.pp-expl-chips__track');
            categories.forEach(c => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'pp-expl-chip';
                btn.dataset.cat = c.slug;
                btn.textContent = c.label;
                track.appendChild(btn);
            });

            track.addEventListener('click', e => {
                const chip = e.target.closest('.pp-expl-chip');
                if (!chip) return;
                track.querySelectorAll('.pp-expl-chip').forEach(c => c.classList.remove('is-active'));
                chip.classList.add('is-active');
                activeCat = chip.dataset.cat;
                renderFeed();
                document.getElementById('curFeed').scrollIntoView({behavior: 'smooth', block: 'start'});
            });

            renderFeed();
        } catch (e) {
            document.getElementById('curLoading').textContent = '리스트를 불러올 수 없습니다.';
        }
    }

    function renderFeed() {
        const feed = document.getElementById('curFeed');
        const filtered = activeCat
            ? allCurations.filter(c => c.category === activeCat)
            : allCurations;

        if (!filtered.length) {
            feed.innerHTML = '<div class="pp-expl-empty">'
                + '<div class="pp-expl-empty__icon">📭</div>'
                + '<p>아직 이 카테고리에 리스트가 없어요</p>'
                + '<button type="button" class="pp-expl-empty__btn" onclick="document.querySelector(\'[data-cat=\\&quot;\\&quot;]\').click()">전체 보기</button>'
                + '</div>';
            return;
        }

        let html = '';
        filtered.forEach((c, i) => {
            html += renderSection(c);
            if (i === 0 && IS_LOGGED_IN) {
                html += renderCtaCard();
            }
        });
        if (filtered.length > 0 && !IS_LOGGED_IN) {
            html += renderCtaCard();
        }
        feed.innerHTML = html;
    }

    function renderCtaCard() {
        const href = IS_LOGGED_IN ? '{{ route("my.curations.create") }}' : '{{ route("login") }}';
        return '<div class="pp-expl-cta">'
            + '<div class="pp-expl-cta__text">나만 알던 장소, 같이 볼까요?</div>'
            + '<a href="' + href + '" class="pp-expl-cta__btn">내 리스트 만들기</a>'
            + '</div>';
    }

    function renderSection(c) {
        const places = c.places_list || [];
        const savesMeta = c.save_count > 0
            ? '<span class="pp-expl-sec__saves"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg> ' + c.save_count + '명이 담아갔어요</span>'
            : '';

        const savedClass = c.is_saved ? ' is-saved' : '';
        const savedLabel = c.is_saved ? '담음' : '담기';
        const savedIcon = c.is_saved ? '✓' : '+';

        const authorAvatar = c.is_official
            ? '<img class="pp-expl-author__avatar" src="{{ asset("icon-192.png") }}" alt="">'
            : (c.author_avatar
                ? '<img class="pp-expl-author__avatar" src="' + esc(c.author_avatar) + '" alt="">'
                : '<span class="pp-expl-author__avatar pp-expl-author__avatar--initial">' + esc((c.author_name || '?').charAt(0)) + '</span>');
        const authorName = c.is_official ? '핀픽' : esc(c.author_name || '');
        const catLabel = c.category_label || '';

        let cardsHtml = places.slice(0, PLACE_MAX).map((p, i) => {
            let hasBg = !!p.thumb_url;
            let imgSrc = p.thumb_url;
            if (!hasBg && p.lat && p.lng) {
                imgSrc = '/api/static-map?lat=' + encodeURIComponent(p.lat) + '&lng=' + encodeURIComponent(p.lng) + '&overseas=' + (p.is_overseas ? 1 : 0) + '&w=320&h=320';
                hasBg = true;
            }
            const bgStyle = hasBg ? 'background-image:url(' + esc(imgSrc) + ')' : GRADIENTS[i % GRADIENTS.length];
            const styleAttr = hasBg ? bgStyle : 'background:' + bgStyle;
            const placeholderClass = hasBg ? '' : ' pp-expl-pcard--ph';
            const regionCat = [p.region, p.category_label].filter(Boolean).join(' · ');
            return '<a href="/c/' + c.id + '" class="pp-expl-pcard' + placeholderClass + '" style="' + styleAttr + '">'
                + (!hasBg ? '<div class="pp-expl-pcard__pin-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg></div>' : '')
                + '<div class="pp-expl-pcard__overlay">'
                + '<div class="pp-expl-pcard__name">' + esc(p.name) + '</div>'
                + (regionCat ? '<div class="pp-expl-pcard__sub">' + esc(regionCat) + '</div>' : '')
                + '</div></a>';
        }).join('');

        if (places.length > PLACE_MAX) {
            cardsHtml += '<a href="/c/' + c.id + '" class="pp-expl-pcard pp-expl-pcard--more">'
                + '<span class="pp-expl-pcard__more-label">전체 보기</span>'
                + '<span class="pp-expl-pcard__more-count">' + c.places_count + '곳</span>'
                + '<span class="pp-expl-pcard__more-arrow">→</span></a>';
        }

        return '<div class="pp-expl-sec">'
            + '<div class="pp-expl-sec__head">'
            + '<div class="pp-expl-sec__left">'
            + '<div class="pp-expl-author">'
            + authorAvatar
            + '<span class="pp-expl-author__name">' + authorName + '</span>'
            + '<span class="pp-expl-author__dot">·</span>'
            + '<span class="pp-expl-author__count">' + (c.places_count || 0) + '개 매장</span>'
            + '</div>'
            + '<a href="/c/' + c.id + '" class="pp-expl-sec__title">' + esc(c.title) + '</a>'
            + (c.description ? '<p class="pp-expl-sec__desc">' + esc(c.description) + '</p>' : '')
            + savesMeta
            + '</div>'
            + '<a href="/c/' + c.id + '?action=save" class="pp-expl-sec__save-btn' + savedClass + '" onclick="event.stopPropagation()">'
            + '<span>' + savedIcon + ' ' + savedLabel + '</span></a>'
            + '</div>'
            + '<div class="pp-expl-scroll"><div class="pp-expl-scroll__track">' + cardsHtml + '</div></div>'
            + '</div>';
    }

    function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    init();
})();
</script>
@endsection
