@extends('layouts.app')
@section('title', '핀픽')
@section('app_class', 'pp-app--home')

@section('header')
<header class="yg-header">
    <div class="yg-header__brand">핀픽</div>
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
            <div class="yg-hero__pin">📍</div>
        </div>
        <div class="yg-catslide">
            @foreach($categories as $c)
                @php $latest = ($categoryLatest ?? collect())->get($c->id); @endphp
                @if($latest)
                    <a href="{{ route('map', ['category' => $c->id]) }}" class="yg-catcard2 yg-catcard2--filled">
                        <div class="yg-catcard2__thumb">{{ $c->icon }}</div>
                        <div class="yg-catcard2__body">
                            <div class="yg-catcard2__cat">{{ $c->name }}</div>
                            <div class="yg-catcard2__name">{{ Str::limit($latest->name, 12) }}</div>
                            <div class="yg-catcard2__meta">{{ Str::limit($latest->road_address ?: $latest->address ?: '주소 없음', 16) }}</div>
                        </div>
                    </a>
                @else
                    <a href="{{ route('places.create', ['category' => $c->id]) }}" class="yg-catcard2 yg-catcard2--empty">
                        <div class="yg-catcard2__plus">＋</div>
                        <div class="yg-catcard2__cat"><span class="yg-cat-emoji">{{ $c->icon }}</span> {{ $c->name }}</div>
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
                        <div class="yg-prod__thumb">
                            {{ $p['icon'] }}
                            <span class="yg-prod__rate">⭐ {{ number_format($p['saves']) }}</span>
                        </div>
                        <div class="yg-prod__name">{{ $p['name'] }}</div>
                        <div class="yg-prod__meta">{{ $p['category'] }} · {{ $p['area'] }}</div>
                        <div class="yg-prod__price">📌 {{ number_format($p['saves']) }} 저장</div>
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
                        <div class="yg-prod__thumb">{{ $p['icon'] }}</div>
                        <div class="yg-prod__name">{{ $p['name'] }}</div>
                        <div class="yg-prod__meta">{{ $p['category'] }} · {{ $p['area'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 내 장소 탭 --}}
    <div class="yg-pane" data-pane="mine">
        @auth
            @if($myPlaces->count())
                <div class="yg-mylist">
                    @foreach($myPlaces as $p)
                        <a href="{{ route('places.show', $p) }}" class="yg-myitem">
                            <div class="yg-myitem__thumb">{{ $p->category?->icon ?? '📍' }}</div>
                            <div class="yg-myitem__body">
                                <div class="yg-myitem__name">{{ $p->name }}</div>
                                <div class="yg-myitem__meta">{{ $p->category?->name ?? '기타' }}@if($p->road_address || $p->address) · {{ Str::limit($p->road_address ?: $p->address, 24) }}@endif</div>
                            </div>
                            <span class="yg-myitem__badge yg-myitem__badge--{{ $p->status }}">{{ $p->status === 'visited' ? '방문완료' : '방문예정' }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="yg-empty">
                    <div class="yg-empty__icon">📍</div>
                    <div class="yg-empty__title">아직 저장한 장소가 없어요</div>
                    <div class="yg-empty__desc">하단 + 버튼을 눌러 첫 장소를 추가해보세요</div>
                    <a href="{{ route('places.create') }}" class="yg-empty__btn">＋ 새 장소 저장</a>
                </div>
            @endif
        @else
            <div class="yg-empty">
                <div class="yg-empty__icon">🔒</div>
                <div class="yg-empty__title">로그인이 필요해요</div>
                <div class="yg-empty__desc">로그인하면 내가 저장한 장소를 모아볼 수 있어요</div>
                <a href="{{ route('login') }}" class="yg-empty__btn">로그인하기</a>
            </div>
        @endauth
    </div>

    {{-- 프로모 배너 --}}
    <div class="yg-promo">
        <div>
            <div class="yg-promo__title">나만의 지도를 시작해보세요</div>
            <div class="yg-promo__big">로그인 없이도<br>5개까지 저장 OK</div>
        </div>
        <div class="yg-promo__icon">📍</div>
    </div>

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
</script>
@endpush
