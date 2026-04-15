@extends('layouts.app')
@section('title', $category->name)

@section('header')
<header class="pp-header">
    <a href="{{ url('/') }}" class="pp-header__icon" aria-label="뒤로">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </a>
    <div class="pp-header__title">{{ $category->name }} <span class="cat-show__count">({{ $totalCount }})</span></div>
</header>
@endsection

@section('content')
<div class="cat-show">
    {{-- 탭: 목록 / 지도 --}}
    <div class="cat-show__tabs">
        <a href="{{ route('categories.show', ['category' => $category, 'view' => 'list', 'status' => $status, 'sort' => $sort]) }}"
            class="cat-show__tab {{ $view === 'list' ? 'is-active' : '' }}">목록 보기</a>
        <a href="{{ route('categories.show', ['category' => $category, 'view' => 'map', 'status' => $status, 'sort' => $sort]) }}"
            class="cat-show__tab {{ $view === 'map' ? 'is-active' : '' }}">지도 보기</a>
    </div>

    {{-- 필터 --}}
    <div class="cat-show__filters">
        <div class="cat-show__chipset">
            @foreach(['all' => '전체', 'planned' => '방문예정', 'visited' => '방문완료'] as $k => $label)
                <a href="{{ route('categories.show', ['category' => $category, 'view' => $view, 'status' => $k, 'sort' => $sort]) }}"
                    class="cat-show__chip {{ $status === $k ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
        <select class="cat-show__sort" onchange="location.href=this.value">
            @foreach(['recent' => '최근 저장순', 'name' => '이름순', 'visit' => '최근 방문순'] as $k => $label)
                <option value="{{ route('categories.show', ['category' => $category, 'view' => $view, 'status' => $status, 'sort' => $k]) }}"
                    {{ $sort === $k ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    {{-- 본문 --}}
    @if($view === 'map')
        <div class="cat-show__map-wrap">
            <div id="catShowMap" class="cat-show__map"></div>
        </div>
        @if($places->isEmpty())
            <div class="cat-show__empty">조건에 맞는 장소가 없어요</div>
        @endif
    @else
        @if($places->isEmpty())
            <div class="cat-show__empty">
                <div class="cat-show__empty-emoji">📍</div>
                <p>조건에 맞는 장소가 없어요</p>
                <a href="{{ route('places.create', ['category' => $category->id]) }}" class="cat-show__empty-btn">장소 추가하기</a>
            </div>
        @else
            <div class="cat-show__grid">
                @foreach($places as $p)
                    @php $thumbUrl = $p->images->first() ? $p->images->first()->thumb_url : ($p->thumbnail ? asset('storage/' . $p->thumbnail) : null); @endphp
                    <a href="{{ route('places.show', $p) }}" class="cat-card">
                        <div class="cat-card__thumb"
                            @if($thumbUrl) style="background-image:url('{{ $thumbUrl }}')" @endif>
                            @if(!$thumbUrl)
                                <div class="cat-card__thumb-ph">📍</div>
                            @endif
                            <span class="cat-card__badge cat-card__badge--{{ $p->status }}">
                                {{ $p->status === 'visited' ? '방문완료' : '방문예정' }}
                            </span>
                        </div>
                        <div class="cat-card__body">
                            <div class="cat-card__name">{{ $p->name }}</div>
                            @if($p->road_address || $p->address)
                                <div class="cat-card__addr">{{ $p->road_address ?: $p->address }}</div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    @endif
</div>
@endsection

@if($view === 'map' && $naverClientId)
@push('head')
<script src="https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId={{ $naverClientId }}"></script>
@endpush

@push('scripts')
@php
    $mapPlaces = [];
    foreach ($places as $p) {
        if (!$p->lat || !$p->lng) continue;
        $mapPlaces[] = [
            'id' => $p->id,
            'name' => $p->name,
            'lat' => $p->lat,
            'lng' => $p->lng,
            'status' => $p->status,
            'url' => route('places.show', $p),
        ];
    }
@endphp
<script>
(function() {
    var places = {!! json_encode($mapPlaces, JSON_UNESCAPED_UNICODE) !!};
    console.log('[cat-map] IIFE start, places=', places);

    if (typeof naver === 'undefined') {
        console.error('[cat-map] naver undefined — maps.js 로드 실패');
        var el = document.getElementById('catShowMap');
        if (el) el.innerHTML = '<div style="padding:30px;text-align:center;color:#c00">네이버 지도 스크립트 로드 실패</div>';
        return;
    }
    console.log('[cat-map] naver OK, el=', document.getElementById('catShowMap'));

    var center = places.length
        ? new naver.maps.LatLng(+places[0].lat, +places[0].lng)
        : new naver.maps.LatLng(37.5665, 126.9780);
    var map = new naver.maps.Map('catShowMap', { center: center, zoom: 13 });
    console.log('[cat-map] Map created');

    if (!places.length) return;

    var bounds = new naver.maps.LatLngBounds();
    places.forEach(function(p) {
        var pos = new naver.maps.LatLng(+p.lat, +p.lng);
        var marker = new naver.maps.Marker({
            position: pos, map: map, title: p.name,
            icon: {
                content: '<div class="pp-mappin"><div class="pp-mappin__bubble">'
                    + '<span class="pp-mappin__pin"></span>'
                    + '<span class="pp-mappin__label">' + p.name.replace(/</g,'&lt;') + '</span>'
                    + '</div></div>',
                anchor: new naver.maps.Point(0, 0),
            },
        });
        naver.maps.Event.addListener(marker, 'click', function() { location.href = p.url; });
        bounds.extend(pos);
    });
    if (places.length > 1) map.fitBounds(bounds);
    else map.setZoom(15);
})();
</script>
@endpush
@endif
