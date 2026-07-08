@extends('admin.layouts.app')
@section('title', $user->name . ' — 회원 상세')

@section('content')
<div style="margin-bottom:16px">
    <a href="{{ route('admin.users.index') }}" class="ad-btn ad-btn--sm">&larr; 회원목록</a>
</div>

<div class="ad-grid-2" style="margin-bottom:24px">
    <div class="ad-card">
        <div class="ad-card__title" style="margin-bottom:12px">회원 정보</div>
        <table class="ad-table">
            <tr><th style="width:100px">ID</th><td>{{ $user->id }}</td></tr>
            <tr><th>이름</th><td>{{ $user->name }}</td></tr>
            <tr><th>이메일</th><td>{{ $user->email }}</td></tr>
            <tr>
                <th>로그인</th>
                <td>
                    @if($user->provider)
                        <span class="ad-badge ad-badge--blue">{{ $user->provider }}</span>
                    @else
                        <span class="ad-badge ad-badge--gray">이메일</span>
                    @endif
                </td>
            </tr>
            <tr><th>가입일</th><td>{{ $user->created_at->format('Y-m-d H:i') }}</td></tr>
            <tr><th>장소 수</th><td><strong>{{ $user->places_count }}</strong>개</td></tr>
        </table>
    </div>
    <div class="ad-card">
        <div class="ad-card__title" style="margin-bottom:12px">관리</div>
        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('정말 이 회원을 삭제하시겠습니까? 관련 데이터도 모두 삭제됩니다.')">
            @csrf @method('DELETE')
            <button type="submit" class="ad-btn ad-btn--danger">회원 삭제</button>
        </form>
    </div>
</div>

<div class="ad-card">
    <div class="ad-card__title" style="margin-bottom:12px">등록한 장소 ({{ $places->total() }})</div>
    <div class="ad-table-wrap">
        <table class="ad-table">
            <thead><tr><th>장소명</th><th>카테고리</th><th>주소</th><th>상태</th><th>등록일</th></tr></thead>
            <tbody>
            @forelse($places as $p)
                <tr>
                    <td><a href="{{ route('admin.places.show', $p) }}">{{ Str::limit($p->name, 30) }}</a></td>
                    <td class="ad-text-sub">{{ $p->category?->name ?? '-' }}</td>
                    <td class="ad-text-sub" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $p->road_address ?: $p->address }}</td>
                    <td>
                        <span class="ad-badge {{ $p->status === 'visited' ? 'ad-badge--green' : 'ad-badge--amber' }}">
                            {{ $p->status === 'visited' ? '방문완료' : '방문예정' }}
                        </span>
                    </td>
                    <td class="ad-text-sub">{{ $p->created_at->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="ad-text-sub" style="text-align:center">등록된 장소가 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($places->hasPages())
    <div class="ad-pagination">
        @if($places->onFirstPage())
            <span>&laquo;</span>
        @else
            <a href="{{ $places->previousPageUrl() }}">&laquo;</a>
        @endif
        @foreach($places->getUrlRange(max(1, $places->currentPage()-2), min($places->lastPage(), $places->currentPage()+2)) as $page => $url)
            @if($page == $places->currentPage())
                <span class="current">{{ $page }}</span>
            @else
                <a href="{{ $url }}">{{ $page }}</a>
            @endif
        @endforeach
        @if($places->hasMorePages())
            <a href="{{ $places->nextPageUrl() }}">&raquo;</a>
        @else
            <span>&raquo;</span>
        @endif
    </div>
    @endif
</div>
@endsection