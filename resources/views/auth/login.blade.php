@extends('layouts.app')
@section('title', '로그인')

@section('content')
<div class="pp-login">
    <div class="pp-login__logo">핀픽</div>
    <div class="pp-login__tagline">내가 저장한 장소를 빠르게 꺼내 쓰는<br>나만의 지도</div>

    <a href="/auth/kakao" class="pp-login__btn pp-login__btn--kakao">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3C6.48 3 2 6.58 2 11c0 2.86 1.88 5.37 4.7 6.78-.2.74-.75 2.81-.86 3.25-.14.55.2.54.42.4.17-.12 2.7-1.84 3.79-2.58.64.1 1.3.15 1.95.15 5.52 0 10-3.58 10-8S17.52 3 12 3z"/></svg>
        카카오로 시작하기
    </a>
    <a href="/auth/naver" class="pp-login__btn pp-login__btn--naver">
        <span class="pp-login__naver-ico">N</span>
        네이버로 시작하기
    </a>
    <a href="/auth/google" class="pp-login__btn pp-login__btn--google">
        <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.7-6 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.8 1.1 7.9 3l5.7-5.7C34 6.1 29.3 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8c1.8-4.3 6-7.5 10.9-7.5 3 0 5.8 1.1 7.9 3L37.4 9.4C34 6.1 29.3 4 24 4 16.4 4 9.8 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.3 35.4 26.8 36.3 24 36.3c-5.3 0-9.7-3.3-11.3-8l-6.5 5C9.6 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4.1 5.5l6.2 5.2c-.4.4 6.6-4.8 6.6-14.7 0-1.3-.1-2.4-.4-3.5z"/></svg>
        Google로 시작하기
    </a>

    <div style="margin-top:30px;font-size:12px;color:var(--pp-text-sub)">
        로그인 없이 최대 5개까지 임시 저장 가능해요
    </div>
</div>
@endsection
