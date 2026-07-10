@extends('layouts.app')
@section('page_title', '나만의 장소, 나만의 지도 | 핀픽')
@section('meta_description', '맛집, 카페, 여행지, 가고싶은 곳까지 — 내 장소를 모두 저장하고 쉽게 꺼내쓰는 나만의 지도앱, 핀픽. 로그인 없이 바로 시작하세요.')
@section('canonical', 'https://mypinpick.net')
@section('app_class', 'pp-app--home')

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'WebSite',
            'name' => '핀픽',
            'alternateName' => 'Pinpick',
            'url' => 'https://mypinpick.net',
        ],
        [
            '@type' => 'Organization',
            'name' => '핀픽',
            'alternateName' => 'Pinpick',
            'url' => 'https://mypinpick.net',
            'logo' => 'https://mypinpick.net/icon-512.png',
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('header')
<header class="yg-header">
    <div class="yg-header__brand">
        <img src="{{ asset('icon-192.png') }}?v={{ filemtime(public_path('icon-192.png')) }}" alt="" class="yg-header__logo-ico" width="28" height="28">
        <div class="yg-header__brand-text">
            <span class="yg-header__logo">핀픽,</span>
            <span class="yg-header__sub">나만의 장소 · 나만의 지도</span>
        </div>
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

    {{-- personal-only-v1: 세그먼트 탭 제거 (v1-with-people 태그에서 복원 가능) --}}
    {{--
    <div class="yg-segtab">
        <button type="button" class="yg-segtab__btn is-active" data-pane="people">사람들</button>
        <button type="button" class="yg-segtab__btn" data-pane="mine">내 장소</button>
    </div>
    --}}

    {{-- 상단 히어로 카드 (요약 + 카테고리 탭 + 최근 장소) --}}
    <div class="pp-hero2">
        <div class="pp-hero2__head">
            <div class="pp-hero2__titles">
                @auth
                    <h2 class="pp-hero2__title" id="ppHeroTitle" data-people="{{ auth()->user()->name }}님의 지도" data-mine="내 장소">{{ auth()->user()->name }}님의 지도</h2>
                @else
                    <h2 class="pp-hero2__title" id="ppHeroTitle">나만의 다양한 장소를 저장해보세요</h2>
                    <div class="pp-guest-cta" id="ppGuestLoginCta" hidden>
                        <div class="pp-guest-cta__title">간편 로그인하고 더 많이 저장해보세요</div>
                        <ul class="pp-guest-cta__features">
                            <li>무제한 장소 저장</li>
                            <li>장소 사진도 함께 저장</li>
                            <li>국내·해외 장소를 한눈에</li>
                        </ul>
                        <a href="{{ route('login') }}" class="pp-guest-cta__btn">로그인하고 내 지도 이어보기</a>
                    </div>
                @endauth
                @auth
                <div class="pp-hero2__stats">
                    <span class="pp-hero2__stat">저장 {{ $savedCount }}</span>
                    <span class="pp-hero2__stat-dot">·</span>
                    <span class="pp-hero2__stat" id="ppHeroCatCount">카테고리 {{ $categories->count() }}</span>
                </div>
                @endauth
            </div>
            @auth
            <div class="pp-hero2__head-actions">
                <a href="{{ route('mypage.categories') }}" class="yg-catorder__btn pp-hero2__edit-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                    카테고리 추가/수정
                </a>
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
                        @php $thumbUrl = $p->images->first() ? $p->images->first()->thumb_url : ($p->thumbnail ? asset('storage/' . $p->thumbnail) : null); @endphp
                        <div class="pp-rspot__thumb"@if($thumbUrl) style="background-image:url('{{ $thumbUrl }}');background-size:cover;background-position:center"@endif>
                            @unless($thumbUrl)<span class="pp-rspot__ph">{{ $p->category?->icon ?? '📌' }}</span>@endunless
                            <span class="pp-rspot__badge">{{ $p->status === 'visited' ? '방문완료' : '방문예정' }}</span>
                            @if($p->status === 'visited' && $p->visited_at)
                                <span class="pp-rspot__date">{{ $p->visited_at->format('Y.m.d') }}</span>
                            @endif
                        </div>
                        <div class="pp-rspot__body">
                            <div class="pp-rspot__name">{{ $p->name }}</div>
                            <div class="pp-rspot__meta">
                                <span>{{ $p->category?->name ?? '' }}</span>
                                @if($p->themes->isNotEmpty())
                                    <span class="pp-meta-dot" aria-hidden="true"></span>
                                    @foreach($p->themes->take(2) as $theme)
                                        <span class="pp-theme-badge">{{ $theme->name }}</span>
                                    @endforeach
                                @endif
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

    {{-- personal-only-v1: 사람들 탭 통째로 비활성화 (v1-with-people 태그에서 복원 가능) --}}
    @if(false)
    <div class="yg-pane is-active" data-pane="people" data-default-region="{{ $defaultRegion ?? '' }}">
        {{-- 1섹션: 이번주 핫한 저장 --}}
        <section class="pp-trend" data-section="weekly">
            <div class="pp-trend__head">
                <div class="pp-trend__titles">
                    <h3 class="pp-trend__title">이번주 핫한 저장</h3>
                    <p class="pp-trend__sub">전국에서 이번주 저장이 빠르게 늘어난 장소</p>
                </div>
                <a href="/explore/trending?type=weekly" class="pp-trend__more">전체보기 ›</a>
            </div>
            <div class="pp-trend__hscroll" data-list="weekly">
                <div class="pp-trend-card pp-trend-card--skel">
                    <div class="pp-trend-card__thumb pp-skel"></div>
                    <div class="pp-trend-card__body">
                        <div class="pp-trend-card__name pp-skel pp-skel--line"></div>
                        <div class="pp-trend-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
                    </div>
                </div>
                <div class="pp-trend-card pp-trend-card--skel">
                    <div class="pp-trend-card__thumb pp-skel"></div>
                    <div class="pp-trend-card__body">
                        <div class="pp-trend-card__name pp-skel pp-skel--line"></div>
                        <div class="pp-trend-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
                    </div>
                </div>
                <div class="pp-trend-card pp-trend-card--skel">
                    <div class="pp-trend-card__thumb pp-skel"></div>
                    <div class="pp-trend-card__body">
                        <div class="pp-trend-card__name pp-skel pp-skel--line"></div>
                        <div class="pp-trend-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 2섹션: 내 지역에서 많이 저장한 장소 --}}
        <section class="pp-trend" data-section="region" hidden>
            <div class="pp-trend__head">
                <div class="pp-trend__titles">
                    <h3 class="pp-trend__title" data-title="region">전국에서 많이 저장한 장소</h3>
                    <p class="pp-trend__sub">현재 위치 또는 최근 본 지역 기준으로 개인화된 섹션</p>
                </div>
                <a href="#" class="pp-trend__more" data-more="region">더보기 ›</a>
            </div>
            <div class="pp-trend__hscroll" data-list="region">
                <div class="pp-trend-card pp-trend-card--skel">
                    <div class="pp-trend-card__thumb pp-skel"></div>
                    <div class="pp-trend-card__body">
                        <div class="pp-trend-card__name pp-skel pp-skel--line"></div>
                        <div class="pp-trend-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
                    </div>
                </div>
                <div class="pp-trend-card pp-trend-card--skel">
                    <div class="pp-trend-card__thumb pp-skel"></div>
                    <div class="pp-trend-card__body">
                        <div class="pp-trend-card__name pp-skel pp-skel--line"></div>
                        <div class="pp-trend-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
                    </div>
                </div>
                <div class="pp-trend-card pp-trend-card--skel">
                    <div class="pp-trend-card__thumb pp-skel"></div>
                    <div class="pp-trend-card__body">
                        <div class="pp-trend-card__name pp-skel pp-skel--line"></div>
                        <div class="pp-trend-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 3섹션: 요즘 많이 찾는 장소 (테마 탭) --}}
        <section class="pp-trend" data-section="theme">
            <div class="pp-trend__head">
                <div class="pp-trend__titles">
                    <h3 class="pp-trend__title">요즘 많이 찾는 장소</h3>
                </div>
                <a href="#" class="pp-trend__more" data-more="theme">더보기 ›</a>
            </div>
            <div class="pp-trend__themes" id="ppThemeTabs" role="tablist">
                <button type="button" class="pp-trend__theme is-active" data-theme="food">맛집</button>
                <button type="button" class="pp-trend__theme" data-theme="cafe">카페</button>
                <button type="button" class="pp-trend__theme" data-theme="travel">여행</button>
                <button type="button" class="pp-trend__theme" data-theme="beauty">뷰티/케어</button>
                <button type="button" class="pp-trend__theme" data-theme="stay">숙소</button>
                <button type="button" class="pp-trend__theme" data-theme="culture">문화/여가</button>
            </div>
            <div class="pp-trend__hscroll" data-list="theme">
                <div class="pp-trend-card pp-trend-card--skel">
                    <div class="pp-trend-card__thumb pp-skel"></div>
                    <div class="pp-trend-card__body">
                        <div class="pp-trend-card__name pp-skel pp-skel--line"></div>
                        <div class="pp-trend-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
                    </div>
                </div>
                <div class="pp-trend-card pp-trend-card--skel">
                    <div class="pp-trend-card__thumb pp-skel"></div>
                    <div class="pp-trend-card__body">
                        <div class="pp-trend-card__name pp-skel pp-skel--line"></div>
                        <div class="pp-trend-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
                    </div>
                </div>
                <div class="pp-trend-card pp-trend-card--skel">
                    <div class="pp-trend-card__thumb pp-skel"></div>
                    <div class="pp-trend-card__body">
                        <div class="pp-trend-card__name pp-skel pp-skel--line"></div>
                        <div class="pp-trend-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 4섹션: 국내 여행자들의 핀픽 --}}
        <section class="pp-trend" data-section="travel-domestic">
            <div class="pp-trend__head">
                <div class="pp-trend__titles">
                    <h3 class="pp-trend__title">국내 여행자들의 핀픽</h3>
                    <p class="pp-trend__sub">요즘 많이 저장되는 국내 여행지</p>
                </div>
                <a href="/explore/trending?type=travel&scope=domestic" class="pp-trend__more" data-more="travel-domestic">더보기 ›</a>
            </div>
            <div class="pp-trend__hscroll pp-trend__hscroll--big" data-list="travel-domestic">
                <div class="pp-trend-card pp-trend-card--big pp-trend-card--skel">
                    <div class="pp-trend-card__thumb pp-skel"></div>
                    <div class="pp-trend-card__name pp-skel pp-skel--line"></div>
                    <div class="pp-trend-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
                </div>
                <div class="pp-trend-card pp-trend-card--big pp-trend-card--skel">
                    <div class="pp-trend-card__thumb pp-skel"></div>
                    <div class="pp-trend-card__name pp-skel pp-skel--line"></div>
                    <div class="pp-trend-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
                </div>
                <div class="pp-trend-card pp-trend-card--big pp-trend-card--skel">
                    <div class="pp-trend-card__thumb pp-skel"></div>
                    <div class="pp-trend-card__name pp-skel pp-skel--line"></div>
                    <div class="pp-trend-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
                </div>
            </div>
        </section>

        {{-- 5섹션: 해외 여행자들의 핀픽 --}}
        <section class="pp-trend" data-section="travel-overseas">
            <div class="pp-trend__head">
                <div class="pp-trend__titles">
                    <h3 class="pp-trend__title">해외 여행자들의 핀픽</h3>
                    <p class="pp-trend__sub">요즘 많이 저장되는 해외 여행지</p>
                </div>
                <a href="/explore/trending?type=travel&scope=overseas" class="pp-trend__more" data-more="travel-overseas">더보기 ›</a>
            </div>
            <div class="pp-trend__hscroll pp-trend__hscroll--big" data-list="travel-overseas">
                <div class="pp-trend-card pp-trend-card--big pp-trend-card--skel">
                    <div class="pp-trend-card__thumb pp-skel"></div>
                    <div class="pp-trend-card__name pp-skel pp-skel--line"></div>
                    <div class="pp-trend-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
                </div>
                <div class="pp-trend-card pp-trend-card--big pp-trend-card--skel">
                    <div class="pp-trend-card__thumb pp-skel"></div>
                    <div class="pp-trend-card__name pp-skel pp-skel--line"></div>
                    <div class="pp-trend-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
                </div>
                <div class="pp-trend-card pp-trend-card--big pp-trend-card--skel">
                    <div class="pp-trend-card__thumb pp-skel"></div>
                    <div class="pp-trend-card__name pp-skel pp-skel--line"></div>
                    <div class="pp-trend-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>
                </div>
            </div>
        </section>
    </div>

    @endif

    {{-- personal-only-v1: 내 장소 영역만 노출. is-active 기본 활성화 --}}
    <div class="yg-pane is-active" data-pane="mine">
        {{-- 카테고리 관리 버튼은 히어로 헤드로 이동됨. 패널은 그대로 사용 --}}
        @auth
        <div class="pp-mine-sechead">
            <h3 class="pp-mine-sechead__title">전체 장소</h3>
            <div class="pp-mine-sechead__actions">
                <button type="button" class="pp-mine-chip" id="ppShareBtn" hidden>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                    공유
                </button>
                <button type="button" class="pp-mine-chip" id="ppSelEnter">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span id="ppSelEnterLabel">선택</span>
                </button>
                <div class="pp-sortfilter" id="ppSortFilter">
                    <button type="button" class="pp-mine-chip" id="ppSortFilterBtn" aria-expanded="false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M3 6h18"/><path d="M7 12h10"/><path d="M11 18h2"/></svg>
                        <span id="ppSortFilterLabel">정렬/필터</span>
                    </button>
                <div class="pp-sortfilter__menu" id="ppSortFilterMenu" hidden>
                    <div class="pp-sortfilter__group">
                        <div class="pp-sortfilter__gtitle">정렬</div>
                        <button type="button" class="pp-sortfilter__opt is-active" data-sort="created_desc">최근 등록순</button>
                        <button type="button" class="pp-sortfilter__opt" data-sort="custom">내 순서</button>
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
        </div>
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
                <a href="{{ route('places.show', $p) }}" class="pp-mine-grid__item" data-id="{{ $p->id }}" data-cat="{{ $p->category_id }}" data-name="{{ $p->name }}" data-created="{{ $p->created_at->timestamp }}" data-status="{{ $p->status }}" data-order="{{ $p->sort_order }}">
                    @php
                        $thumbUrl = $p->images->first() ? $p->images->first()->thumb_url : ($p->thumbnail ? asset('storage/' . $p->thumbnail) : null);
                        $mapThumb = (!$thumbUrl && $p->lat && $p->lng)
                            ? '/api/static-map?lat=' . $p->lat . '&lng=' . $p->lng . '&overseas=' . ($p->is_overseas ? 1 : 0) . '&w=320&h=320'
                            : null;
                    @endphp
                    <div class="pp-mine-grid__thumb"@if($thumbUrl) style="background-image:url('{{ $thumbUrl }}');background-size:cover;background-position:center"@endif>
                        <span class="pp-mine-grid__check"><svg width="14" height="14" viewBox="0 0 22 22" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 11 10 15 16 7"/></svg></span>
                        @if($mapThumb)
                            <img class="pp-mine-grid__thumb-img" src="{{ $mapThumb }}" alt="{{ $p->name }} 위치 지도" loading="lazy" onerror="this.remove()">
                        @endif
                        @unless($thumbUrl)<span class="pp-mine-grid__ph">{{ $p->category?->icon ?? '📌' }}</span>@endunless
                        <span class="pp-mine-grid__badge">{{ $p->status === 'visited' ? '방문완료' : '방문예정' }}</span>
                        <span class="pp-mine-grid__region {{ $p->is_overseas ? 'is-overseas' : '' }}">{{ $p->is_overseas ? '해외' : '국내' }}</span>
                        @if($p->status === 'visited' && $p->visited_at)
                            <span class="pp-mine-grid__date">{{ $p->visited_at->format('Y.m.d') }}</span>
                        @endif
                        <div class="pp-mine-grid__reorder" aria-hidden="true">
                            <button type="button" class="pp-mine-grid__arrow" data-dir="up" aria-label="위로">↑</button>
                            <button type="button" class="pp-mine-grid__arrow" data-dir="down" aria-label="아래로">↓</button>
                        </div>
                    </div>
                    <div class="pp-mine-grid__body">
                        <div class="pp-mine-grid__name">{{ $p->name }}</div>
                        <div class="pp-mine-grid__meta">
                            <span class="pp-mine-grid__cat">{{ $p->category?->name ?? '' }}</span>
                            @if($p->themes->isNotEmpty())
                                <span class="pp-mine-grid__dot" aria-hidden="true">·</span>
                                <span class="pp-card-theme-badge">{{ $p->themes->first()->name }}</span>
                            @endif
                        </div>
                        @if($p->road_address || $p->address)
                            <div class="pp-mine-grid__addr">{{ Str::limit($p->road_address ?: $p->address, 20) }}</div>
                        @endif
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
        <div class="pp-footer__policy">
            <a href="#">이용약관</a> ㅣ <a href="#">개인정보 수집이용</a> ㅣ <a href="#">위치정보 이용약관</a>
        </div>
        <button type="button" class="pp-footer__biz-toggle" id="ppBizToggle">
            사업자 정보
            <svg class="pp-footer__biz-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <address class="pp-footer__biz-content" id="ppBizContent" hidden>
            <span>(주) 맵큐브</span>
            <p>주소 : 경기도 성남시 분당구 황새울로 354 8층</p>
            <p>대표이사 : 이학영 | 사업자등록번호: 230-81-13255 | 통신판매번호 : 2023-성남분당A-0360 | 전화번호 : 1670-1376 | 전자우편주소 : help.mapcube@gmail.com</p>
        </address>
        <div class="pp-footer__copy">&copy; {{ date('Y') }} 핀픽. All rights reserved.</div>
    </footer>
    <script>
    document.getElementById('ppBizToggle').addEventListener('click', function() {
        const content = document.getElementById('ppBizContent');
        const open = content.hidden;
        content.hidden = !open;
        this.classList.toggle('is-open', open);
    });
    </script>

</div>

{{-- 공유 바텀시트 --}}
@auth
<div class="pp-share-sheet" id="ppShareSheet">
    <div class="pp-share-sheet__backdrop" data-close-share></div>
    <div class="pp-share-sheet__panel">
        <div class="pp-share-sheet__handle"></div>
        <h3 class="pp-share-sheet__title">장소 공유</h3>
        <div class="pp-share-sheet__field">
            <label class="pp-share-sheet__label">공유 제목</label>
            <input type="text" class="pp-share-sheet__input" id="ppShareTitle" maxlength="100">
        </div>
        <div class="pp-share-sheet__field">
            <label class="pp-share-sheet__label">장소명 표시 방식</label>
            <label class="pp-share-sheet__radio">
                <input type="radio" name="shareMode" value="original" checked>
                <span>기본 장소명으로 보내기</span>
            </label>
            <label class="pp-share-sheet__radio">
                <input type="radio" name="shareMode" value="custom">
                <span>내가 지은 이름과 메모 포함</span>
            </label>
            <p class="pp-share-sheet__hint" id="ppShareModeHint" hidden>직접 입력한 장소명과 메모가 상대방에게 그대로 보여요</p>
        </div>
        <div class="pp-share-sheet__buttons">
            <button type="button" class="pp-btn pp-btn--kakao-share" id="ppShareKakao">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3C6.48 3 2 6.58 2 11c0 2.86 1.88 5.37 4.7 6.78-.2.74-.75 2.81-.86 3.25-.14.55.2.54.42.4.17-.12 2.7-1.84 3.79-2.58.64.1 1.3.15 1.95.15 5.52 0 10-3.58 10-8S17.52 3 12 3z"/></svg>
                카카오톡으로 공유
            </button>
            <button type="button" class="pp-btn pp-btn--ghost" id="ppShareCopyLink">링크 복사</button>
        </div>
    </div>
</div>
{{-- 다중 선택 모드 UI --}}
<div class="pp-sel-bar" id="ppSelBar" style="display:none">
    <div class="pp-sel-bar__left">
        <span class="pp-sel-bar__count" id="ppSelCount">0개 선택됨</span>
        <button type="button" class="pp-sel-bar__all" id="ppSelAll">전체선택</button>
    </div>
    <button type="button" class="pp-sel-bar__close" id="ppSelClose">✕</button>
</div>
<div class="pp-sel-actions" id="ppSelActions" style="display:none">
    <button type="button" class="pp-sel-actions__btn pp-sel-actions__btn--share" id="ppSelShareBtn2" disabled>공유</button>
    <button type="button" class="pp-sel-actions__btn pp-sel-actions__btn--move" id="ppSelMove" disabled>카테고리 이동</button>
    <button type="button" class="pp-sel-actions__btn pp-sel-actions__btn--delete" id="ppSelDelete" disabled>삭제</button>
</div>
<div class="pp-sel-move-sheet" id="ppSelMoveSheet">
    <div class="pp-sel-move-sheet__backdrop" data-role="close-move"></div>
    <div class="pp-sel-move-sheet__panel">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <h3 style="font-size:16px;font-weight:700;color:var(--pp-text,#2b211e);margin:0">이동할 카테고리 선택</h3>
            <button type="button" style="border:none;background:none;font-size:20px;color:var(--pp-text-sub,#6b5e52);cursor:pointer;padding:4px" data-role="close-move">✕</button>
        </div>
        <div class="pp-sel-move-sheet__list" id="ppSelMoveList"></div>
    </div>
</div>
@endauth

@endsection

@push('scripts')
@auth
<script>
// 비로그인 저장 장소 → 로그인 계정으로 이관 (카테고리 배정 단계 포함)
(function() {
    const KEY = 'pinpick_guest_places';
    let list;
    try { list = JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) { return; }
    if (!Array.isArray(list) || !list.length) return;

    const userCats = @json($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'icon' => $c->icon])->values());
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const groups = {};
    list.forEach(p => {
        const key = p.category_label || '__none__';
        if (!groups[key]) groups[key] = { label: p.category_label || null, places: [] };
        groups[key].places.push(p);
    });
    const groupKeys = Object.keys(groups);

    function findMatchCat(name) {
        if (!name) return null;
        const lower = name.toLowerCase();
        return userCats.find(c => c.name.toLowerCase() === lower) || null;
    }

    function escHtml(s) { return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    // Step 1: 확인 모달
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10000;display:flex;align-items:flex-end;justify-content:center;padding:0';
    overlay.innerHTML = `
        <div id="ppSyncPanel" style="background:#fff;border-radius:18px 18px 0 0;padding:24px 22px calc(20px + env(safe-area-inset-bottom,0px));max-width:480px;width:100%;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 -4px 30px rgba(0,0,0,.15)">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                <h3 style="font-size:16px;font-weight:700;color:#2b211e;margin:0">비회원 장소 동기화</h3>
                <button id="ppSyncClose" style="border:none;background:none;font-size:20px;color:#6b5e52;cursor:pointer;padding:4px">✕</button>
            </div>
            <div id="ppSyncStep1">
                <div style="font-size:13.5px;color:#6b5e52;line-height:1.5;margin-bottom:20px">비회원으로 등록된 장소 <b>${list.length}개</b>를 내 계정으로 가져올까요?</div>
                <div style="display:flex;gap:8px">
                    <button id="ppGuestSkip" style="flex:1;padding:12px 0;border:1px solid #e0d6cc;border-radius:10px;background:#fff;color:#6b5e52;font-size:14px;font-weight:600;cursor:pointer">안할래요</button>
                    <button id="ppGuestNext" style="flex:1;padding:12px 0;border:none;border-radius:10px;background:#2b211e;color:#fff;font-size:14px;font-weight:600;cursor:pointer">동기화</button>
                </div>
            </div>
            <div id="ppSyncStep2" style="display:none;flex:1;overflow-y:auto">
                <div style="font-size:13px;color:#6b5e52;margin-bottom:14px">카테고리를 선택해주세요</div>
                <div id="ppSyncGroups"></div>
                <div style="margin-top:16px">
                    <button id="ppSyncImport" style="width:100%;padding:13px 0;border:none;border-radius:10px;background:#2b211e;color:#fff;font-size:14px;font-weight:600;cursor:pointer">가져오기</button>
                </div>
            </div>
        </div>`;
    document.body.appendChild(overlay);

    document.getElementById('ppSyncClose').addEventListener('click', () => {
        localStorage.removeItem(KEY);
        overlay.remove();
    });
    document.getElementById('ppGuestSkip').addEventListener('click', () => {
        localStorage.removeItem(KEY);
        overlay.remove();
    });

    // Step 2: 카테고리 배정 단계
    document.getElementById('ppGuestNext').addEventListener('click', () => {
        document.getElementById('ppSyncStep1').style.display = 'none';
        document.getElementById('ppSyncStep2').style.display = '';
        renderGroups();
    });

    function renderGroups() {
        const container = document.getElementById('ppSyncGroups');
        container.innerHTML = '';
        groupKeys.forEach((key, gi) => {
            const g = groups[key];
            const isLabeled = !!g.label;
            const match = isLabeled ? findMatchCat(g.label) : null;
            const title = isLabeled ? '📁 ' + escHtml(g.label) : '📌 직접 저장한 장소';

            let optionsHtml = '<option value="__new__">새 카테고리로 만들기</option>';
            userCats.forEach(c => {
                optionsHtml += '<option value="existing_' + c.id + '">' + escHtml((c.icon || '📌') + ' ' + c.name) + '</option>';
            });

            const defaultInputVal = isLabeled ? escHtml(g.label.slice(0, 30)) : '';

            const div = document.createElement('div');
            div.style.cssText = 'margin-bottom:14px;padding:14px;border:1px solid #e0d6cc;border-radius:12px';
            div.innerHTML = `
                <div style="font-size:14px;font-weight:600;color:#2b211e;margin-bottom:4px">${title} <span style="color:#6b5e52;font-weight:400">(${g.places.length}개)</span></div>
                <select data-group="${gi}" style="width:100%;padding:9px 10px;border:1px solid #e0d6cc;border-radius:8px;font-size:13.5px;color:#2b211e;background:#fff;margin-top:8px">${optionsHtml}</select>
                <div data-input-wrap="${gi}" style="margin-top:8px">
                    <input data-cat-input="${gi}" type="text" maxlength="30" value="${defaultInputVal}" placeholder="카테고리 이름" style="width:100%;padding:9px 10px;border:1px solid #e0d6cc;border-radius:8px;font-size:13.5px;color:#2b211e;background:#fff;box-sizing:border-box;outline:none">
                    <div data-cat-hint="${gi}" style="font-size:12px;margin-top:4px;display:none"></div>
                </div>
            `;
            container.appendChild(div);

            const sel = div.querySelector(`select[data-group="${gi}"]`);
            const inputWrap = div.querySelector(`[data-input-wrap="${gi}"]`);
            const input = div.querySelector(`[data-cat-input="${gi}"]`);
            const hint = div.querySelector(`[data-cat-hint="${gi}"]`);

            sel.value = '__new__';
            inputWrap.style.display = '';

            function checkHint() {
                const val = input.value.trim();
                const m = findMatchCat(val);
                if (m) {
                    hint.textContent = "기존 '" + m.name + "' 카테고리에 추가돼요";
                    hint.style.cssText = 'font-size:12px;margin-top:4px;color:var(--pp-primary,#2b211e)';
                } else if (!val && sel.value === '__new__') {
                    hint.textContent = '';
                    hint.style.display = 'none';
                } else {
                    hint.textContent = '';
                    hint.style.display = 'none';
                }
            }

            sel.addEventListener('change', () => {
                if (sel.value === '__new__') {
                    inputWrap.style.display = '';
                    input.focus();
                    checkHint();
                } else {
                    inputWrap.style.display = 'none';
                    hint.style.display = 'none';
                }
            });
            input.addEventListener('input', checkHint);
            input.addEventListener('focus', () => { input.style.borderColor = 'var(--pp-primary,#2b211e)'; });
            input.addEventListener('blur', () => { input.style.borderColor = '#e0d6cc'; });
            checkHint();
        });
    }

    // 가져오기
    document.getElementById('ppSyncImport').addEventListener('click', async () => {
        const btn = document.getElementById('ppSyncImport');

        let hasError = false;
        groupKeys.forEach((key, gi) => {
            const sel = document.querySelector(`select[data-group="${gi}"]`);
            const input = document.querySelector(`[data-cat-input="${gi}"]`);
            const hint = document.querySelector(`[data-cat-hint="${gi}"]`);
            if (sel.value === '__new__' && !input.value.trim()) {
                hint.textContent = '카테고리 이름을 입력해주세요';
                hint.style.cssText = 'font-size:12px;margin-top:4px;color:#e74c3c';
                input.style.borderColor = '#e74c3c';
                input.focus();
                hasError = true;
            }
        });
        if (hasError) return;

        btn.textContent = '가져오는 중…';
        btn.disabled = true;

        const assignments = [];
        groupKeys.forEach((key, gi) => {
            const sel = document.querySelector(`select[data-group="${gi}"]`);
            const input = document.querySelector(`[data-cat-input="${gi}"]`);
            const g = groups[key];
            g.places.forEach(p => {
                const item = { ...p };
                if (sel.value === '__new__') {
                    const inputName = input.value.trim().slice(0, 30);
                    const dup = findMatchCat(inputName);
                    if (dup) {
                        item._category_id = dup.id;
                        item._new_category = null;
                    } else {
                        item._category_id = null;
                        item._new_category = inputName;
                    }
                } else if (sel.value.startsWith('existing_')) {
                    item._category_id = parseInt(sel.value.replace('existing_', ''));
                    item._new_category = null;
                }
                assignments.push(item);
            });
        });

        try {
            const res = await fetch('/api/places/import-guest', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ places: assignments })
            });
            const j = await res.json();
            if (j && j.ok) {
                localStorage.removeItem(KEY);
                overlay.remove();
                const toast = document.createElement('div');
                toast.textContent = `장소 ${j.imported || list.length}개를 내 지도로 옮겼어요`;
                toast.style.cssText = 'position:fixed;left:50%;bottom:90px;transform:translateX(-50%);background:#2b211e;color:#fff;padding:11px 18px;border-radius:999px;font-size:13.5px;font-weight:600;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.22)';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 2400);
                setTimeout(() => location.reload(), 600);
            }
        } catch (e) {
            btn.textContent = '다시 시도';
            btn.disabled = false;
        }
    });
})();
</script>

{{-- 공유 기능 JS --}}
<script>
(function() {
    const shareBtn = document.getElementById('ppShareBtn');
    const shareSheet = document.getElementById('ppShareSheet');
    if (!shareBtn || !shareSheet) return;

    const titleInput = document.getElementById('ppShareTitle');
    const modeHint = document.getElementById('ppShareModeHint');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let currentCatId = null;

    document.querySelectorAll('input[name="shareMode"]').forEach(r => {
        r.addEventListener('change', () => { modeHint.hidden = r.value !== 'custom' || !r.checked; });
    });

    shareBtn.addEventListener('click', () => {
        const activeTab = document.querySelector('#ppHeroTabs .pp-hero2__tab.is-active');
        if (!activeTab || activeTab.dataset.cat === 'all') return;
        currentCatId = activeTab.dataset.cat;
        window.__sharePendingPlaceIds = null;
        titleInput.value = activeTab.textContent.trim();
        shareSheet.classList.add('is-open');
    });

    shareSheet.querySelectorAll('[data-close-share]').forEach(el => {
        el.addEventListener('click', () => { shareSheet.classList.remove('is-open'); window.__sharePendingPlaceIds = null; });
    });

    async function createShare() {
        const mode = document.querySelector('input[name="shareMode"]:checked')?.value || 'original';
        const body = { title: titleInput.value.trim() || '공유', name_display_mode: mode };
        if (window.__sharePendingPlaceIds) {
            body.place_ids = window.__sharePendingPlaceIds;
        } else {
            const catId = parseInt(currentCatId);
            if (!catId) { showToast('카테고리를 선택해주세요'); return null; }
            body.category_id = catId;
        }

        try {
            const res = await fetch('/api/share', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                showToast(err.message || '공유 생성에 실패했어요');
                return null;
            }
            return await res.json();
        } catch (e) {
            showToast('네트워크 오류가 발생했어요');
            return null;
        }
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text).catch(() => fallbackCopy(text));
        }
        return fallbackCopy(text);
    }

    function fallbackCopy(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;left:-9999px;top:-9999px';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
    }

    document.getElementById('ppShareCopyLink').addEventListener('click', async () => {
        try {
            const data = await createShare();
            if (!data) return;
            shareSheet.classList.remove('is-open');
            window.__sharePendingPlaceIds = null;
            await copyToClipboard(data.url);
            showToast('링크가 복사됐어요');
        } catch (e) {
            showToast('오류가 발생했어요');
        }
    });

    document.getElementById('ppShareKakao').addEventListener('click', async () => {
        try {
            const data = await createShare();
            if (!data) return;
            shareSheet.classList.remove('is-open');
            window.__sharePendingPlaceIds = null;

            if (typeof Kakao === 'undefined') {
                const s = document.createElement('script');
                s.src = 'https://t1.kakaocdn.net/kakao_js_sdk/2.7.4/kakao.min.js';
                s.onload = () => { sendKakaoShare(data); };
                s.onerror = () => { showToast('카카오 SDK를 불러오지 못했어요'); };
                document.head.appendChild(s);
            } else {
                sendKakaoShare(data);
            }
        } catch (e) {
            showToast('오류가 발생했어요');
        }
    });

    function sendKakaoShare(data) {
        if (!Kakao.isInitialized()) Kakao.init('{{ config("services.kakao.js_key") }}');
        Kakao.Share.sendDefault({
            objectType: 'feed',
            content: {
                title: data.title,
                description: '장소 ' + data.place_count + '개 · 나만의 장소, 나만의 지도 핀픽',
                imageUrl: data.thumbnail_url || '{{ asset("images/og-image.png") }}',
                link: { mobileWebUrl: data.url, webUrl: data.url },
            },
            buttons: [{ title: '장소 확인하기', link: { mobileWebUrl: data.url, webUrl: data.url } }],
        });
    }

    function showToast(msg) {
        let t = document.getElementById('ppToast');
        if (!t) { t = document.createElement('div'); t.id = 'ppToast'; t.className = 'pp-toast'; document.body.appendChild(t); }
        t.textContent = msg;
        t.classList.add('is-show');
        setTimeout(() => t.classList.remove('is-show'), 2500);
    }
})();
</script>
@endauth
<script>
(function() {
    // personal-only-v1: 항상 mine 모드. setPane / 세그먼트 탭 비활성화.
    (function applyMineMode() {
        const tip = document.getElementById('ppFabTip');
        if (tip) tip.classList.add('is-visible');
        const hero = document.querySelector('.pp-hero2');
        if (hero) {
            hero.classList.add('pp-hero2--compact');
            hero.classList.add('pp-hero2--mine');
        }
        const content = document.querySelector('.yg-content');
        if (content) content.classList.add('is-mine');
        const title = document.getElementById('ppHeroTitle');
        if (title && title.dataset.mine) title.textContent = title.dataset.mine;
    })();

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
                if (currentSort === 'custom') {
                    const ao = +a.dataset.order || 0, bo = +b.dataset.order || 0;
                    if (ao !== bo) return ao - bo;
                    const ac = +a.dataset.created || 0, bc = +b.dataset.created || 0;
                    return bc - ac;
                }
                const av = +a.dataset.created || 0, bv = +b.dataset.created || 0;
                return currentSort === 'created_asc' ? av - bv : bv - av;
            });
            items.forEach(el => mineGrid.appendChild(el));
            if (mineEmptyEl) mineGrid.appendChild(mineEmptyEl);
            mineGrid.classList.toggle('is-reorder', currentSort === 'custom');
        }

        // ── 내 순서 화살표: DOM swap + debounce로 전체 순서 저장 ──
        let _reorderTimer = null;
        function persistMineOrder() {
            const ids = Array.from(mineGrid.querySelectorAll('.pp-mine-grid__item'))
                .map(el => +el.dataset.id).filter(Number.isFinite);
            if (!ids.length) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            fetch('{{ route('api.places.reorder-all') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ order: ids })
            }).catch(() => {});
            // 로컬 dataset.order 동기화 (다음 렌더 시 일관성)
            ids.forEach((id, i) => {
                const el = mineGrid.querySelector(`.pp-mine-grid__item[data-id="${id}"]`);
                if (el) el.dataset.order = String(i);
            });
        }
        if (mineGrid) {
            mineGrid.addEventListener('click', (e) => {
                const arrow = e.target.closest('.pp-mine-grid__arrow');
                if (!arrow) return;
                e.preventDefault();
                e.stopPropagation();
                if (currentSort !== 'custom') return;
                const card = arrow.closest('.pp-mine-grid__item');
                if (!card) return;
                const visible = Array.from(mineGrid.querySelectorAll('.pp-mine-grid__item'))
                    .filter(el => el.style.display !== 'none');
                const idx = visible.indexOf(card);
                const dir = arrow.dataset.dir === 'up' ? -1 : 1;
                const swapIdx = idx + dir;
                if (idx < 0 || swapIdx < 0 || swapIdx >= visible.length) return;
                if (dir === -1) mineGrid.insertBefore(card, visible[swapIdx]);
                else mineGrid.insertBefore(visible[swapIdx], card);
                card.classList.add('pp-mine-grid__item--moved');
                setTimeout(() => card.classList.remove('pp-mine-grid__item--moved'), 500);
                if (_reorderTimer) clearTimeout(_reorderTimer);
                _reorderTimer = setTimeout(persistMineOrder, 500);
            });
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
            // FAB 장소추가 링크에 현재 카테고리 전달
            const fabLink = document.querySelector('.pp-nav__fab-inner');
            if (fabLink) {
                if (!fabLink.dataset.createBase) {
                    fabLink.dataset.createBase = fabLink.getAttribute('href').split('?')[0];
                }
                const base = fabLink.dataset.createBase;
                fabLink.href = (cat && cat !== 'all') ? base + '?category=' + encodeURIComponent(cat) : base;
            }
            const shareBtn = document.getElementById('ppShareBtn');
            if (shareBtn) shareBtn.hidden = (cat === 'all');
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
        const sortLabels = { 'created_desc':'최근 등록순', 'created_asc':'오래된 순', 'name':'이름순', 'custom':'내 순서' };
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

    // personal-only-v1: 세그먼트 탭 listener 비활성화 (탭 자체가 DOM에 없음)
})();

// ── 카테고리 관리 (순서 + 이름편집 + 추가 + 삭제) ──
(function() {
    const csrf = '{{ csrf_token() }}';
    const isGuest = {{ auth()->check() ? 'false' : 'true' }};

    function escHtml(s) { return String(s||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

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

    function updateCatCount() {
        const n = document.querySelectorAll('.yg-mycat-meta .yg-mycat').length;
        const el = document.getElementById('ppHeroCatCount');
        if (el) el.textContent = `카테고리 ${n}`;
    }

    if (isGuest) return;
    const orderPanel = document.getElementById('catOrderPanel');
    const orderList = document.getElementById('catOrderList');
    if (!orderPanel) return;

    // 상단 버튼으로 열기
    document.getElementById('catOrderEditBtnHero')?.addEventListener('click', openCatPanel);

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
                    if (!j.ok) { ppToast(j.error || '이름 변경 실패: ' + origName, true); }
                } catch (e) { ppToast('네트워크 오류', true); }
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
            if (!j.ok) { ppToast('순서 저장 실패', true); return; }
        } catch (e) { ppToast('네트워크 오류', true); return; }

        // 3) DOM 반영: 카테고리명 + 순서 재배치 + 히어로 탭 동기화
        const pane = document.querySelector('[data-pane="mine"]');
        const sections = Array.from(pane.querySelectorAll('.yg-mycat'));
        const heroTabs = document.getElementById('ppHeroTabs');
        items.forEach(li => {
            const id = +li.dataset.id;
            const newName = li.querySelector('.yg-catorder-item__input').value.trim();
            const sec = sections.find(s => +s.dataset.catId === id);
            if (sec && newName) {
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
            if (sec) pane.appendChild(sec);
            // 히어로 탭 버튼 이름 + 순서 반영
            if (heroTabs && newName) {
                const tabBtn = heroTabs.querySelector(`.pp-hero2__tab[data-cat="${id}"]`);
                if (tabBtn) {
                    tabBtn.textContent = newName;
                    heroTabs.appendChild(tabBtn);
                }
            }
        });
        orderPanel.hidden = true;
        ppToast('카테고리가 저장되었어요');
    });

    // 카테고리 추가
    document.getElementById('catOrderAdd').addEventListener('click', async () => {
        const name = prompt('새 카테고리 이름을 입력하세요');
        if (!name || !name.trim()) return;
        const trimmed = name.trim();
        try {
            const r = await fetch('/api/categories', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ name: trimmed })
            });
            const j = await r.json();
            if (!j.ok) { ppToast('추가 실패', true); return; }

            const newId = j.item.id;

            // 1) hidden meta 섹션 추가 — 맨 위로 (카테고리 관리 리스트의 데이터 소스)
            const metaWrap = document.querySelector('.yg-mycat-meta');
            if (metaWrap) {
                const sec = document.createElement('section');
                sec.className = 'yg-mycat';
                sec.dataset.catId = newId;
                sec.dataset.sort = '0';
                sec.innerHTML = `<span class="yg-mycat__catname">${escHtml(trimmed)}</span>`;
                metaWrap.prepend(sec);
            }

            // 2) 히어로 탭 버튼 추가 — '전체' 탭 바로 다음(가장 앞)
            const heroTabs = document.getElementById('ppHeroTabs');
            if (heroTabs) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'pp-hero2__tab';
                btn.dataset.cat = newId;
                btn.textContent = trimmed;
                const allBtn = heroTabs.querySelector('.pp-hero2__tab[data-cat="all"]');
                if (allBtn && allBtn.nextSibling) heroTabs.insertBefore(btn, allBtn.nextSibling);
                else heroTabs.appendChild(btn);
            }

            // 3) 카테고리 관리 리스트에도 즉시 반영
            renderCatOrderList();

            // 4) 상단 카운트 갱신
            updateCatCount();

            ppToast('새 카테고리가 추가되었어요');
        } catch (e) { ppToast('네트워크 오류', true); }
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
        // 모바일 터치 잔상 방지 — 눌린 버튼의 포커스 해제
        if (document.activeElement) document.activeElement.blur();
        if (dir === -1) orderList.insertBefore(li, items[swapIdx]);
        else orderList.insertBefore(items[swapIdx], li);
        refreshUpDown();
        // 이동된 항목에 잠시 하이라이트
        li.classList.add('yg-catorder-item--moved');
        setTimeout(() => li.classList.remove('yg-catorder-item--moved'), 600);
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
            if (!j.ok) { ppToast(j.error || '삭제 실패', true); return; }
            li.remove();
            refreshUpDown();
            // DOM에서도 해당 카테고리 섹션 + 히어로 탭 + 내장소 섹션 모두 제거
            document.querySelectorAll(`.yg-mycat[data-cat-id="${id}"]`).forEach(s => s.remove());
            const tabBtn = document.querySelector(`#ppHeroTabs .pp-hero2__tab[data-cat="${id}"]`);
            if (tabBtn) {
                // 삭제된 탭이 활성 상태면 "전체"로 되돌림
                if (tabBtn.classList.contains('is-active')) {
                    const allBtn = document.querySelector('#ppHeroTabs .pp-hero2__tab[data-cat="all"]');
                    if (allBtn) allBtn.click();
                }
                tabBtn.remove();
            }
            updateCatCount();
            ppToast('카테고리가 삭제되었어요');
        } catch (e) { ppToast('네트워크 오류', true); }
    }
})();

// ── 다중 선택 모드 ──
(function() {
    const grid = document.getElementById('ppMineGrid');
    const enterBtn = document.getElementById('ppSelEnter');
    const selBar = document.getElementById('ppSelBar');
    const selActions = document.getElementById('ppSelActions');
    const selCount = document.getElementById('ppSelCount');
    const selAllBtn = document.getElementById('ppSelAll');
    const selClose = document.getElementById('ppSelClose');
    const moveBtn = document.getElementById('ppSelMove');
    const deleteBtn = document.getElementById('ppSelDelete');
    const selShareBtn2 = document.getElementById('ppSelShareBtn2');
    const selEnterLabel = document.getElementById('ppSelEnterLabel');
    const moveSheet = document.getElementById('ppSelMoveSheet');
    const moveList = document.getElementById('ppSelMoveList');
    if (!grid || !enterBtn) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let selecting = false;
    const selected = new Set();
    let longPressTimer = null;
    let longPressTarget = null;
    let scrolledDuringPress = false;

    function getVisibleItems() {
        return Array.from(grid.querySelectorAll('.pp-mine-grid__item')).filter(el => el.style.display !== 'none');
    }

    function enterSelMode(firstItem) {
        selecting = true;
        grid.classList.add('is-selecting');
        selBar.style.display = '';
        selActions.style.display = '';
        document.querySelector('.pp-mine-sechead__actions')?.style.setProperty('display', 'none');
        document.querySelector('.pp-nav')?.classList.add('pp-nav--hidden');
        if (firstItem) toggleItem(firstItem);
    }

    function exitSelMode() {
        selecting = false;
        selected.clear();
        grid.classList.remove('is-selecting');
        grid.querySelectorAll('.pp-mine-grid__item.is-checked').forEach(el => el.classList.remove('is-checked'));
        selBar.style.display = 'none';
        selActions.style.display = 'none';
        document.querySelector('.pp-mine-sechead__actions')?.style.removeProperty('display');
        document.querySelector('.pp-nav')?.classList.remove('pp-nav--hidden');
        closeMoveSheet();
    }

    function toggleItem(el) {
        const id = el.dataset.id;
        if (selected.has(id)) { selected.delete(id); el.classList.remove('is-checked'); }
        else { selected.add(id); el.classList.add('is-checked'); }
        updateCount();
    }

    function updateCount() {
        const n = selected.size;
        selCount.textContent = n + '개 선택됨';
        moveBtn.disabled = n === 0;
        deleteBtn.disabled = n === 0;
        if (selShareBtn2) selShareBtn2.disabled = n === 0;
        const vis = getVisibleItems();
        selAllBtn.textContent = (n > 0 && n >= vis.length) ? '선택해제' : '전체선택';
    }

    enterBtn.addEventListener('click', () => enterSelMode(null));
    selClose.addEventListener('click', exitSelMode);

    selAllBtn.addEventListener('click', () => {
        const vis = getVisibleItems();
        if (selected.size >= vis.length) {
            selected.clear();
            grid.querySelectorAll('.pp-mine-grid__item.is-checked').forEach(el => el.classList.remove('is-checked'));
        } else {
            vis.forEach(el => { selected.add(el.dataset.id); el.classList.add('is-checked'); });
        }
        updateCount();
    });

    // 롱프레스 감지 (500ms)
    grid.addEventListener('touchstart', (e) => {
        const item = e.target.closest('.pp-mine-grid__item');
        if (!item || selecting) return;
        scrolledDuringPress = false;
        longPressTarget = item;
        longPressTimer = setTimeout(() => {
            if (!scrolledDuringPress) {
                e.preventDefault();
                enterSelMode(item);
            }
            longPressTimer = null;
            longPressTarget = null;
        }, 500);
    }, { passive: false });

    grid.addEventListener('touchmove', () => {
        scrolledDuringPress = true;
        if (longPressTimer) { clearTimeout(longPressTimer); longPressTimer = null; longPressTarget = null; }
    });

    grid.addEventListener('touchend', () => {
        if (longPressTimer) { clearTimeout(longPressTimer); longPressTimer = null; longPressTarget = null; }
    });

    // 선택 모드에서 카드 탭 → 링크 이동 대신 토글
    grid.addEventListener('click', (e) => {
        if (!selecting) return;
        const item = e.target.closest('.pp-mine-grid__item');
        if (!item) return;
        if (e.target.closest('.pp-mine-grid__arrow')) return;
        e.preventDefault();
        e.stopPropagation();
        toggleItem(item);
    }, true);

    // 삭제
    deleteBtn.addEventListener('click', async () => {
        if (!selected.size) return;
        if (!confirm(selected.size + '개 장소를 삭제할까요? 이 작업은 되돌릴 수 없어요.')) return;
        deleteBtn.textContent = '삭제 중…';
        deleteBtn.disabled = true;
        try {
            const res = await fetch('/api/places/bulk-delete', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ ids: Array.from(selected).map(Number) })
            });
            const j = await res.json();
            if (j.ok) {
                selected.forEach(id => {
                    const el = grid.querySelector(`.pp-mine-grid__item[data-id="${id}"]`);
                    if (el) el.remove();
                });
                ppToast(j.deleted + '개 장소가 삭제됐어요');
                exitSelMode();
                updateSavedCount();
            } else {
                ppToast(j.error || '삭제 실패', true);
            }
        } catch (e) {
            ppToast('네트워크 오류', true);
        }
        deleteBtn.textContent = '삭제';
        deleteBtn.disabled = false;
    });

    // 선택 모드 공유
    if (selShareBtn2) {
        selShareBtn2.addEventListener('click', () => {
            if (!selected.size) return;
            const shareSheet = document.getElementById('ppShareSheet');
            const titleInput = document.getElementById('ppShareTitle');
            if (!shareSheet) return;
            window.__sharePendingPlaceIds = Array.from(selected).map(Number);
            if (titleInput) titleInput.value = selected.size + '개 장소';
            shareSheet.classList.add('is-open');
        });
    }

    // 카테고리 이동
    moveBtn.addEventListener('click', () => {
        if (!selected.size) return;
        renderMoveList();
        moveSheet.classList.add('is-open');
    });

    function closeMoveSheet() { moveSheet.classList.remove('is-open'); }
    moveSheet.querySelectorAll('[data-role="close-move"]').forEach(el => {
        el.addEventListener('click', closeMoveSheet);
    });

    function renderMoveList() {
        const cats = [];
        document.querySelectorAll('.yg-mycat-meta .yg-mycat').forEach(sec => {
            cats.push({ id: sec.dataset.catId, name: sec.querySelector('.yg-mycat__catname')?.textContent || '' });
        });
        moveList.innerHTML = '';
        cats.forEach(c => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pp-sel-move-sheet__opt';
            btn.textContent = c.name;
            btn.addEventListener('click', () => doMove(c.id, c.name));
            moveList.appendChild(btn);
        });
    }

    async function doMove(catId, catName) {
        closeMoveSheet();
        moveBtn.textContent = '이동 중…';
        moveBtn.disabled = true;
        try {
            const res = await fetch('/api/places/bulk-move', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ ids: Array.from(selected).map(Number), category_id: +catId })
            });
            const j = await res.json();
            if (j.ok) {
                selected.forEach(id => {
                    const el = grid.querySelector(`.pp-mine-grid__item[data-id="${id}"]`);
                    if (el) {
                        el.dataset.cat = String(catId);
                        const catSpan = el.querySelector('.pp-mine-grid__cat');
                        if (catSpan) catSpan.textContent = catName;
                    }
                });
                ppToast(j.moved + '개 장소가 이동됐어요');
                exitSelMode();
            } else {
                ppToast(j.error || '이동 실패', true);
            }
        } catch (e) {
            ppToast('네트워크 오류', true);
        }
        moveBtn.textContent = '카테고리 이동';
        moveBtn.disabled = false;
    }

    function updateSavedCount() {
        const stat = document.querySelector('.pp-hero2__stat');
        if (stat) {
            const n = grid.querySelectorAll('.pp-mine-grid__item').length;
            stat.textContent = '저장 ' + n;
        }
    }

    function ppToast(msg, isError) {
        const el = document.createElement('div');
        el.className = 'pp-flash' + (isError ? ' pp-flash--error' : '');
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(() => { el.style.transition = 'opacity .4s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 450); }, 2000);
    }
})();

// 게스트 localStorage 장소 hydrate → 통합 그리드에 렌더
@guest
(function() {
    // 일회성 게스트 데이터 초기화 (phone/opening_hours 추가)
    if (!localStorage.getItem('_pp_reset_0710a')) {
        localStorage.removeItem('pinpick_guest_places');
        localStorage.setItem('_pp_reset_0710a', '1');
    }
    const grid = document.getElementById('ppMineGrid');
    if (!grid) return;
    const list = JSON.parse(localStorage.getItem('pinpick_guest_places') || '[]');
    function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
    // 1~5개 등록 시 로그인 유도 CTA 노출, 기본 타이틀 숨김
    const heroTitle = document.getElementById('ppHeroTitle');
    const loginCta = document.getElementById('ppGuestLoginCta');
    if (list.length >= 1 && list.length <= 5) {
        if (heroTitle) heroTitle.hidden = true;
        if (loginCta) loginCta.hidden = false;
    }
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
        const dateHtml = (p.status === 'visited' && p.visited_at)
            ? `<span class="pp-mine-grid__date">${escapeHtml(String(p.visited_at).slice(0,10).replaceAll('-','.'))}</span>` : '';
        const hasLatLng = Number.isFinite(+p.lat) && Number.isFinite(+p.lng);
        let thumbImg = '';
        if (p.thumbnail_url) {
            thumbImg = `<img class="pp-mine-grid__thumb-img" alt="${escapeHtml(p.name)}" loading="lazy" src="${escapeHtml(p.thumbnail_url)}" onerror="this.remove()">`;
        } else if (hasLatLng) {
            thumbImg = `<img class="pp-mine-grid__thumb-img" alt="${escapeHtml(p.name)} 위치 지도" loading="lazy" src="/api/static-map?lat=${encodeURIComponent(p.lat)}&lng=${encodeURIComponent(p.lng)}&overseas=${p.is_overseas ? 1 : 0}&w=320&h=320" onerror="this.remove()">`;
        }
        const card = document.createElement('a');
        card.className = 'pp-mine-grid__item';
        card.href = '/guest/places/' + encodeURIComponent(p.id);
        card.dataset.cat = p.category_id;
        card.innerHTML = `
            <div class="pp-mine-grid__thumb">
                ${thumbImg}
                <span class="pp-mine-grid__ph">📌</span>
                <span class="pp-mine-grid__badge">${badge}</span>
                <span class="pp-mine-grid__region ${p.is_overseas ? 'is-overseas' : ''}">${p.is_overseas ? '해외' : '국내'}</span>
                ${dateHtml}
            </div>
            <div class="pp-mine-grid__body">
                <div class="pp-mine-grid__name">${escapeHtml(p.name)}</div>
                <div class="pp-mine-grid__meta">${escapeHtml(p.category_label || p.category_name || '')}${addr ? ' · ' + escapeHtml(addr) : ''}</div>
            </div>
        `;
        grid.appendChild(card);
    });
})();
@endguest

// ── personal-only-v1: 사람들 탭 (트렌딩) IIFE 통째로 비활성화 (v1-with-people 태그에서 복원 가능) ──
@if(false)
(function() {
    const peoplePane = document.querySelector('.yg-pane[data-pane="people"]');
    if (!peoplePane) return;

    const DEFAULT_REGION = peoplePane.dataset.defaultRegion || '';
    const TRENDING_URL = @json(route('api.places.trending'));
    const PLACE_SHOW_URL = (id) => `/place/${id}`;
    const STORAGE_REGION = 'pp_trend_region';   // cached {name, ts}
    const REGION_TTL_MS = 1000 * 60 * 60 * 6;   // 6시간 캐시

    let initialized = false;
    let currentTheme = 'food';
    let resolvedRegion = null; // {name, source: 'geo'|'user'|'fallback'}

    function fmtNum(n) {
        return Number(n || 0).toLocaleString('ko-KR');
    }
    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function buildCard(it, opts) {
        opts = opts || {};
        const big = !!opts.big;
        const groupRegion = !!opts.groupRegion;
        const regionPrefix = opts.regionPrefix || '';
        const tag = opts.tag || '';

        const thumbInner = it.thumbnail
            ? `<div class="pp-trend-card__thumb" style="background-image:url('${escapeHtml(it.thumbnail)}')">`
            : `<div class="pp-trend-card__thumb pp-trend-card__thumb--ph"><span class="pp-trend-card__ph">핀픽</span>`;

        const badgeText = groupRegion
            ? `${fmtNum(it.region_count)} 저장`
            : (regionPrefix
                ? `${regionPrefix} ${fmtNum(it.week_count)} 저장`
                : `${fmtNum(it.week_count)} 저장`);
        const badge = `<span class="pp-trend-card__badge">${escapeHtml(badgeText)}</span>`;

        const theme = it.theme_name || it.category_name || '';
        const region = it.region || '';

        if (big) {
            return `<a class="pp-trend-card pp-trend-card--big" href="${PLACE_SHOW_URL(it.id)}">
                ${thumbInner}${badge}</div>
                <div class="pp-trend-card__body">
                    <span class="pp-trend-card__region">${escapeHtml(region)}</span>
                    <div class="pp-trend-card__name">${escapeHtml(it.name)}</div>
                    <div class="pp-trend-card__meta">${fmtNum(it.region_count || it.week_count)} 저장</div>
                </div>
            </a>`;
        }

        let metaHtml = '';
        if (theme || region) {
            const parts = [];
            if (theme) parts.push(`<span class="pp-trend-card__theme">${escapeHtml(theme)}</span>`);
            if (theme && region) parts.push(`<span class="pp-trend-card__meta-dot">·</span>`);
            if (region) parts.push(`<span class="pp-trend-card__region">${escapeHtml(region)}</span>`);
            metaHtml = `<div class="pp-trend-card__meta">${parts.join('')}</div>`;
        }
        const tagHtml = tag ? `<div class="pp-trend-card__tag">${escapeHtml(tag)}</div>` : '';

        return `<a class="pp-trend-card" href="${PLACE_SHOW_URL(it.id)}">
            ${thumbInner}${badge}</div>
            <div class="pp-trend-card__body">
                <div class="pp-trend-card__name">${escapeHtml(it.name)}</div>
                ${metaHtml}
                ${tagHtml}
            </div>
        </a>`;
    }

    function renderEmpty(listEl, message) {
        listEl.innerHTML = `<div class="pp-trend-empty">${escapeHtml(message)}</div>`;
    }

    function hideSection(section) {
        section.hidden = true;
    }

    async function fetchTrending(params) {
        const qs = new URLSearchParams(params);
        const res = await fetch(`${TRENDING_URL}?${qs.toString()}`, {
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error('network');
        const j = await res.json();
        return j.items || [];
    }

    function weeklyTagOf(it, idx) {
        if (idx < 3) return '이번주 급상승';
        if (it.theme_name) return `${it.theme_name} 테마 인기`;
        return '이번주 저장 많음';
    }

    async function loadWeekly() {
        const section = peoplePane.querySelector('[data-section="weekly"]');
        const listEl = section.querySelector('[data-list="weekly"]');
        try {
            const items = await fetchTrending({ period: '7days', limit: 10 });
            if (!items.length) { hideSection(section); return; }
            listEl.innerHTML = items.map((it, i) => buildCard(it, { tag: weeklyTagOf(it, i) })).join('');
        } catch (e) {
            hideSection(section);
        }
    }

    function regionTagOf(it, idx, regionName) {
        if (idx === 0 && regionName) return `${regionName} 저장 상위`;
        if (it.theme_name) return `${it.theme_name} 저장 많음`;
        if (regionName) return `${regionName} 인기`;
        return '저장 많음';
    }

    async function loadRegion(regionName) {
        const section = peoplePane.querySelector('[data-section="region"]');
        const listEl = section.querySelector('[data-list="region"]');
        const titleEl = section.querySelector('[data-title="region"]');
        const moreEl = section.querySelector('[data-more="region"]');

        const title = regionName
            ? `${regionName}에서 많이 저장한 장소`
            : '전국에서 많이 저장한 장소';
        titleEl.textContent = title;
        if (moreEl) {
            moreEl.href = regionName
                ? `/explore/trending?type=local&region=${encodeURIComponent(regionName)}`
                : '/explore/trending?type=local';
        }

        const params = { period: '30days', limit: 10 };
        if (regionName) params.region = regionName;

        try {
            const items = await fetchTrending(params);
            if (!items.length) {
                // 지역 데이터가 없으면 전국 fallback
                if (regionName) {
                    const fallback = await fetchTrending({ period: '30days', limit: 10 });
                    if (!fallback.length) { hideSection(section); return; }
                    titleEl.textContent = '전국에서 많이 저장한 장소';
                    listEl.innerHTML = fallback.map((it, i) => buildCard(it, { tag: regionTagOf(it, i, '') })).join('');
                    section.hidden = false;
                    return;
                }
                hideSection(section);
                return;
            }
            listEl.innerHTML = items.map((it, i) => buildCard(it, {
                tag: regionTagOf(it, i, regionName),
                regionPrefix: regionName || ''
            })).join('');
            section.hidden = false;
        } catch (e) {
            hideSection(section);
        }
    }

    const THEME_LABELS = {
        food: '맛집', cafe: '카페', travel: '여행',
        beauty: '뷰티/케어', stay: '숙소', culture: '문화/여가',
        medical: '병원/약국', shopping: '쇼핑', etc: '',
    };

    async function loadTheme(themeSlug) {
        const section = peoplePane.querySelector('[data-section="theme"]');
        const listEl = section.querySelector('[data-list="theme"]');
        const moreEl = section.querySelector('[data-more="theme"]');
        if (moreEl) moreEl.href = `/explore/trending?type=theme&theme=${encodeURIComponent(themeSlug)}`;

        // skeleton 복원
        const skelCard = '<div class="pp-trend-card pp-trend-card--skel">'
            + '<div class="pp-trend-card__thumb pp-skel"></div>'
            + '<div class="pp-trend-card__body">'
            + '<div class="pp-trend-card__name pp-skel pp-skel--line"></div>'
            + '<div class="pp-trend-card__meta pp-skel pp-skel--line pp-skel--line-sm"></div>'
            + '</div></div>';
        listEl.innerHTML = skelCard + skelCard + skelCard;

        const label = THEME_LABELS[themeSlug] || '';
        try {
            const items = await fetchTrending({ theme: themeSlug, period: '30days', limit: 10 });
            if (!items.length) {
                renderEmpty(listEl, '아직 해당 테마의 장소 데이터가 부족해요.');
                return;
            }
            listEl.innerHTML = items.map((it, i) => buildCard(it, {
                tag: i === 0 && label ? `${label} 테마 1위` : (label ? `${label} 저장 많음` : '저장 많음')
            })).join('');
        } catch (e) {
            renderEmpty(listEl, '데이터를 불러오지 못했어요.');
        }
    }

    async function loadTravelScope(scope) {
        const sectionKey = scope === 'overseas' ? 'travel-overseas' : 'travel-domestic';
        const section = peoplePane.querySelector(`[data-section="${sectionKey}"]`);
        if (!section) return;
        const listEl = section.querySelector(`[data-list="${sectionKey}"]`);

        try {
            const items = await fetchTrending({
                theme: 'travel',
                period: '7days',
                group_by: 'region',
                scope: scope,
                limit: 3,
            });
            if (!items.length) { hideSection(section); return; }
            listEl.innerHTML = items.map(it => buildCard(it, { big: true, groupRegion: true })).join('');
        } catch (e) {
            hideSection(section);
        }
    }

    // ── 지역 판단 (3단계 우선순위) ──
    function cachedRegion() {
        try {
            const raw = sessionStorage.getItem(STORAGE_REGION);
            if (!raw) return null;
            const obj = JSON.parse(raw);
            if (!obj || !obj.name) return null;
            if (Date.now() - (obj.ts || 0) > REGION_TTL_MS) return null;
            return obj.name;
        } catch (e) { return null; }
    }
    function saveRegionCache(name) {
        try {
            sessionStorage.setItem(STORAGE_REGION, JSON.stringify({ name, ts: Date.now() }));
        } catch (e) {}
    }

    async function reverseGeocodeRegion(lat, lng) {
        const params = new URLSearchParams({ lat, lng, provider: 'naver' });
        const res = await fetch(`/api/geocode/reverse?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('geocode');
        const j = await res.json();
        return j.region || '';
    }

    function requestGeolocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) return reject(new Error('no-geo'));
            navigator.geolocation.getCurrentPosition(
                pos => {
                    const coords = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                    try { localStorage.setItem('pp_last_geo', JSON.stringify({ lat: coords.lat, lng: coords.lng, ts: Date.now() })); } catch (e) {}
                    resolve(coords);
                },
                err => reject(err),
                { enableHighAccuracy: false, timeout: 5000, maximumAge: 1000 * 60 * 30 }
            );
        });
    }

    async function resolveRegion() {
        // 1순위: Geolocation
        const cached = cachedRegion();
        if (cached) return { name: cached, source: 'geo-cached' };

        try {
            const coords = await requestGeolocation();
            const region = await reverseGeocodeRegion(coords.lat, coords.lng);
            if (region) {
                saveRegionCache(region);
                return { name: region, source: 'geo' };
            }
        } catch (e) {
            // 위치 권한 거부 or 실패 → 2순위로
        }

        // 2순위: 로그인 사용자의 최근 저장 장소 지역
        if (DEFAULT_REGION) return { name: DEFAULT_REGION, source: 'user' };

        // 3순위: 전국
        return { name: '', source: 'fallback' };
    }

    // 테마 탭 클릭
    const themeTabs = document.getElementById('ppThemeTabs');
    if (themeTabs) {
        themeTabs.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-theme]');
            if (!btn) return;
            if (btn.dataset.theme === currentTheme) return;
            themeTabs.querySelectorAll('[data-theme]').forEach(b => b.classList.toggle('is-active', b === btn));
            currentTheme = btn.dataset.theme;
            loadTheme(currentTheme);
        });
    }

    async function initPeopleTab() {
        if (initialized) return;
        initialized = true;

        // 1섹션, 3섹션, 4·5섹션은 지역 무관 즉시 로드
        loadWeekly();
        loadTheme(currentTheme);
        loadTravelScope('domestic');
        loadTravelScope('overseas');

        // 2섹션: 지역 판단 후 로드
        const { name } = await resolveRegion();
        resolvedRegion = name;
        loadRegion(name);
    }

    // 페이지 로드 시 현재 사람들 탭이 활성화돼 있으면 즉시 초기화, 아니면 탭 클릭 시 초기화
    function maybeInit() {
        if (peoplePane.classList.contains('is-active')) initPeopleTab();
    }
    maybeInit();
    document.querySelectorAll('.yg-segtab__btn[data-pane="people"]').forEach(btn => {
        btn.addEventListener('click', () => setTimeout(maybeInit, 0));
    });
})();
@endif
</script>
@endpush
