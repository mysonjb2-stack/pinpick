@extends('admin.layouts.app')
@section('title', '큐레이션 관리')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <div class="ad-search">
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input class="ad-input" name="q" value="{{ $q ?? '' }}" placeholder="제목 검색">
            <select class="ad-input" name="status" style="width:130px">
                <option value="">전체 상태</option>
                <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>검토 대기{{ $pendingCount > 0 ? " ({$pendingCount})" : '' }}</option>
                <option value="approved" {{ ($status ?? '') === 'approved' ? 'selected' : '' }}>공개됨</option>
                <option value="draft" {{ ($status ?? '') === 'draft' ? 'selected' : '' }}>초안</option>
                <option value="rejected" {{ ($status ?? '') === 'rejected' ? 'selected' : '' }}>반려됨</option>
                <option value="suspended" {{ ($status ?? '') === 'suspended' ? 'selected' : '' }}>비공개 처리</option>
            </select>
            <button class="ad-btn" type="submit">검색</button>
        </form>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        @if($pendingCount > 0)
            <a href="?status=pending" class="ad-btn" style="background:#FFF3E0;color:#E65100;border-color:#FFE0B2">
                대기 {{ $pendingCount }}건
            </a>
        @endif
        <a href="{{ route('admin.curations.create') }}" class="ad-btn ad-btn--primary">+ 새 큐레이션</a>
    </div>
</div>

<div class="ad-card">
    <div class="ad-table-wrap">
        <table class="ad-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th style="width:56px"></th>
                    <th>제목</th>
                    <th>작성자</th>
                    <th>카테고리</th>
                    <th>장소</th>
                    <th>상태</th>
                    <th>조회</th>
                    <th>담기</th>
                    <th>신고</th>
                    <th>수정일</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($curations as $c)
                @php
                    $statusConfig = [
                        'draft' => ['초안', 'ad-badge--gray'],
                        'pending' => ['검토 대기', 'ad-badge--amber'],
                        'approved' => ['공개', 'ad-badge--green'],
                        'rejected' => ['반려', 'ad-badge--red'],
                        'suspended' => ['비공개', 'ad-badge--gray'],
                    ];
                    [$sLabel, $sClass] = $statusConfig[$c->status] ?? ['?', 'ad-badge--gray'];
                    $authorName = $c->author_type === 'user' && $c->author ? $c->author->name : '운영자';
                    $catConfig = config("curation_categories.{$c->category}");
                    $catLabel = $catConfig['label'] ?? $c->category ?? '-';
                @endphp
                @php
                    $fp = $c->places->first();
                    $thumbUrl = null;
                    if ($fp) {
                        $thumbUrl = $fp->thumb_url;
                        if (!$thumbUrl && $fp->latitude && $fp->longitude) {
                            $thumbUrl = '/api/static-map?lat=' . $fp->latitude . '&lng=' . $fp->longitude . '&overseas=' . ($fp->is_overseas ? 1 : 0) . '&w=112&h=112';
                        }
                    }
                    $thumbUrl = $thumbUrl ?: asset('images/og-image.png');
                @endphp
                <tr style="{{ $c->status === 'pending' ? 'background:#FFFDE7' : '' }}">
                    <td>{{ $c->id }}</td>
                    <td style="padding:6px 4px">
                        <a href="{{ route('admin.curations.edit', $c) }}" style="display:block;width:48px;height:48px;border-radius:8px;overflow:hidden;background:#f0ede9;position:relative">
                            @if($thumbUrl !== asset('images/og-image.png'))
                            <img src="{{ $thumbUrl }}" alt="" style="width:48px;height:48px;object-fit:cover;display:block" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            @endif
                            <span style="display:{{ $thumbUrl === asset('images/og-image.png') ? 'flex' : 'none' }};width:48px;height:48px;align-items:center;justify-content:center">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z" fill="#c5bdb5"/></svg>
                            </span>
                        </a>
                    </td>
                    <td><a href="{{ route('admin.curations.edit', $c) }}">{{ $c->title }}</a></td>
                    <td>
                        @if($c->author_type === 'user')
                            <span class="ad-badge ad-badge--blue">{{ $authorName }}</span>
                        @else
                            <span class="ad-badge ad-badge--gray">운영자</span>
                        @endif
                    </td>
                    <td>{{ $catLabel }}</td>
                    <td>{{ $c->places_count }}개</td>
                    <td><span class="ad-badge {{ $sClass }}">{{ $sLabel }}</span></td>
                    <td>{{ number_format($c->view_count) }}</td>
                    <td>{{ number_format($c->save_count) }}</td>
                    <td>{{ $c->reports_count > 0 ? $c->reports_count : '-' }}</td>
                    <td class="ad-text-sub">{{ $c->updated_at->format('m-d H:i') }}</td>
                    <td style="white-space:nowrap">
                        @if($c->status === 'pending')
                            <form method="POST" action="{{ route('admin.curations.approve', $c) }}" style="display:inline">
                                @csrf
                                <button class="ad-btn ad-btn--sm ad-btn--primary" onclick="return confirm('승인하시겠습니까?')">승인</button>
                            </form>
                            <button class="ad-btn ad-btn--sm" style="color:#C62828" onclick="rejectCuration({{ $c->id }})">반려</button>
                        @elseif($c->status === 'approved' && $c->author_type === 'user')
                            <form method="POST" action="{{ route('admin.curations.suspend', $c) }}" style="display:inline">
                                @csrf
                                <button class="ad-btn ad-btn--sm" style="color:#C62828" onclick="return confirm('강제 비공개 처리하시겠습니까?')">비공개</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.curations.edit', $c) }}" class="ad-btn ad-btn--sm">수정</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="12" style="text-align:center;padding:40px;color:var(--ad-text-sub)">큐레이션이 없습니다</td></tr>
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

<script>
function rejectCuration(id) {
    const reason = prompt('반려 사유를 입력해주세요:');
    if (!reason) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/curations/' + id + '/reject';
    form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">'
        + '<input type="hidden" name="reason" value="' + reason.replace(/"/g, '&quot;') + '">';
    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection
