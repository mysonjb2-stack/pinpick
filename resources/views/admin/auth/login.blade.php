<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>로그인 — 핀픽 Admin</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f1f5f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-wrap { width: 100%; max-width: 380px; padding: 16px; }
        .login-card { background: #fff; border-radius: 12px; padding: 36px 32px; box-shadow: 0 4px 24px rgba(0,0,0,.06); }
        .login-logo { text-align: center; margin-bottom: 28px; }
        .login-logo h1 { font-size: 22px; color: #1e293b; }
        .login-logo h1 span { font-size: 13px; background: #2563eb; color: #fff; padding: 2px 10px; border-radius: 4px; margin-left: 8px; font-weight: 500; }
        .login-group { margin-bottom: 18px; }
        .login-group label { display: block; font-size: 13px; font-weight: 500; color: #64748b; margin-bottom: 5px; }
        .login-group input { width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; outline: none; transition: border .15s; }
        .login-group input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
        .login-error { color: #dc2626; font-size: 13px; margin-bottom: 12px; }
        .login-btn { width: 100%; padding: 11px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background .15s; }
        .login-btn:hover { background: #1d4ed8; }
        .login-remember { display: flex; align-items: center; gap: 6px; margin-bottom: 20px; font-size: 13px; color: #64748b; }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo">
            <h1>핀픽 <span>Admin</span></h1>
        </div>
        @if($errors->any())
            <div class="login-error">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('admin.login') }}">
            @csrf
            <div class="login-group">
                <label for="login_id">아이디</label>
                <input type="text" id="login_id" name="login_id" value="{{ old('login_id') }}" autofocus required>
            </div>
            <div class="login-group">
                <label for="password">비밀번호</label>
                <input type="password" id="password" name="password" required>
            </div>
            <label class="login-remember">
                <input type="checkbox" name="remember" value="1"> 로그인 유지
            </label>
            <button type="submit" class="login-btn">로그인</button>
        </form>
    </div>
</div>
</body>
</html>