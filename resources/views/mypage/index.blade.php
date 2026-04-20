@extends('layouts.app')
@section('title', '마이페이지')

@section('header')
<header class="pp-header">
    <div class="pp-header__title">마이</div>
</header>
@endsection

@section('content')
@auth
    <div class="pp-profile">
        <div class="pp-profile__avatar">
            @if($user->profile_image)
                <img src="{{ $user->profile_image }}" alt="">
            @else
                😀
            @endif
        </div>
        <div>
            <div class="pp-profile__name">
                {{ $user->name }}
                @if($user->email)
                    <span class="pp-profile__email">({{ $user->email }})</span>
                @endif
            </div>
            <div class="pp-profile__sub">
                {{ ['kakao' => '카카오 로그인', 'google' => '구글 로그인', 'naver' => '네이버 로그인'][$user->provider] ?? '회원' }}
                · 저장 장소 {{ $placeCount }}개
            </div>
        </div>
    </div>

    <div class="pp-menu">
        <a href="#" class="pp-menu__item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>
            카테고리 관리
            <span class="pp-menu__arrow">›</span>
        </a>
        <a href="#" class="pp-menu__item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M7 12h10"/><path d="M10 18h4"/></svg>
            여행 루트 ({{ $trips->count() }})
            <span class="pp-menu__arrow">›</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button type="submit" class="pp-menu__item" style="width:100%;text-align:left">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                로그아웃
                <span class="pp-menu__arrow">›</span>
            </button>
        </form>
    </div>
@else
    <div class="pp-empty" style="padding-top:80px">
        <div class="pp-empty__icon">👋</div>
        <div class="pp-empty__title">로그인이 필요해요</div>
        <div class="pp-empty__desc">내 장소를 저장하고 언제든 꺼내보세요</div>
        <a href="{{ route('login') }}" class="pp-btn" style="display:inline-flex;align-items:center;justify-content:center;margin-top:20px;max-width:260px">로그인 하러 가기</a>
    </div>
@endauth
@endsection
