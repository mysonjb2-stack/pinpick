@extends('admin.layouts.app')
@section('title', '회원관리')

@section('content')
<div class="ad-search">
    <form method="GET" action="{{ route('admin.users.index') }}" style="display:flex;gap:8px;width:100%">
        <input type="text" name="search" class="ad-input" placeholder="이름 또는 이메일 검색" value="{{ request('search') }}">
        <button class="ad-btn ad-btn--primary" type="submit">검색</button>
        @if(request('search'))
            <a href="{{ route('admin.users.index') }}" class="ad-btn">초기화</a>
        @endif
    </form>
</div>

<div class="ad-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
        <div class="ad-card__title" style="margin:0">전체 {{ number_format($users->total()) }}명</div>
        <div style="display:flex;gap:6px">
            <a href="{{ route('admin.users.index', ['sort' => 'latest'] + request()->query()) }}" class="ad-btn ad-btn--sm{{ request('sort', 'latest') === 'latest' ? ' ad-btn--primary' : '' }}">최신순</a>
            <a href="{{ route('admin.users.index', ['sort' => 'places'] + request()->query()) }}" class="ad-btn ad-btn--sm{{ request('sort') === 'places' ? ' ad-btn--primary' : '' }}">장소순</a>
        </div>
    </div>
    <div class="ad-table-wrap">
        <table class="ad-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>이름</th>
                    <th>이메일</th>
                    <th>로그인</th>
                    <th>장소</th>
                    <th>가입일</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td class="ad-text-sub">{{ $user->id }}</td>
                    <td>
                        <a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a>
                        @if($user->is_review_account)<span class="ad-badge ad-badge--green" style="margin-left:4px;font-size:10px">심사</span>@endif
                    </td>
                    <td class="ad-text-sub">{{ $user->email }}</td>
                    <td>
                        @if($user->provider)
                            <span class="ad-badge ad-badge--blue">{{ $user->provider }}</span>
                        @else
                            <span class="ad-badge ad-badge--gray">이메일</span>
                        @endif
                    </td>
                    <td><strong>{{ $user->places_count }}</strong></td>
                    <td class="ad-text-sub">{{ $user->created_at->format('Y-m-d') }}</td>
                    <td>
                        <a href="{{ route('admin.users.show', $user) }}" class="ad-btn ad-btn--sm">상세</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="ad-text-sub" style="text-align:center;padding:24px">회원이 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="ad-pagination">
        @if($users->onFirstPage())
            <span>&laquo;</span>
        @else
            <a href="{{ $users->previousPageUrl() }}">&laquo;</a>
        @endif
        @foreach($users->getUrlRange(max(1, $users->currentPage()-2), min($users->lastPage(), $users->currentPage()+2)) as $page => $url)
            @if($page == $users->currentPage())
                <span class="current">{{ $page }}</span>
            @else
                <a href="{{ $url }}">{{ $page }}</a>
            @endif
        @endforeach
        @if($users->hasMorePages())
            <a href="{{ $users->nextPageUrl() }}">&raquo;</a>
        @else
            <span>&raquo;</span>
        @endif
    </div>
    @endif
</div>
@endsection