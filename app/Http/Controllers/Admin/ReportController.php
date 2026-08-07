<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curation;
use App\Models\CurationReport;
use App\Models\User;
use App\Notifications\ReportHandled;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        $reports = CurationReport::with(['curation:id,title,status,author_user_id', 'reporter:id,name'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(30);

        $pendingCount = CurationReport::where('status', 'pending')->count();
        $overdueCount = CurationReport::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(24))
            ->count();

        return view('admin.reports.index', compact('reports', 'status', 'pendingCount', 'overdueCount'));
    }

    public function handle(Request $request, CurationReport $report)
    {
        $data = $request->validate([
            'result' => 'required|in:removed,kept',
        ]);

        $report->update([
            'status' => 'handled',
            'result' => $data['result'],
            'handled_at' => now(),
        ]);

        if ($data['result'] === 'removed') {
            $report->curation->update([
                'status' => 'suspended',
                'approved_snapshot' => null,
            ]);
        }

        if ($report->reporter_user_id) {
            $reporter = User::find($report->reporter_user_id);
            if ($reporter) {
                $report->load('curation');
                $reporter->notify(new ReportHandled($report, $data['result']));
            }
        }

        return back()->with('success', '신고가 처리되었습니다.');
    }

    public function suspendUser(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $user->update([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspend_reason' => $request->reason,
        ]);

        Curation::where('author_user_id', $user->id)
            ->whereIn('status', ['approved', 'pending'])
            ->update(['status' => 'suspended', 'approved_snapshot' => null]);

        return back()->with('success', "사용자 {$user->name}의 계정이 정지되었습니다.");
    }

    public function unsuspendUser(User $user)
    {
        $user->update([
            'is_suspended' => false,
            'suspended_at' => null,
            'suspend_reason' => null,
        ]);

        return back()->with('success', "사용자 {$user->name}의 계정 정지가 해제되었습니다.");
    }
}
