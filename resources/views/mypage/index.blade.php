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
        <div class="pp-profile__info">
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
        <a href="{{ route('mypage.profile.edit') }}" class="pp-profile__edit" aria-label="프로필 수정">수정</a>
    </div>

    <div class="pp-menu">
        <a href="{{ route('mypage.categories') }}" class="pp-menu__item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>
            카테고리 관리
            <span class="pp-menu__arrow">›</span>
        </a>
        <a href="{{ route('notices') }}" class="pp-menu__item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
            공지사항
            <span class="pp-menu__arrow">›</span>
        </a>
        <a href="{{ route('faq') }}" class="pp-menu__item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
            FAQ
            <span class="pp-menu__arrow">›</span>
        </a>
        <a href="{{ route('terms') }}" class="pp-menu__item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h8"/></svg>
            이용약관
            <span class="pp-menu__arrow">›</span>
        </a>
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
