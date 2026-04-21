@extends('layouts.app')
@section('title', '이용약관')
@section('app_class', 'pp-app--form')

@section('header')
<header class="pp-header">
    <a href="{{ route('mypage') }}" class="pp-header__icon pp-header__back" aria-label="뒤로">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </a>
    <div class="pp-header__title">이용약관</div>
    <div class="pp-header__spacer"></div>
</header>
@endsection

@section('content')
<div class="pp-terms">
    <div class="pp-terms__tabs" id="ppTermsTabs">
        <button type="button" class="pp-terms__tab {{ $tab === 'terms' ? 'is-active' : '' }}" data-tab="terms">이용약관</button>
        <button type="button" class="pp-terms__tab {{ $tab === 'privacy' ? 'is-active' : '' }}" data-tab="privacy">개인정보 수집이용</button>
    </div>

    <section class="pp-terms__panel" data-panel="terms" {{ $tab === 'terms' ? '' : 'hidden' }}>
        <h3>제1조 (목적)</h3>
        <p>본 약관은 (주)맵큐브(이하 "회사")가 제공하는 핀픽 서비스(이하 "서비스")의 이용 조건 및 절차, 이용자와 회사 간의 권리·의무 및 책임사항을 규정함을 목적으로 합니다.</p>

        <h3>제2조 (정의)</h3>
        <p>"회원"이란 본 약관에 동의하고 회사가 제공하는 서비스에 가입한 자를 말합니다. "장소"란 회원이 서비스 내에서 저장, 편집할 수 있는 모든 위치 기반 데이터를 말합니다.</p>

        <h3>제3조 (서비스의 제공)</h3>
        <p>회사는 회원이 관심 있는 장소를 저장, 분류, 지도상에서 조회할 수 있는 기능을 제공합니다. 서비스의 구체적 내용은 회사가 필요에 따라 변경할 수 있습니다.</p>

        <h3>제4조 (회원 탈퇴)</h3>
        <p>회원은 언제든지 MY &gt; 프로필 수정 페이지에서 탈퇴를 신청할 수 있으며, 탈퇴 시 회원이 저장한 모든 장소·카테고리 데이터는 즉시 삭제되고 복구되지 않습니다.</p>

        <h3>제5조 (면책)</h3>
        <p>회사는 천재지변, 불가항력, 제3자 서비스(지도/검색 API 등)의 장애로 인한 서비스 중단에 대해 책임을 지지 않습니다.</p>

        <h3>제6조 (문의)</h3>
        <p>서비스 이용과 관련된 문의는 <a href="mailto:help.mapcube@gmail.com">help.mapcube@gmail.com</a> 으로 연락해 주시기 바랍니다.</p>

        <p class="pp-terms__meta">본 약관은 2026년 1월 1일부터 시행됩니다.</p>
    </section>

    <section class="pp-terms__panel" data-panel="privacy" {{ $tab === 'privacy' ? '' : 'hidden' }}>
        <h3>1. 수집하는 개인정보 항목</h3>
        <p>회사는 서비스 제공을 위해 다음의 개인정보를 수집합니다.</p>
        <ul>
            <li>필수: 이름(닉네임), 이메일, 소셜 로그인 식별자, 프로필 이미지</li>
            <li>자동 수집: 접속 로그, 쿠키, 접속 IP, 기기 정보</li>
            <li>위치 정보: 이용자 동의 시 현재 위치 좌표</li>
        </ul>

        <h3>2. 수집 및 이용 목적</h3>
        <ul>
            <li>회원 식별 및 로그인</li>
            <li>저장한 장소·카테고리의 조회 및 지도 표시</li>
            <li>서비스 개선 및 통계 분석</li>
            <li>고객 문의 응대 및 공지사항 전달</li>
        </ul>

        <h3>3. 보유 및 이용 기간</h3>
        <p>회원 탈퇴 시 즉시 파기합니다. 단, 관련 법령에 따라 일정 기간 보관이 필요한 경우 해당 기간 동안 보관됩니다.</p>

        <h3>4. 제3자 제공</h3>
        <p>회사는 이용자의 개인정보를 제3자에게 제공하지 않습니다. 단, 법령에 의거하거나 수사기관의 적법한 요청이 있는 경우 예외로 합니다.</p>

        <h3>5. 이용자의 권리</h3>
        <p>이용자는 언제든지 자신의 개인정보를 조회·수정할 수 있으며, 탈퇴를 통해 전체 삭제를 요청할 수 있습니다.</p>

        <h3>6. 개인정보 보호책임자</h3>
        <p>성명: 이학영 · 연락처: <a href="mailto:help.mapcube@gmail.com">help.mapcube@gmail.com</a></p>

        <p class="pp-terms__meta">본 방침은 2026년 1월 1일부터 시행됩니다.</p>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const tabs = document.getElementById('ppTermsTabs');
    tabs.addEventListener('click', (e) => {
        const btn = e.target.closest('.pp-terms__tab');
        if (!btn) return;
        const target = btn.dataset.tab;
        tabs.querySelectorAll('.pp-terms__tab').forEach(b => b.classList.toggle('is-active', b === btn));
        document.querySelectorAll('.pp-terms__panel').forEach(p => {
            p.hidden = p.dataset.panel !== target;
        });
        history.replaceState(null, '', '?tab=' + target);
    });
})();
</script>
@endpush
