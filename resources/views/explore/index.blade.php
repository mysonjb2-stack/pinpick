@extends('layouts.app')
@section('title', '탐색')
@section('app_class', 'pp-app--home')

@section('header')
<header class="ph-header">
    <div class="ph-header__top">
        <div class="ph-brand">
            <small>직접 찾아보는</small>
            <div class="ph-brand__name">탐색</div>
        </div>
    </div>
    <div class="ph-search">
        <span>🔍</span>
        <input type="search" placeholder="장소, 지역, 카테고리로 검색">
    </div>
</header>
@endsection

@section('content')
<div class="ph-content">
    <div class="ph-mymap">
        <div class="ph-mymap__top">
            <div>
                <h2>탐색 기능 준비 중</h2>
                <p>지도에서 주변 인기 저장 장소, 지역·카테고리별 탐색, 필터·바로저장 기능이 곧 추가됩니다.</p>
            </div>
            <div class="ph-ghost-pin">🧭</div>
        </div>
    </div>

    <div class="ph-section">
        <div class="ph-section__title"><strong>카테고리로 둘러보기</strong></div>
        <div class="ph-grid2">
            <div class="ph-cur"><span class="ph-cur__emoji">🍽</span><strong>맛집</strong><p>지역별 인기 맛집</p></div>
            <div class="ph-cur"><span class="ph-cur__emoji">☕</span><strong>카페</strong><p>분위기 좋은 카페</p></div>
            <div class="ph-cur"><span class="ph-cur__emoji">🏨</span><strong>숙소</strong><p>출장·여행 숙소</p></div>
            <div class="ph-cur"><span class="ph-cur__emoji">✈️</span><strong>여행</strong><p>가볼 만한 곳</p></div>
        </div>
    </div>
</div>
@endsection
