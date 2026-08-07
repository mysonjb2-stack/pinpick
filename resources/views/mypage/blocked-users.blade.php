@extends('layouts.app')
@section('page_title', '차단한 사용자 | 핀픽')
@section('noindex', true)

@section('header')
<header class="pp-header">
    <a href="{{ route('mypage') }}" class="pp-header__back" aria-label="뒤로">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <div class="pp-header__title">차단한 사용자</div>
</header>
@endsection

@section('content')
<div class="pp-blocked">
    @forelse($blockedUsers as $b)
        <div class="pp-blocked__item" data-uid="{{ $b->blocked_user_id }}">
            <div class="pp-blocked__user">
                @if($b->blockedUser && $b->blockedUser->profile_image)
                    <img class="pp-blocked__avatar" src="{{ $b->blockedUser->profile_image }}" alt="">
                @else
                    @php $hue = $b->blockedUser ? crc32($b->blockedUser->name) % 360 : 0; @endphp
                    <span class="pp-blocked__avatar pp-blocked__avatar--initial" style="background:hsl({{ $hue }},45%,55%)">{{ mb_substr($b->blockedUser->name ?? '?', 0, 1) }}</span>
                @endif
                <div class="pp-blocked__info">
                    <div class="pp-blocked__name">{{ $b->blockedUser->name ?? '삭제된 사용자' }}</div>
                    <div class="pp-blocked__date">{{ $b->created_at->format('Y.m.d') }} 차단</div>
                </div>
            </div>
            <button type="button" class="pp-blocked__unblock" data-uid="{{ $b->blocked_user_id }}">해제</button>
        </div>
    @empty
        <div class="pp-blocked__empty">
            <div class="pp-blocked__empty-icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--pp-text-sub,#999)" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            </div>
            <p>차단한 사용자가 없습니다</p>
        </div>
    @endforelse
</div>

<script>
(function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    document.querySelectorAll('.pp-blocked__unblock').forEach(btn => {
        btn.addEventListener('click', function() {
            const uid = this.dataset.uid;
            if (!confirm('차단을 해제하시겠어요?')) return;
            fetch('/api/block/' + uid, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    const item = document.querySelector('.pp-blocked__item[data-uid="' + uid + '"]');
                    if (item) {
                        item.style.transition = 'opacity .3s, height .3s';
                        item.style.opacity = '0';
                        item.style.height = '0';
                        item.style.overflow = 'hidden';
                        setTimeout(() => {
                            item.remove();
                            if (!document.querySelector('.pp-blocked__item')) {
                                document.querySelector('.pp-blocked').innerHTML = '<div class="pp-blocked__empty"><div class="pp-blocked__empty-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--pp-text-sub,#999)" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></div><p>차단한 사용자가 없습니다</p></div>';
                            }
                        }, 300);
                    }
                }
            });
        });
    });
})();
</script>
@endsection
