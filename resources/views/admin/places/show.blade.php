@extends('admin.layouts.app')
@section('title', $place->name . ' — 장소 상세')

@section('content')
<div style="margin-bottom:16px">
    <a href="{{ route('admin.places.index') }}" class="ad-btn ad-btn--sm">&larr; 장소목록</a>
</div>

<div class="ad-grid-2" style="margin-bottom:24px">
    <div class="ad-card">
        <div class="ad-card__title" style="margin-bottom:12px">장소 정보</div>
        <table class="ad-table">
            <tr><th style="width:100px">ID</th><td>{{ $place->id }}</td></tr>
            <tr><th>장소명</th><td>{{ $place->name }}</td></tr>
            @if($place->original_name && $place->original_name !== $place->name)
            <tr><th>원본명</th><td class="ad-text-sub">{{ $place->original_name }}</td></tr>
            @endif
            <tr><th>카테고리</th><td>{{ $place->category?->name ?? '-' }}</td></tr>
            <tr>
                <th>테마</th>
                <td>
                    @forelse($place->themes as $theme)
                        <span class="ad-badge ad-badge--blue">{{ $theme->name }}</span>
                    @empty
                        <span class="ad-text-sub">-</span>
                    @endforelse
                </td>
            </tr>
            <tr><th>주소</th><td>{{ $place->road_address ?: $place->address }}</td></tr>
            <tr><th>전화</th><td>{{ $place->phone ?: '-' }}</td></tr>
            <tr>
                <th>구분</th>
                <td>
                    <span class="ad-badge {{ $place->is_overseas ? 'ad-badge--blue' : 'ad-badge--green' }}">{{ $place->is_overseas ? '해외' : '국내' }}</span>
                    <span class="ad-badge {{ $place->status === 'visited' ? 'ad-badge--green' : 'ad-badge--amber' }}">{{ $place->status === 'visited' ? '방문완료' : '방문예정' }}</span>
                </td>
            </tr>
            <tr><th>좌표</th><td class="ad-text-sub">{{ $place->lat }}, {{ $place->lng }}</td></tr>
            <tr><th>등록일</th><td>{{ $place->created_at->format('Y-m-d H:i') }}</td></tr>
            <tr>
                <th>등록자</th>
                <td>
                    @if($place->user)
                        <a href="{{ route('admin.users.show', $place->user) }}">{{ $place->user->name }}</a>
                    @else
                        -
                    @endif
                </td>
            </tr>
        </table>
    </div>
    <div class="ad-card">
        <div class="ad-card__title" style="margin-bottom:12px">이미지 ({{ $place->images->count() }})</div>
        @if($place->images->count())
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                @foreach($place->images as $img)
                    <img src="{{ $img->url }}" alt="" style="width:100px;height:80px;object-fit:cover;border-radius:8px;border:1px solid var(--ad-border)">
                @endforeach
            </div>
        @else
            <div class="ad-text-sub">등록된 이미지 없음</div>
        @endif
        @if($place->memo)
            <div class="ad-card__title" style="margin:16px 0 8px">메모</div>
            <div style="padding:12px;background:var(--ad-bg);border-radius:8px;font-size:13px">{{ $place->memo }}</div>
        @endif
        <div style="margin-top:24px">
            <form method="POST" action="{{ route('admin.places.destroy', $place) }}" onsubmit="return confirm('정말 이 장소를 삭제하시겠습니까?')">
                @csrf @method('DELETE')
                <button type="submit" class="ad-btn ad-btn--danger">장소 삭제</button>
            </form>
        </div>
    </div>
</div>
@endsection