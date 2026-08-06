@extends('layouts.app')
@section('page_title', '로그인 | 핀픽')
@section('noindex', true)

@section('content')
@php
    $lastLogin = request()->cookie('pp_last_login');
    $lastLogin = in_array($lastLogin, ['kakao', 'naver', 'google', 'apple'], true) ? $lastLogin : null;
@endphp
<div class="pp-login">
    <div class="pp-login__logo" id="ppLogo">핀픽</div>
    <div class="pp-login__tagline">내가 저장한 장소를 빠르게 꺼내 쓰는<br>나만의 지도</div>

    <div class="pp-login__slot">
        @if($lastLogin === 'kakao')
            <div class="pp-login__tip">최근 사용한 로그인 방법</div>
        @endif
        <button type="button" onclick="handleKakaoLogin()" class="pp-login__btn pp-login__btn--kakao">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3C6.48 3 2 6.58 2 11c0 2.86 1.88 5.37 4.7 6.78-.2.74-.75 2.81-.86 3.25-.14.55.2.54.42.4.17-.12 2.7-1.84 3.79-2.58.64.1 1.3.15 1.95.15 5.52 0 10-3.58 10-8S17.52 3 12 3z"/></svg>
            카카오로 시작하기
        </button>
    </div>
    <div class="pp-login__slot">
        @if($lastLogin === 'naver')
            <div class="pp-login__tip">최근 사용한 로그인 방법</div>
        @endif
        <button type="button" onclick="handleNaverLogin()" class="pp-login__btn pp-login__btn--naver">
            <span class="pp-login__naver-ico">N</span>
            네이버로 시작하기
        </button>
    </div>
    <div class="pp-login__slot">
        @if($lastLogin === 'google')
            <div class="pp-login__tip">최근 사용한 로그인 방법</div>
        @endif
        <button type="button" onclick="handleGoogleLogin()" class="pp-login__btn pp-login__btn--google">
            <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.7-6 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.8 1.1 7.9 3l5.7-5.7C34 6.1 29.3 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8c1.8-4.3 6-7.5 10.9-7.5 3 0 5.8 1.1 7.9 3L37.4 9.4C34 6.1 29.3 4 24 4 16.4 4 9.8 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.3 35.4 26.8 36.3 24 36.3c-5.3 0-9.7-3.3-11.3-8l-6.5 5C9.6 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4.1 5.5l6.2 5.2c-.4.4 6.6-4.8 6.6-14.7 0-1.3-.1-2.4-.4-3.5z"/></svg>
            Google로 시작하기
        </button>
    </div>

    <div class="pp-login__slot pp-login__slot--apple" id="ppAppleSlot" hidden>
        @if($lastLogin === 'apple')
            <div class="pp-login__tip">최근 사용한 로그인 방법</div>
        @endif
        <button type="button" onclick="handleAppleLogin()" class="pp-login__btn pp-login__btn--apple">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/></svg>
            Apple로 시작하기
        </button>
    </div>

    <div style="margin-top:30px;font-size:12px;color:var(--pp-text-sub)">
        로그인 없이 최대 5개까지 임시 저장 가능해요
    </div>

    <div class="pp-login__terms">
        로그인 시 <a href="{{ route('terms', ['tab' => 'terms']) }}">이용약관</a>,
        <a href="{{ route('terms', ['tab' => 'privacy']) }}">개인정보처리방침</a>,
        <a href="{{ route('terms', ['tab' => 'location']) }}">위치기반서비스 이용약관</a>에<br>동의하는 것으로 간주됩니다.
    </div>

    <div id="ppReviewForm" style="display:none;margin-top:24px;padding:20px;background:var(--pp-bg-sub,#f5f5f5);border-radius:12px">
        <div style="font-size:13px;font-weight:600;margin-bottom:12px;color:var(--pp-text)">테스트 로그인</div>
        <input type="email" id="ppRevEmail" placeholder="이메일" autocomplete="email" style="width:100%;padding:10px 12px;border:1px solid var(--pp-border,#ddd);border-radius:8px;font-size:14px;margin-bottom:8px;box-sizing:border-box;background:var(--pp-bg,#fff);color:var(--pp-text)">
        <input type="password" id="ppRevPw" placeholder="비밀번호" autocomplete="current-password" style="width:100%;padding:10px 12px;border:1px solid var(--pp-border,#ddd);border-radius:8px;font-size:14px;margin-bottom:10px;box-sizing:border-box;background:var(--pp-bg,#fff);color:var(--pp-text)">
        <button type="button" id="ppRevBtn" onclick="doReviewLogin()" style="width:100%;padding:10px;border:none;border-radius:8px;background:var(--pp-primary,#5B4ACF);color:#fff;font-size:14px;font-weight:600;cursor:pointer">로그인</button>
        <div id="ppRevErr" style="display:none;margin-top:8px;font-size:12px;color:#C62828"></div>
    </div>
</div>

<script>
function isPinpickApp() {
    return /MYPINPICK/i.test(navigator.userAgent);
}

function handleSocialLogin(provider, handlerName) {
    if (isPinpickApp()) {
        if (window.pinpick_aos && typeof window.pinpick_aos[handlerName] === 'function') {
            window.pinpick_aos[handlerName]();
            return;
        }
        if (window.webkit?.messageHandlers?.[handlerName]) {
            window.webkit.messageHandlers[handlerName].postMessage('');
            return;
        }
    }
    location.href = '/auth/' + provider;
}

function handleKakaoLogin() { handleSocialLogin('kakao', 'kakaologin'); }
function handleGoogleLogin() { handleSocialLogin('google', 'googlelogin'); }
function handleNaverLogin() { handleSocialLogin('naver', 'naverlogin'); }
function handleAppleLogin() { handleSocialLogin('apple', 'applelogin'); }

// iOS 기기에서만 Apple 로그인 버튼 표시
(function() {
    var isIOS = /iPad|iPhone|iPod|Macintosh/.test(navigator.userAgent) && ('ontouchend' in document || navigator.maxTouchPoints > 0);
    if (isIOS || isPinpickApp()) {
        var el = document.getElementById('ppAppleSlot');
        if (el) el.hidden = false;
    }
})();

function nativeLoginSuccess(provider, accessToken) {
    fetch('/auth/native/' + provider, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ access_token: accessToken })
    })
    .then(r => r.ok ? r.json() : Promise.reject(r))
    .then(data => {
        if (data.success) location.href = data.redirect || '/';
    })
    .catch(() => {
        alert('로그인에 실패했어요. 다시 시도해주세요.');
    });
}

// 로고 5회 탭 → 심사 로그인 폼 토글
(function() {
    var logo = document.getElementById('ppLogo');
    var tapCount = 0, tapTimer = null;
    logo.addEventListener('click', function() {
        tapCount++;
        clearTimeout(tapTimer);
        tapTimer = setTimeout(function() { tapCount = 0; }, 2000);
        if (tapCount >= 5) {
            tapCount = 0;
            var form = document.getElementById('ppReviewForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    });
})();

function doReviewLogin() {
    var btn = document.getElementById('ppRevBtn');
    var errEl = document.getElementById('ppRevErr');
    btn.disabled = true;
    btn.textContent = '로그인 중...';
    errEl.style.display = 'none';

    fetch('/auth/review-login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            email: document.getElementById('ppRevEmail').value,
            password: document.getElementById('ppRevPw').value
        })
    })
    .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
    .then(function(res) {
        btn.disabled = false;
        btn.textContent = '로그인';
        if (res.ok && res.data.success) {
            location.href = res.data.redirect || '/';
        } else {
            errEl.textContent = res.data.error || '로그인 실패';
            errEl.style.display = 'block';
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.textContent = '로그인';
        errEl.textContent = '네트워크 오류';
        errEl.style.display = 'block';
    });
}

window.onKakaoLoginSuccess = function(t) { nativeLoginSuccess('kakao', t); };
window.onGoogleLoginSuccess = function(t) { nativeLoginSuccess('google', t); };
window.onNaverLoginSuccess = function(t) { nativeLoginSuccess('naver', t); };
window.onAppleLoginSuccess = function(t, name) {
    fetch('/auth/native/apple', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ access_token: t, name: name || null })
    })
    .then(function(r) { return r.ok ? r.json() : Promise.reject(r); })
    .then(function(data) { if (data.success) location.href = data.redirect || '/'; })
    .catch(function() { alert('로그인에 실패했어요. 다시 시도해주세요.'); });
};
</script>
@endsection
