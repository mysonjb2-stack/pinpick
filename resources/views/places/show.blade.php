@extends('layouts.app')
@section('title', $place->name)

@section('header')
<header class="pp-header">
    <button class="pp-header__icon" onclick="history.back()" aria-label="뒤로">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </button>
    <div class="pp-header__title">장소 상세</div>
    <div class="pp-header__spacer"></div>
    <a href="{{ route('places.edit', $place) }}" class="pp-header__icon" aria-label="수정">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
    </a>
</header>
@endsection

@section('content')
<div style="padding:16px">
    {{-- 이미지 갤러리 --}}
    @if($place->images->count())
    <div class="pp-show-images">
        @foreach($place->images as $img)
        <div class="pp-show-images__item">
            <img src="{{ asset('storage/' . $img->path) }}" alt="{{ $place->name }}">
        </div>
        @endforeach
    </div>
    @endif

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
        @if($place->visited_at)
            <div style="margin-top:8px;font-size:12px;color:var(--pp-text-sub)">방문일: {{ $place->visited_at->format('Y.m.d') }}</div>
        @endif
    </div>

    {{-- 액션 버튼 (전화/길찾기) --}}
    @php
        $hasCoord = $place->lat && $place->lng;
        $addrText = $place->road_address ?: $place->address;
    @endphp
    @if($place->phone || $hasCoord)
    <div class="pp-actions">
        @if($place->phone)
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $place->phone) }}" class="pp-actions__btn pp-actions__btn--call">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.35 1.85.59 2.81.72A2 2 0 0 1 22 16.92z"/></svg>
                전화 예약
            </a>
        @endif
        @if($hasCoord)
            <button type="button" onclick="ppOpenRoute('kakao', {{ $place->lat }}, {{ $place->lng }}, @js($place->name))" class="pp-actions__btn pp-actions__btn--kakao">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                카카오 길찾기
            </button>
            <button type="button" onclick="ppOpenRoute('naver', {{ $place->lat }}, {{ $place->lng }}, @js($place->name))" class="pp-actions__btn pp-actions__btn--naver">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                네이버 길찾기
            </button>
        @endif
    </div>
    @endif

    {{-- 순서 변경 --}}
    @if($place->category_id)
    <div class="pp-reorder" id="reorderBox">
        <div class="pp-reorder__label">카테고리 내 순서</div>
        <div class="pp-reorder__btns">
            <button type="button" class="pp-reorder__btn" id="reorderUp" aria-label="위로 올리기">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="m18 15-6-6-6 6"/></svg>
                위로 올리기
            </button>
            <button type="button" class="pp-reorder__btn" id="reorderDown" aria-label="아래로 내리기">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="m6 9 6 6 6-6"/></svg>
                아래로 내리기
            </button>
        </div>
    </div>
    @endif

    <div style="display:flex;gap:10px;margin-top:16px">
        <a href="{{ route('places.edit', $place) }}" class="pp-btn pp-btn--outline" style="flex:1;text-align:center;text-decoration:none;display:flex;align-items:center;justify-content:center">수정</a>
        <form method="POST" action="{{ route('places.destroy', $place) }}" onsubmit="return confirm('삭제할까요?')" style="flex:1">
            @csrf @method('DELETE')
            <button type="submit" class="pp-btn pp-btn--ghost" style="width:100%">삭제</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
// 길찾기 (내 위치 → 목적지). 모바일: 앱스킴 → 1초 내 앱 미실행 시 웹 폴백. 데스크톱: 바로 웹.
window.ppOpenRoute = function(provider, lat, lng, name) {
    const ua = navigator.userAgent || '';
    const isMobile = /iPhone|iPad|iPod|Android/i.test(ua);
    const encName = encodeURIComponent(name);
    let webUrl, appUrl;

    if (provider === 'kakao') {
        appUrl = `kakaomap://route?ep=${lat},${lng}&by=CAR`;
        webUrl = `https://map.kakao.com/link/to/${encName},${lat},${lng}`;
    } else {
        appUrl = `nmap://route/car?dlat=${lat}&dlng=${lng}&dname=${encName}&appname=net.mypinpick`;
        webUrl = `https://map.naver.com/p/directions/-/${lng},${lat},${encName},,PLACE_POI/-/car`;
    }

    if (isMobile) {
        let t = Date.now();
        window.location.href = appUrl;
        setTimeout(() => {
            if (Date.now() - t < 1600 && !document.hidden) window.open(webUrl, '_blank');
        }, 1200);
    } else {
        window.open(webUrl, '_blank');
    }
};

(function() {
    const csrf = '{{ csrf_token() }}';
    const placeId = {{ $place->id }};
    const upBtn = document.getElementById('reorderUp');
    const downBtn = document.getElementById('reorderDown');
    if (!upBtn || !downBtn) return;

    async function reorder(direction) {
        const btn = direction === 'up' ? upBtn : downBtn;
        btn.disabled = true;
        try {
            const r = await fetch(`/api/places/${placeId}/reorder`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ direction })
            });
            const j = await r.json();
            if (j.ok) {
                const label = document.querySelector('.pp-reorder__label');
                label.textContent = direction === 'up' ? '위로 이동했어요' : '아래로 이동했어요';
                label.style.color = '#2f6fed';
                setTimeout(() => { label.textContent = '카테고리 내 순서'; label.style.color = ''; }, 1500);
            } else if (j.error === 'already_at_edge') {
                alert(direction === 'up' ? '이미 맨 위입니다.' : '이미 맨 아래입니다.');
            }
        } catch (e) { alert('네트워크 오류'); }
        btn.disabled = false;
    }

    upBtn.addEventListener('click', () => reorder('up'));
    downBtn.addEventListener('click', () => reorder('down'));
})();
</script>
@endpush
@endsection
