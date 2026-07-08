<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\User;
use App\Models\VisitLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $weekAgo = $today->copy()->subDays(6);

        // 오늘 통계
        $todayVisitors = VisitLog::where('visited_date', $today)->distinct('ip')->count('ip');
        $todayPageviews = VisitLog::where('visited_date', $today)->count();

        // 총 통계
        $totalUsers = User::count();
        $totalPlaces = Place::count();

        // 오늘 신규
        $newUsersToday = User::whereDate('created_at', $today)->count();
        $newPlacesToday = Place::whereDate('created_at', $today)->count();

        // 최근 7일 방문자/PV 차트 데이터
        $dailyStats = VisitLog::select(
                'visited_date',
                DB::raw('COUNT(DISTINCT ip) as visitors'),
                DB::raw('COUNT(*) as pageviews')
            )
            ->where('visited_date', '>=', $weekAgo)
            ->groupBy('visited_date')
            ->orderBy('visited_date')
            ->get()
            ->keyBy(fn($r) => $r->visited_date->format('Y-m-d'));

        $chartLabels = [];
        $chartVisitors = [];
        $chartPageviews = [];
        for ($d = $weekAgo->copy(); $d->lte($today); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $chartLabels[] = $d->format('m/d');
            $row = $dailyStats->get($key);
            $chartVisitors[] = $row ? $row->visitors : 0;
            $chartPageviews[] = $row ? $row->pageviews : 0;
        }

        // 최근 가입 회원
        $recentUsers = User::latest()->limit(5)->get();

        // 최근 등록 장소
        $recentPlaces = Place::with('category')->latest()->limit(5)->get();

        // 인기 페이지 (오늘)
        $topPages = VisitLog::select('path', DB::raw('COUNT(*) as cnt'))
            ->where('visited_date', $today)
            ->groupBy('path')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'todayVisitors', 'todayPageviews', 'totalUsers', 'totalPlaces',
            'newUsersToday', 'newPlacesToday',
            'chartLabels', 'chartVisitors', 'chartPageviews',
            'recentUsers', 'recentPlaces', 'topPages'
        ));
    }
}
