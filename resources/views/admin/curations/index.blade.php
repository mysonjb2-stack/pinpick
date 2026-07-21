@extends('admin.layouts.app')
@section('title', '큐레이션 관리')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div class="ad-search">
        <form method="GET" style="display:flex;gap:8px">
            <input class="ad-input" name="q" value="{{ $q }}" placeholder="제목 검색">
            <button class="ad-btn" type="submit">검색</button>
        </form>
    </div>
    <a href="{{ route('admin.curations.create') }}" class="ad-btn ad-btn--primary">+ 새 큐레이션</a>
</div>

<div class="ad-card">
    <div class="ad-table-wrap">
        <table class="ad-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>제목</th>
                    <th>타입</th>
                    <th>지역</th>
                    <th>장소</th>
                    <th>상태</th>
                    <th>조회</th>
                    <th>담기</th>
                    <th>생성일</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($curations as $c)
                <tr>
                    <td>{{ $c->id }}</td>
                    <td><a href="{{ route('admin.curations.edit', $c) }}">{{ $c->title }}</a></td>
                    <td><span class="ad-badge {{ $c->type === 'course' ? 'ad-badge--blue' : 'ad-badge--gray' }}">{{ $c->type === 'course' ? '코스' : '리스트' }}</span></td>
                    <td>{{ $c->region_label ?: '-' }}</td>
                    <td>{{ $c->places_count }}개</td>
                    <td>
                        @if($c->status === 'published')
                            <span class="ad-badge ad-badge--green">발행</span>
                        @else
                            <span class="ad-badge ad-badge--amber">초안</span>
                        @endif
                    </td>
                    <td>{{ number_format($c->view_count) }}</td>
                    <td>{{ number_format($c->save_count) }}</td>
                    <td class="ad-text-sub">{{ $c->created_at->format('Y-m-d') }}</td>
                    <td>
                        <a href="{{ route('admin.curations.edit', $c) }}" class="ad-btn ad-btn--sm">수정</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--ad-text-sub)">큐레이션이 없습니다</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($curations->hasPages())
    <div class="ad-pagination">
        {{ $curations->appends(request()->query())->links('pagination::simple-default') }}
    </div>
    @endif
</div>
@endsection
