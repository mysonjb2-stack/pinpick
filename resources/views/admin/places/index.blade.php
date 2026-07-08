@extends('admin.layouts.app')
@section('title', '장소관리')

@section('content')
<div class="ad-search">
    <form method="GET" action="{{ route('admin.places.index') }}" style="display:flex;gap:8px;width:100%">
        <input type="text" name="search" class="ad-input" placeholder="장소명 또는 주소 검색" value="{{ request('search') }}">
        <button class="ad-btn ad-btn--primary" type="submit">검색</button>
        @if(request('search') || request('user_id'))
            <a href="{{ route('admin.places.index') }}" class="ad-btn">초기화</a>
        @endif
    </form>
</div>

@if(request('user_id'))
    <div class="ad-alert ad-alert--success" style="background:#dbeafe;color:#1e40af">
        회원 ID {{ request('user_id') }}의 장소만 표시중
    </div>
@endif

<div class="ad-card">
    <div class="ad-card__title" style="margin-bottom:12px">전체 {{ number_format($places->total()) }}개</div>
    <div class="ad-table-wrap">
        <table class="ad-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>장소명</th>
                    <th>카테고리</th>
                    <th>회원</th>
                    <th>주소</th>
                    <th>구분</th>
                    <th>상태</th>
                    <th>등록일</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($places as $place)
                <tr>
                    <td class="ad-text-sub">{{ $place->id }}</td>
                    <td><a href="{{ route('admin.places.show', $place) }}">{{ Str::limit($place->name, 20) }}</a></td>
                    <td class="ad-text-sub">{{ $place->category?->name ?? '-' }}</td>
                    <td>
                        @if($place->user)
                            <a href="{{ route('admin.users.show', $place->user) }}">{{ $place->user->name }}</a>
                        @else
                            <span class="ad-text-sub">-</span>
                        @endif
                    </td>
                    <td class="ad-text-sub" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $place->road_address ?: $place->address }}</td>
                    <td>
                        <span class="ad-badge {{ $place->is_overseas ? 'ad-badge--blue' : 'ad-badge--green' }}">
                            {{ $place->is_overseas ? '해외' : '국내' }}
                        </span>
                    </td>
                    <td>
                        <span class="ad-badge {{ $place->status === 'visited' ? 'ad-badge--green' : 'ad-badge--amber' }}">
                            {{ $place->status === 'visited' ? '방문' : '예정' }}
                        </span>
                    </td>
                    <td class="ad-text-sub">{{ $place->created_at->format('Y-m-d') }}</td>
                    <td><a href="{{ route('admin.places.show', $place) }}" class="ad-btn ad-btn--sm">상세</a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="ad-text-sub" style="text-align:center;padding:24px">장소가 없습니다.</td></tr>
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