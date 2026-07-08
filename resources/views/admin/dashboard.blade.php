@extends('admin.layouts.app')
@section('title', '대시보드')

@section('content')
<div class="ad-stats">
    <div class="ad-card">
        <div class="ad-card__title">오늘 방문자</div>
        <div class="ad-card__value">{{ number_format($todayVisitors) }}</div>
        <div class="ad-card__sub">페이지뷰 {{ number_format($todayPageviews) }}</div>
    </div>
    <div class="ad-card">
        <div class="ad-card__title">전체 회원</div>
        <div class="ad-card__value">{{ number_format($totalUsers) }}</div>
        <div class="ad-card__sub">오늘 +{{ $newUsersToday }}</div>
    </div>
    <div class="ad-card">
        <div class="ad-card__title">전체 장소</div>
        <div class="ad-card__value">{{ number_format($totalPlaces) }}</div>
        <div class="ad-card__sub">오늘 +{{ $newPlacesToday }}</div>
    </div>
    <div class="ad-card">
        <div class="ad-card__title">장소/회원 평균</div>
        <div class="ad-card__value">{{ $totalUsers > 0 ? round($totalPlaces / $totalUsers, 1) : 0 }}</div>
        <div class="ad-card__sub">회원당 평균 등록 장소</div>
    </div>
</div>

<div class="ad-card" style="margin-bottom:24px">
    <div class="ad-card__title" style="margin-bottom:16px">최근 7일 방문 추이</div>
    <div class="ad-chart">
        <canvas id="adChart"></canvas>
    </div>
</div>

<div class="ad-grid-2">
    <div class="ad-card">
        <div class="ad-card__title" style="margin-bottom:12px">최근 가입 회원</div>
        <div class="ad-table-wrap">
            <table class="ad-table">
                <thead><tr><th>이름</th><th>이메일</th><th>가입일</th></tr></thead>
                <tbody>
                @forelse($recentUsers as $u)
                    <tr>
                        <td><a href="{{ route('admin.users.show', $u) }}">{{ $u->name }}</a></td>
                        <td class="ad-text-sub">{{ $u->email }}</td>
                        <td class="ad-text-sub">{{ $u->created_at->format('m/d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="ad-text-sub">회원이 없습니다.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="ad-card">
        <div class="ad-card__title" style="margin-bottom:12px">최근 등록 장소</div>
        <div class="ad-table-wrap">
            <table class="ad-table">
                <thead><tr><th>장소명</th><th>카테고리</th><th>등록일</th></tr></thead>
                <tbody>
                @forelse($recentPlaces as $p)
                    <tr>
                        <td><a href="{{ route('admin.places.show', $p) }}">{{ Str::limit($p->name, 20) }}</a></td>
                        <td class="ad-text-sub">{{ $p->category?->name ?? '-' }}</td>
                        <td class="ad-text-sub">{{ $p->created_at->format('m/d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="ad-text-sub">장소가 없습니다.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($topPages->count())
<div class="ad-card" style="margin-top:24px">
    <div class="ad-card__title" style="margin-bottom:12px">오늘 인기 페이지 TOP 10</div>
    <div class="ad-table-wrap">
        <table class="ad-table">
            <thead><tr><th>#</th><th>페이지</th><th>조회수</th></tr></thead>
            <tbody>
            @foreach($topPages as $i => $page)
                <tr>
                    <td class="ad-text-sub">{{ $i + 1 }}</td>
                    <td style="word-break:break-all">{{ $page->path ?: '/' }}</td>
                    <td><strong>{{ number_format($page->cnt) }}</strong></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
@endpush

@push('scripts')
<script>
(function(){
    const ctx = document.getElementById('adChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: '방문자',
                    data: @json($chartVisitors),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37,99,235,.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    pointBackgroundColor: '#2563eb',
                },
                {
                    label: '페이지뷰',
                    data: @json($chartPageviews),
                    borderColor: '#94a3b8',
                    backgroundColor: 'transparent',
                    borderDash: [4,4],
                    tension: 0.3,
                    pointRadius: 3,
                    pointBackgroundColor: '#94a3b8',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true, padding: 16, font: { size: 12 } } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { font: { size: 11 } }, grid: { color: '#f1f5f9' } },
                x: { ticks: { font: { size: 11 } }, grid: { display: false } }
            }
        }
    });
})();
</script>
@endpush
