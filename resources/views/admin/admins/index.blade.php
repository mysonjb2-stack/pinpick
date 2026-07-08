@extends('admin.layouts.app')
@section('title', '운영자관리')

@section('content')
<div class="ad-grid-2">
    <div class="ad-card">
        <div class="ad-card__title" style="margin-bottom:12px">운영자 목록</div>
        <div class="ad-table-wrap">
            <table class="ad-table">
                <thead><tr><th>아이디</th><th>이름</th><th>권한</th><th>마지막 로그인</th><th></th></tr></thead>
                <tbody>
                @foreach($admins as $admin)
                    <tr>
                        <td><strong>{{ $admin->login_id }}</strong></td>
                        <td>{{ $admin->name }}</td>
                        <td>
                            <span class="ad-badge {{ $admin->role === 'super' ? 'ad-badge--red' : 'ad-badge--blue' }}">
                                {{ $admin->role === 'super' ? '최고관리자' : '관리자' }}
                            </span>
                        </td>
                        <td class="ad-text-sub">{{ $admin->last_login_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>
                            @if($admin->role !== 'super' && Auth::guard('admin')->user()->isSuper())
                                <form method="POST" action="{{ route('admin.admins.destroy', $admin) }}" onsubmit="return confirm('삭제하시겠습니까?')" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ad-btn ad-btn--sm ad-btn--danger">삭제</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="ad-card" style="margin-bottom:20px">
            <div class="ad-card__title" style="margin-bottom:12px">비밀번호 변경</div>
            <form method="POST" action="{{ route('admin.admins.password', Auth::guard('admin')->user()) }}">
                @csrf @method('PATCH')
                <div class="ad-form-group">
                    <label>새 비밀번호</label>
                    <input type="password" name="password" class="ad-input" required minlength="4">
                </div>
                <div class="ad-form-group">
                    <label>비밀번호 확인</label>
                    <input type="password" name="password_confirmation" class="ad-input" required>
                </div>
                <button type="submit" class="ad-btn ad-btn--primary">변경</button>
            </form>
        </div>

        @if(Auth::guard('admin')->user()->isSuper())
        <div class="ad-card">
            <div class="ad-card__title" style="margin-bottom:12px">운영자 추가</div>
            <form method="POST" action="{{ route('admin.admins.store') }}">
                @csrf
                <div class="ad-form-group">
                    <label>아이디</label>
                    <input type="text" name="login_id" class="ad-input" required>
                </div>
                <div class="ad-form-group">
                    <label>이름</label>
                    <input type="text" name="name" class="ad-input" required>
                </div>
                <div class="ad-form-group">
                    <label>비밀번호</label>
                    <input type="password" name="password" class="ad-input" required minlength="4">
                </div>
                <button type="submit" class="ad-btn ad-btn--primary">추가</button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
