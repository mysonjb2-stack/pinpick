@extends('layouts.app')
@section('title', '공지사항')
@section('app_class', 'pp-app--form')

@section('header')
<header class="pp-header">
    <a href="{{ route('mypage') }}" class="pp-header__icon pp-header__back" aria-label="뒤로">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </a>
    <div class="pp-header__title">공지사항</div>
    <div class="pp-header__spacer"></div>
</header>
@endsection

@section('content')
<div class="pp-notice">
    @forelse($notices as $n)
        <article class="pp-notice__item">
            <div class="pp-notice__date">{{ $n['date'] }}</div>
            <h3 class="pp-notice__title">{{ $n['title'] }}</h3>
            <p class="pp-notice__body">{!! nl2br(e($n['body'])) !!}</p>
        </article>
    @empty
        <div class="pp-empty" style="padding-top:80px">
            <div class="pp-empty__icon">📢</div>
            <div class="pp-empty__title">등록된 공지사항이 없어요</div>
            <div class="pp-empty__desc">새로운 소식이 있으면 이곳에 안내드릴게요.</div>
        </div>
    @endforelse
</div>
@endsection
