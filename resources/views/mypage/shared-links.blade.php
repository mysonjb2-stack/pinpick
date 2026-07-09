@extends('layouts.app')
@section('page_title', '내가 공유한 링크 | 핀픽')
@section('noindex', true)
@section('app_class', 'pp-app--detail')

@section('header')
<header class="pp-header">
    <a href="{{ route('mypage') }}" class="pp-header__back" aria-label="뒤로">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
    </a>
    <div class="pp-header__title">내가 공유한 링크</div>
</header>
@endsection

@section('content')
<div class="pp-shared-links">
    @if($collections->isEmpty())
        <div class="pp-empty" style="padding-top:60px">
            <div class="pp-empty__icon">🔗</div>
            <div class="pp-empty__title">공유한 링크가 없어요</div>
            <div class="pp-empty__desc">홈에서 카테고리를 선택하고 공유해보세요</div>
        </div>
    @else
        @foreach($collections as $col)
        <div class="pp-slink" data-id="{{ $col->id }}">
            <div class="pp-slink__body">
                <h3 class="pp-slink__title">{{ $col->title }}</h3>
                <p class="pp-slink__meta">
                    {{ $col->created_at->format('Y.m.d') }} · 장소 {{ $col->places_count }}개 · 조회 {{ $col->view_count }}회
                </p>
            </div>
            <div class="pp-slink__actions">
                @if($col->is_active)
                    <button type="button" class="pp-slink__btn" onclick="copyLink('{{ url('/s/' . $col->token) }}')">링크 복사</button>
                    <button type="button" class="pp-slink__btn pp-slink__btn--cancel" onclick="deactivateShare({{ $col->id }})">공유 취소</button>
                @else
                    <span class="pp-slink__badge">취소됨</span>
                @endif
            </div>
        </div>
        @endforeach
    @endif
</div>

<script>
function copyLink(url) {
    navigator.clipboard.writeText(url).then(() => showToast('링크가 복사됐어요'));
}
function deactivateShare(id) {
    if (!confirm('공유를 취소하면 이미 보낸 링크도 열 수 없게 돼요')) return;
    fetch(`/api/share/${id}/deactivate`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
}
function showToast(msg) {
    let t = document.getElementById('ppToast');
    if (!t) { t = document.createElement('div'); t.id = 'ppToast'; t.className = 'pp-toast'; document.body.appendChild(t); }
    t.textContent = msg;
    t.classList.add('is-show');
    setTimeout(() => t.classList.remove('is-show'), 2500);
}
</script>
@endsection
