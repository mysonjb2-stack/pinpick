<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index()
    {
        $admins = Admin::orderByDesc('role')->latest()->get();
        return view('admin.admins.index', compact('admins'));
    }

    public function updatePassword(Request $request, Admin $admin)
    {
        $currentAdmin = Auth::guard('admin')->user();
        if ($currentAdmin->id !== $admin->id && !$currentAdmin->isSuper()) {
            abort(403);
        }

        $request->validate([
            'password' => 'required|string|min:4|confirmed',
        ]);

        $admin->update(['password' => $request->password]);

        return back()->with('success', '비밀번호가 변경되었습니다.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'login_id' => 'required|string|unique:admins,login_id',
            'name' => 'required|string|max:50',
            'password' => 'required|string|min:4',
        ]);

        Admin::create($data);

        return back()->with('success', '운영자가 추가되었습니다.');
    }

    public function destroy(Admin $admin)
    {
        if ($admin->role === 'super') {
            return back()->with('error', '최고관리자는 삭제할 수 없습니다.');
        }
        $admin->delete();
        return back()->with('success', '운영자가 삭제되었습니다.');
    }
}
