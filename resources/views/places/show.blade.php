@extends('layouts.app')
@section('title', $place->name)

@section('header')
<header class="pp-header">
    <button class="pp-header__icon" onclick="history.back()" aria-label="뒤로">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </button>
    <div class="pp-header__title">장소 상세</div>
</header>
@endsection

@section('content')
<div style="padding:16px">
    <div class="pp-card">
        <div class="pp-card__top">
            <div class="pp-card__icon">{{ $place->category?->icon ?? '📌' }}</div>
            <div class="pp-card__body">
                <div class="pp-card__name">{{ $place->name }}</div>
                <div class="pp-card__meta">{{ $place->category?->name ?? '기타' }}</div>
            </div>
            <span class="pp-badge pp-badge--{{ $place->status }}">{{ $place->status === 'visited' ? '방문완료' : '방문예정' }}</span>
        </div>
        @if($place->road_address || $place->address)
            <div style="margin-top:12px;font-size:13px">{{ $place->road_address ?: $place->address }}</div>
        @endif
        @if($place->phone)
            <div style="margin-top:6px;font-size:13px;color:var(--pp-text-sub)">{{ $place->phone }}</div>
        @endif
        @if($place->memo)
            <div style="margin-top:12px;padding:12px;background:var(--pp-bg-soft);border-radius:10px;font-size:13.5px">{{ $place->memo }}</div>
        @endif
    </div>

    <form method="POST" action="{{ route('places.destroy', $place) }}" onsubmit="return confirm('삭제할까요?')" style="margin-top:16px">
        @csrf @method('DELETE')
        <button type="submit" class="pp-btn pp-btn--ghost">삭제</button>
    </form>
</div>
@endsection
