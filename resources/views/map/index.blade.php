@extends('layouts.app')
@section('title', '지도')

@section('header')
<header class="pp-header">
    <div class="pp-header__title">내 지도</div>
    <div class="pp-header__spacer"></div>
</header>
@endsection

@section('content')
<div id="pp-map" class="pp-map"></div>
@endsection

@push('head')
@if($naverClientId)
<script src="https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId={{ $naverClientId }}"></script>
@endif
@endpush

@push('scripts')
<script>
(function() {
    const places = {!! json_encode($places->map(function($p){
        return [
            'id' => $p->id,
            'name' => $p->name,
            'lat' => (float) $p->lat,
            'lng' => (float) $p->lng,
            'icon' => optional($p->category)->icon ?? '📌',
            'category' => optional($p->category)->name ?? '기타',
        ];
    })->values()) !!};

    if (typeof naver === 'undefined') {
        document.getElementById('pp-map').innerHTML =
          '<div class="pp-empty"><div class="pp-empty__icon">🗺️</div><div class="pp-empty__title">지도 준비 중</div><div class="pp-empty__desc">NAVER_MAP_CLIENT_ID를 .env에 설정해주세요</div></div>';
        return;
    }

    const center = places.length
        ? new naver.maps.LatLng(places[0].lat, places[0].lng)
        : new naver.maps.LatLng(37.5665, 126.9780);

    const map = new naver.maps.Map('pp-map', {
        center: center,
        zoom: 13,
    });

    places.forEach(p => {
        new naver.maps.Marker({
            position: new naver.maps.LatLng(p.lat, p.lng),
            map: map,
            title: p.name,
        });
    });
})();
</script>
@endpush
