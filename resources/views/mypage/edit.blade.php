@extends('layouts.app')
@section('page_title', '프로필 수정 | 핀픽')
@section('noindex', true)
@section('app_class', 'pp-app--form')

@section('header')
<header class="pp-header">
    <a href="{{ route('mypage') }}" class="pp-header__icon pp-header__back" aria-label="뒤로">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </a>
    <div class="pp-header__title">프로필 수정</div>
    <div class="pp-header__spacer"></div>
</header>
@endsection

@section('content')
<form method="POST" action="{{ route('mypage.profile.update') }}" enctype="multipart/form-data" class="pp-profile-edit" id="ppProfileEditForm">
    @csrf

    <div class="pp-profile-edit__avatar-wrap">
        <div class="pp-profile-edit__avatar" id="ppAvatarPreview">
            @if($user->profile_image)
                <img src="{{ $user->profile_image }}" alt="" id="ppAvatarImg">
            @else
                <span class="pp-profile-edit__avatar-ph">😀</span>
            @endif
        </div>
        <button type="button" class="pp-profile-edit__camera" id="ppAvatarBtn" aria-label="프로필 사진 변경">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="4"/></svg>
        </button>
        <input type="file" name="avatar" id="ppAvatarInput" accept="image/*" hidden>
    </div>

    <div class="pp-profile-edit__field">
        <label class="pp-profile-edit__label" for="ppNameInput">닉네임</label>
        <input type="text" id="ppNameInput" name="name" class="pp-profile-edit__input" value="{{ old('name', $user->name) }}" maxlength="30" required>
        @error('name')<div class="pp-profile-edit__error">{{ $message }}</div>@enderror
        @error('avatar')<div class="pp-profile-edit__error">{{ $message }}</div>@enderror
    </div>

    @if($user->email)
        <div class="pp-profile-edit__field">
            <label class="pp-profile-edit__label">이메일</label>
            <div class="pp-profile-edit__readonly">{{ $user->email }}</div>
        </div>
    @endif

    <div class="pp-profile-edit__actions">
        <button type="submit" class="pp-btn">저장</button>
    </div>
</form>

<div class="pp-profile-edit__danger">
    <form method="POST" action="{{ route('logout') }}" class="pp-profile-edit__danger-form" id="ppLogoutForm">
        @csrf
        <button type="button" class="pp-btn pp-btn--outline" id="ppLogoutBtn">로그아웃</button>
    </form>

    <form method="POST" action="{{ route('mypage.account.destroy') }}" class="pp-profile-edit__danger-form pp-profile-edit__danger-form--left" id="ppAccountDeleteForm">
        @csrf
        @method('DELETE')
        <button type="button" class="pp-profile-edit__delete" id="ppAccountDeleteBtn">탈퇴하기</button>
    </form>
</div>

<div class="pp-modal" id="ppLogoutModal" hidden>
    <div class="pp-modal__backdrop"></div>
    <div class="pp-modal__box">
        <p class="pp-modal__msg">정말 로그아웃 하시겠어요?</p>
        <div class="pp-modal__actions">
            <button type="button" class="pp-modal__btn pp-modal__btn--cancel" id="ppLogoutCancel">취소</button>
            <button type="button" class="pp-modal__btn pp-modal__btn--confirm" id="ppLogoutConfirm">로그아웃</button>
        </div>
    </div>
</div>

<div class="pp-modal" id="ppDeleteModal" hidden>
    <div class="pp-modal__backdrop"></div>
    <div class="pp-modal__box">
        <p class="pp-modal__msg">정말 탈퇴하시겠어요?<br><span class="pp-modal__sub">저장한 모든 장소와 카테고리가 함께 삭제되며 복구할 수 없어요.</span></p>
        <div class="pp-modal__actions">
            <button type="button" class="pp-modal__btn pp-modal__btn--cancel" id="ppDeleteCancel">취소</button>
            <button type="button" class="pp-modal__btn pp-modal__btn--danger" id="ppDeleteConfirm">탈퇴하기</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const input = document.getElementById('ppAvatarInput');
    const preview = document.getElementById('ppAvatarPreview');
    var camEl = document.createElement('input');
    camEl.type = 'file'; camEl.accept = 'image/*';
    camEl.setAttribute('capture', 'environment');
    Object.assign(camEl.style, {position:'fixed',left:'-9999px',top:'-9999px',opacity:'0',width:'1px',height:'1px',pointerEvents:'none'});
    document.body.appendChild(camEl);
    function handleAvatar(inp) {
        const file = inp.files[0];
        if (!file) return;
        const url = URL.createObjectURL(file);
        preview.innerHTML = `<img src="${url}" alt="" id="ppAvatarImg">`;
        if (inp !== input) {
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
        }
        inp.value = '';
    }
    input.addEventListener('change', () => handleAvatar(input));
    camEl.addEventListener('change', () => handleAvatar(camEl));
    document.getElementById('ppAvatarBtn').addEventListener('click', () => {
        ppPhotoSheet(
            function() { camEl.click(); },
            function() { input.click(); }
        );
    });

    const logoutModal = document.getElementById('ppLogoutModal');
    document.getElementById('ppLogoutBtn').addEventListener('click', () => { logoutModal.hidden = false; });
    document.getElementById('ppLogoutCancel').addEventListener('click', () => { logoutModal.hidden = true; });
    logoutModal.querySelector('.pp-modal__backdrop').addEventListener('click', () => { logoutModal.hidden = true; });
    document.getElementById('ppLogoutConfirm').addEventListener('click', () => {
        document.getElementById('ppLogoutForm').submit();
    });

    const deleteModal = document.getElementById('ppDeleteModal');
    document.getElementById('ppAccountDeleteBtn').addEventListener('click', () => { deleteModal.hidden = false; });
    document.getElementById('ppDeleteCancel').addEventListener('click', () => { deleteModal.hidden = true; });
    deleteModal.querySelector('.pp-modal__backdrop').addEventListener('click', () => { deleteModal.hidden = true; });
    document.getElementById('ppDeleteConfirm').addEventListener('click', () => {
        document.getElementById('ppAccountDeleteForm').submit();
    });
})();
</script>
@endpush
