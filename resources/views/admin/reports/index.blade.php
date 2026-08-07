@extends('admin.layouts.app')
@section('title', '신고 관리')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <h2 style="margin:0;font-size:18px">
        신고 관리
        @if($pendingCount > 0)
            <span style="background:#ef4444;color:#fff;font-size:12px;padding:2px 8px;border-radius:99px;margin-left:6px">대기 {{ $pendingCount }}</span>
        @endif
        @if($overdueCount > 0)
            <span style="background:#f59e0b;color:#fff;font-size:12px;padding:2px 8px;border-radius:99px;margin-left:4px">24h 초과 {{ $overdueCount }}</span>
        @endif
    </h2>
    <div style="display:flex;gap:6px">
        <a href="?status=pending" class="ad-btn{{ $status === 'pending' ? ' ad-btn--active' : '' }}">대기</a>
        <a href="?status=handled" class="ad-btn{{ $status === 'handled' ? ' ad-btn--active' : '' }}">처리됨</a>
        <a href="?status=all" class="ad-btn{{ $status === 'all' ? ' ad-btn--active' : '' }}">전체</a>
    </div>
</div>

<p style="font-size:13px;color:#6b7280;margin-bottom:14px">
    운영 기준: 신고 접수 후 <strong>24시간 이내</strong> 처리 원칙
</p>

<table class="ad-tbl">
    <thead>
        <tr>
            <th>ID</th>
            <th>리스트</th>
            <th>신고자</th>
            <th>사유</th>
            <th>상세</th>
            <th>접수일</th>
            <th>SLA</th>
            <th>상태</th>
            <th>조치</th>
        </tr>
    </thead>
    <tbody>
        @forelse($reports as $r)
        @php
            $isOverdue = $r->status === 'pending' && $r->created_at->lt(now()->subHours(24));
            $reasonLabels = [
                'spam' => '스팸/광고',
                'inappropriate' => '부적절한 콘텐츠',
                'copyright' => '저작권 침해',
                'false_info' => '허위 정보',
                'other' => '기타',
            ];
        @endphp
        <tr style="{{ $isOverdue ? 'background:#fef3c7' : '' }}">
            <td>{{ $r->id }}</td>
            <td>
                @if($r->curation)
                    <a href="{{ route('admin.curations.edit', $r->curation_id) }}" style="color:#2563eb">
                        {{ Str::limit($r->curation->title, 20) }}
                    </a>
                    <div style="font-size:11px;color:#9ca3af">{{ $r->curation->status }}</div>
                @else
                    <span style="color:#9ca3af">삭제됨</span>
                @endif
            </td>
            <td>{{ $r->reporter->name ?? '게스트' }}</td>
            <td>{{ $reasonLabels[$r->reason] ?? $r->reason }}</td>
            <td style="max-width:200px;word-break:break-all;font-size:12px">{{ Str::limit($r->detail, 50) }}</td>
            <td style="font-size:12px">{{ $r->created_at->format('m/d H:i') }}</td>
            <td>
                @if($r->status === 'pending')
                    @if($isOverdue)
                        <span style="color:#ef4444;font-weight:700">{{ $r->created_at->diffForHumans(null, true) }} 초과</span>
                    @else
                        <span style="color:#22c55e">{{ $r->created_at->diffForHumans(null, true) }}</span>
                    @endif
                @else
                    <span style="color:#6b7280">{{ $r->handled_at ? $r->handled_at->format('m/d H:i') : '-' }}</span>
                @endif
            </td>
            <td>
                @if($r->status === 'handled')
                    <span style="color:{{ $r->result === 'removed' ? '#ef4444' : '#22c55e' }}">
                        {{ $r->result === 'removed' ? '삭제' : '유지' }}
                    </span>
                @else
                    <span style="color:#f59e0b">대기</span>
                @endif
            </td>
            <td>
                @if($r->status === 'pending')
                    <div style="display:flex;gap:4px">
                        <form method="POST" action="{{ route('admin.reports.handle', $r) }}">
                            @csrf
                            <input type="hidden" name="result" value="removed">
                            <button type="submit" class="ad-btn ad-btn--danger" style="font-size:11px;padding:3px 8px" onclick="return confirm('해당 리스트를 비공개 처리합니다.')">삭제</button>
                        </form>
                        <form method="POST" action="{{ route('admin.reports.handle', $r) }}">
                            @csrf
                            <input type="hidden" name="result" value="kept">
                            <button type="submit" class="ad-btn" style="font-size:11px;padding:3px 8px">유지</button>
                        </form>
                    </div>
                    @if($r->curation && $r->curation->author_user_id)
                        @php
                            $authorReportCount = \App\Models\CurationReport::whereHas('curation', fn($q) => $q->where('author_user_id', $r->curation->author_user_id))
                                ->where('status', 'handled')
                                ->where('result', 'removed')
                                ->count();
                        @endphp
                        @if($authorReportCount >= 2)
                            <form method="POST" action="{{ route('admin.users.suspend', $r->curation->author_user_id) }}" style="margin-top:4px">
                                @csrf
                                <input type="hidden" name="reason" value="반복 위반 (삭제 {{ $authorReportCount }}회)">
                                <button type="submit" class="ad-btn ad-btn--danger" style="font-size:10px;padding:2px 6px" onclick="return confirm('이 사용자의 계정을 정지하시겠습니까?')">계정 정지</button>
                            </form>
                        @endif
                    @endif
                @else
                    <span style="font-size:11px;color:#9ca3af">처리완료</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;padding:40px;color:#9ca3af">신고 내역이 없습니다</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:16px">{{ $reports->appends(request()->query())->links() }}</div>
@endsection
