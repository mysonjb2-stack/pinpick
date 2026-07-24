@extends('layouts.app')
@section('page_title', '내 리스트 | 핀픽')
@section('noindex', true)

@section('header')
<header class="pp-header">
    <a href="{{ route('mypage') }}" class="pp-header__back" aria-label="뒤로">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <div class="pp-header__title">내 리스트</div>
</header>
@endsection

@push('head')
<style>
.mc-empty { text-align: center; padding: 60px 20px; color: var(--pp-text-sub); }
.mc-empty__icon { font-size: 40px; margin-bottom: 12px; }
.mc-empty__title { font-size: 16px; font-weight: 700; color: var(--pp-text); margin-bottom: 6px; }
.mc-empty__desc { font-size: 13px; line-height: 1.5; margin-bottom: 20px; }

.mc-list { padding: 12px 16px; }
.mc-card {
  display: block; background: var(--pp-bg-card, #fff); border-radius: 14px;
  padding: 14px 16px; margin-bottom: 10px;
  border: 1px solid var(--pp-line); text-decoration: none; color: inherit;
}
.mc-card__top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
.mc-card__title { font-size: 15px; font-weight: 700; color: var(--pp-text); flex: 1; min-width: 0; }
.mc-card__badge {
  flex-shrink: 0; font-size: 11px; font-weight: 600; padding: 3px 8px;
  border-radius: 10px; white-space: nowrap;
}
.mc-card__badge--pending { background: #FFF3E0; color: #E65100; }
.mc-card__badge--approved { background: #E8F5E9; color: #2E7D32; }
.mc-card__badge--rejected { background: #FFEBEE; color: #C62828; }
.mc-card__badge--draft { background: var(--pp-chip-bg); color: var(--pp-text-sub); }
.mc-card__badge--suspended { background: #EFEBE9; color: #4E342E; }
.mc-card__meta { font-size: 12px; color: var(--pp-text-sub); margin-top: 4px; }
.mc-card__reason {
  font-size: 12px; color: #C62828; margin-top: 6px;
  padding: 8px 10px; background: #FFF5F5; border-radius: 8px; line-height: 1.5;
}
.mc-card__actions { display: flex; gap: 6px; margin-top: 10px; }
.mc-card__act {
  font-size: 12px; font-weight: 600; padding: 5px 12px; border-radius: 8px;
  border: 1px solid var(--pp-line); background: var(--pp-bg); color: var(--pp-text);
  cursor: pointer;
}
.mc-card__act--danger { color: #C62828; border-color: #FFCDD2; }
.mc-card__act--primary { background: var(--pp-primary); color: #fff; border-color: var(--pp-primary); }

.mc-create-btn {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  width: calc(100% - 32px); margin: 16px auto;
  padding: 12px; border-radius: 12px;
  background: var(--pp-primary); color: #fff;
  font-size: 14px; font-weight: 700; border: none; cursor: pointer;
  text-decoration: none;
}
.mc-create-btn svg { flex-shrink: 0; }
</style>
@endpush

@section('content')
<a href="{{ route('my.curations.create') }}" class="mc-create-btn">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    새 리스트 만들기
</a>

@if($curations->isEmpty())
<div class="mc-empty">
    <div class="mc-empty__icon">📋</div>
    <div class="mc-empty__title">아직 만든 리스트가 없어요</div>
    <div class="mc-empty__desc">내 장소를 모아 리스트를 만들고<br>탐색에 공개해보세요!</div>
</div>
@else
<div class="mc-list">
@foreach($curations as $c)
    @php
        $statusMap = [
            'pending' => ['검토하고 있어요', 'pending'],
            'approved' => ['탐색 탭에 공개 중', 'approved'],
            'rejected' => ['수정이 필요해요', 'rejected'],
            'draft' => ['임시저장', 'draft'],
            'suspended' => ['비공개 처리됨', 'suspended'],
        ];
        [$statusLabel, $statusClass] = $statusMap[$c->status] ?? ['알 수 없음', 'draft'];
        $catConfig = config("curation_categories.{$c->category}");
        $catLabel = $catConfig['label'] ?? $c->category;
    @endphp
    <div class="mc-card" data-id="{{ $c->id }}">
        <div class="mc-card__top">
            <div class="mc-card__title">{{ $c->title }}</div>
            <span class="mc-card__badge mc-card__badge--{{ $statusClass }}">{{ $statusLabel }}</span>
        </div>
        <div class="mc-card__meta">{{ $catLabel }} · {{ $c->places_count }}곳 · {{ $c->updated_at->format('Y.m.d') }}</div>

        @if($c->status === 'rejected' && $c->rejected_reason)
            <div class="mc-card__reason">반려 사유: {{ $c->rejected_reason }}</div>
        @endif

        <div class="mc-card__actions">
            @if(in_array($c->status, ['draft', 'rejected']))
                <a href="{{ route('my.curations.edit', $c) }}" class="mc-card__act mc-card__act--primary">수정 후 재제출</a>
                <button type="button" class="mc-card__act mc-card__act--danger" onclick="deleteCuration({{ $c->id }})">삭제</button>
            @elseif($c->status === 'approved')
                <a href="/c/{{ $c->id }}" class="mc-card__act">보기</a>
                <a href="{{ route('my.curations.edit', $c) }}" class="mc-card__act">수정</a>
                <button type="button" class="mc-card__act mc-card__act--danger" onclick="unpublishCuration({{ $c->id }})">내리기</button>
            @elseif($c->status === 'pending')
                <span class="mc-card__act" style="cursor:default;opacity:.6">검토 대기중</span>
            @endif
        </div>
    </div>
@endforeach
</div>
@endif

<script>
const csrf = '{{ csrf_token() }}';
function deleteCuration(id) {
    if (!confirm('정말 삭제할까요? 되돌릴 수 없습니다.')) return;
    fetch('/my/curations/' + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    }).then(r => r.json()).then(d => {
        if (d.success) location.reload();
    });
}
function unpublishCuration(id) {
    if (!confirm('탐색에서 내릴까요? 임시저장 상태로 돌아갑니다.')) return;
    fetch('/my/curations/' + id + '/unpublish', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    }).then(r => r.json()).then(d => {
        if (d.success) location.reload();
    });
}
</script>
@endsection
