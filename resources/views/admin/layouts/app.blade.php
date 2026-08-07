<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', '관리자') — 핀픽 Admin</title>
    <style>
        :root {
            --ad-primary: #2563eb;
            --ad-primary-hover: #1d4ed8;
            --ad-bg: #f1f5f9;
            --ad-card: #ffffff;
            --ad-border: #e2e8f0;
            --ad-text: #1e293b;
            --ad-text-sub: #64748b;
            --ad-sidebar: #1e293b;
            --ad-sidebar-active: #334155;
            --ad-danger: #ef4444;
            --ad-success: #22c55e;
            --ad-warn: #f59e0b;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: var(--ad-bg); color: var(--ad-text); font-size: 14px; line-height: 1.5; }
        a { color: var(--ad-primary); text-decoration: none; }
        a:hover { text-decoration: underline; }

        .ad-wrap { display: flex; min-height: 100vh; }

        /* Sidebar */
        .ad-sidebar { width: 240px; background: var(--ad-sidebar); color: #cbd5e1; display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; z-index: 100; transition: transform .25s; }
        .ad-sidebar__logo { padding: 20px 24px; font-size: 18px; font-weight: 700; color: #fff; border-bottom: 1px solid #334155; display: flex; align-items: center; gap: 10px; }
        .ad-sidebar__logo span { font-size: 12px; background: var(--ad-primary); color: #fff; padding: 2px 8px; border-radius: 4px; font-weight: 500; }
        .ad-sidebar__nav { flex: 1; padding: 12px 0; overflow-y: auto; }
        .ad-sidebar__link { display: flex; align-items: center; gap: 12px; padding: 10px 24px; color: #94a3b8; font-size: 14px; transition: all .15s; }
        .ad-sidebar__link:hover { color: #e2e8f0; background: rgba(255,255,255,.05); text-decoration: none; }
        .ad-sidebar__link.is-active { color: #fff; background: var(--ad-sidebar-active); }
        .ad-sidebar__link svg { width: 18px; height: 18px; flex-shrink: 0; }
        .ad-sidebar__section { padding: 16px 24px 6px; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #475569; font-weight: 600; }
        .ad-sidebar__foot { padding: 16px 24px; border-top: 1px solid #334155; font-size: 12px; color: #475569; }
        .ad-sidebar__foot form { display: inline; }
        .ad-sidebar__foot button { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 12px; padding: 0; }
        .ad-sidebar__foot button:hover { color: #fff; }

        /* Main */
        .ad-main { flex: 1; margin-left: 240px; }
        .ad-topbar { background: var(--ad-card); border-bottom: 1px solid var(--ad-border); padding: 14px 28px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
        .ad-topbar__title { font-size: 16px; font-weight: 600; }
        .ad-topbar__right { font-size: 13px; color: var(--ad-text-sub); }
        .ad-content { padding: 24px 28px; }

        /* Mobile toggle */
        .ad-mobile-toggle { display: none; background: none; border: none; cursor: pointer; padding: 8px; }
        .ad-overlay { display: none; }

        @media (max-width: 768px) {
            .ad-sidebar { transform: translateX(-100%); }
            .ad-sidebar.is-open { transform: translateX(0); }
            .ad-main { margin-left: 0; }
            .ad-mobile-toggle { display: block; }
            .ad-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 90; }
            .ad-overlay.is-open { display: block; }
            .ad-content { padding: 16px; }
        }

        /* Cards */
        .ad-card { background: var(--ad-card); border: 1px solid var(--ad-border); border-radius: 10px; padding: 20px; }
        .ad-card__title { font-size: 13px; color: var(--ad-text-sub); font-weight: 500; margin-bottom: 8px; }
        .ad-card__value { font-size: 28px; font-weight: 700; }
        .ad-card__sub { font-size: 12px; color: var(--ad-text-sub); margin-top: 4px; }

        /* Stat grid */
        .ad-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        @media (max-width: 768px) { .ad-stats { grid-template-columns: repeat(2, 1fr); } }

        /* Table */
        .ad-table-wrap { overflow-x: auto; }
        .ad-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ad-table th { text-align: left; padding: 10px 12px; font-weight: 600; color: var(--ad-text-sub); border-bottom: 2px solid var(--ad-border); font-size: 12px; white-space: nowrap; }
        .ad-table td { padding: 10px 12px; border-bottom: 1px solid var(--ad-border); vertical-align: middle; }
        .ad-table tr:hover td { background: #f8fafc; }
        .ad-table .ad-text-sub { color: var(--ad-text-sub); }

        /* Buttons */
        .ad-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: 6px; font-size: 13px; font-weight: 500; border: 1px solid var(--ad-border); background: var(--ad-card); color: var(--ad-text); cursor: pointer; transition: all .15s; }
        .ad-btn:hover { background: #f8fafc; text-decoration: none; }
        .ad-btn--primary { background: var(--ad-primary); color: #fff; border-color: var(--ad-primary); }
        .ad-btn--primary:hover { background: var(--ad-primary-hover); }
        .ad-btn--danger { color: var(--ad-danger); border-color: var(--ad-danger); }
        .ad-btn--danger:hover { background: #fef2f2; }
        .ad-btn--sm { padding: 4px 10px; font-size: 12px; }

        /* Forms */
        .ad-input { width: 100%; padding: 8px 12px; border: 1px solid var(--ad-border); border-radius: 6px; font-size: 13px; outline: none; transition: border-color .15s; }
        .ad-input:focus { border-color: var(--ad-primary); box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
        .ad-search { display: flex; gap: 8px; margin-bottom: 16px; }
        .ad-search .ad-input { max-width: 300px; }

        /* Badge */
        .ad-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .ad-badge--blue { background: #dbeafe; color: #1d4ed8; }
        .ad-badge--green { background: #dcfce7; color: #16a34a; }
        .ad-badge--gray { background: #f1f5f9; color: #64748b; }
        .ad-badge--red { background: #fee2e2; color: #dc2626; }
        .ad-badge--amber { background: #fef3c7; color: #d97706; }

        /* Alert */
        .ad-alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
        .ad-alert--success { background: #dcfce7; color: #166534; }
        .ad-alert--error { background: #fee2e2; color: #991b1b; }

        /* Pagination */
        .ad-pagination { display: flex; gap: 4px; margin-top: 16px; justify-content: center; }
        .ad-pagination a, .ad-pagination span { padding: 6px 12px; border: 1px solid var(--ad-border); border-radius: 6px; font-size: 13px; }
        .ad-pagination span.current { background: var(--ad-primary); color: #fff; border-color: var(--ad-primary); }
        .ad-pagination a:hover { background: #f8fafc; text-decoration: none; }

        /* Grid */
        .ad-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .ad-grid-2 { grid-template-columns: 1fr; } }

        /* Chart area */
        .ad-chart { height: 260px; position: relative; }

        /* Form group */
        .ad-form-group { margin-bottom: 16px; }
        .ad-form-group label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 4px; color: var(--ad-text-sub); }
    </style>
    @stack('head')
</head>
<body>
<div class="ad-overlay" id="adOverlay"></div>
<div class="ad-wrap">
    <aside class="ad-sidebar" id="adSidebar">
        <div class="ad-sidebar__logo">
            핀픽 <span>Admin</span>
        </div>
        <nav class="ad-sidebar__nav">
            <a href="{{ route('admin.dashboard') }}" class="ad-sidebar__link{{ request()->routeIs('admin.dashboard') ? ' is-active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                대시보드
            </a>
            <div class="ad-sidebar__section">관리</div>
            <a href="{{ route('admin.users.index') }}" class="ad-sidebar__link{{ request()->routeIs('admin.users.*') ? ' is-active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                회원관리
            </a>
            <a href="{{ route('admin.places.index') }}" class="ad-sidebar__link{{ request()->routeIs('admin.places.*') ? ' is-active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                장소관리
            </a>
            <a href="{{ route('admin.curations.index') }}" class="ad-sidebar__link{{ request()->routeIs('admin.curations.*') ? ' is-active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                큐레이션
            </a>
            @php $reportPendingCount = \App\Models\CurationReport::where('status', 'pending')->count(); @endphp
            <a href="{{ route('admin.reports.index') }}" class="ad-sidebar__link{{ request()->routeIs('admin.reports.*') ? ' is-active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                신고 관리
                @if($reportPendingCount > 0)<span style="background:#ef4444;color:#fff;font-size:10px;padding:1px 5px;border-radius:99px;margin-left:4px">{{ $reportPendingCount }}</span>@endif
            </a>
            <a href="{{ route('admin.collector.index') }}" class="ad-sidebar__link{{ request()->routeIs('admin.collector.*') ? ' is-active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                수집 도우미
            </a>
            <a href="{{ route('admin.personas.index') }}" class="ad-sidebar__link{{ request()->routeIs('admin.personas.*') ? ' is-active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                페르소나
            </a>
            <div class="ad-sidebar__section">설정</div>
            <a href="{{ route('admin.admins.index') }}" class="ad-sidebar__link{{ request()->routeIs('admin.admins.*') ? ' is-active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                운영자관리
            </a>
        </nav>
        <div class="ad-sidebar__foot">
            {{ Auth::guard('admin')->user()->name }}
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">로그아웃</button>
            </form>
        </div>
    </aside>

    <main class="ad-main">
        <div class="ad-topbar">
            <div style="display:flex;align-items:center;gap:12px">
                <button type="button" class="ad-mobile-toggle" id="adMenuToggle" aria-label="메뉴">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="ad-topbar__title">@yield('title', '대시보드')</div>
            </div>
            <div class="ad-topbar__right">{{ now()->format('Y-m-d (D)') }}</div>
        </div>
        <div class="ad-content">
            @if(session('success'))
                <div class="ad-alert ad-alert--success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="ad-alert ad-alert--error">{{ session('error') }}</div>
            @endif
            @yield('content')
        </div>
    </main>
</div>

<script>
(function(){
    const sidebar = document.getElementById('adSidebar');
    const overlay = document.getElementById('adOverlay');
    const toggle = document.getElementById('adMenuToggle');
    function open(){ sidebar.classList.add('is-open'); overlay.classList.add('is-open'); }
    function close(){ sidebar.classList.remove('is-open'); overlay.classList.remove('is-open'); }
    if(toggle) toggle.addEventListener('click', open);
    if(overlay) overlay.addEventListener('click', close);
})();
</script>
@stack('scripts')
</body>
</html>