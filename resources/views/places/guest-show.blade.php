@extends('layouts.app')
@section('title', '장소 상세')
@section('app_class', 'pp-app--detail')

@section('header')
<header class="pp-header">
    <a href="/" class="pp-header__icon" aria-label="뒤로">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </a>
    <div class="pp-header__title">장소 상세</div>
    <div class="pp-header__spacer"></div>
    <div class="pp-header__more" id="ppMoreWrap">
        <button type="button" class="pp-header__icon" id="ppMoreBtn" aria-label="더보기" aria-haspopup="menu" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
        </button>
        <div class="pp-header__menu" id="ppMoreMenu" role="menu" hidden>
            <button type="button" class="pp-header__menu-item" role="menuitem" data-guest-action="edit">수정</button>
            <button type="button" class="pp-header__menu-item pp-header__menu-item--del" role="menuitem" data-guest-action="delete">삭제</button>
        </div>
    </div>
</header>
@endsection

@section('content')
<div style="padding:16px" id="ppGuestShow" data-local-id="{{ $localId }}">

    <div class="pp-show-images" id="ppGuestThumb" hidden>
        <div class="pp-show-images__item">
            <img id="ppGuestThumbImg" src="" alt="장소 위치">
        </div>
    </div>

    <div class="pp-card">
        <div class="pp-card__top">
            <div class="pp-card__icon" id="ppGuestIcon">📌</div>
            <div class="pp-card__body">
                <div class="pp-card__name" id="ppGuestName">로딩 중…</div>
                <div class="pp-card__meta">
                    <span id="ppGuestCat">기타</span>
                </div>
            </div>
            <span class="pp-badge pp-badge--planned" id="ppGuestBadge">방문예정</span>
        </div>
        <div class="pp-info-row" id="ppGuestAddrRow" hidden>
            <svg class="pp-info-row__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span id="ppGuestAddr"></span>
        </div>
        <div class="pp-info-row pp-info-row--sub" id="ppGuestPhoneRow" hidden>
            <svg class="pp-info-row__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.35 1.85.59 2.81.72A2 2 0 0 1 22 16.92z"/></svg>
            <a id="ppGuestPhone" href="#"></a>
        </div>
        <div id="ppGuestMemoWrap" hidden style="margin-top:12px;padding:12px;background:var(--pp-bg-soft);border-radius:10px;font-size:13.5px">
            <span id="ppGuestMemo"></span>
        </div>
        <div id="ppGuestVisitedWrap" hidden style="margin-top:8px;font-size:12px;color:var(--pp-text-sub)">
            방문일: <span id="ppGuestVisited"></span>
        </div>
    </div>

    <section class="pp-loc" id="ppGuestLoc" hidden>
        <div class="pp-loc__head">
            <div class="pp-loc__title">위치</div>
        </div>
        <div class="pp-loc__addr" id="ppGuestLocAddr" hidden></div>
        <div class="pp-loc__map-wrap">
            <div class="pp-loc__map">
                <img id="ppGuestMapImg" src="" alt="위치 지도" class="pp-loc__map-fallback">
            </div>
            <a id="ppGuestGoogleLink" href="#" target="_blank" rel="noopener" class="pp-loc__glink" hidden>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z" fill="#4285F4"/></svg>
                Google 지도에서 보기
            </a>
        </div>
        <div class="pp-dirs" id="ppGuestDirs"></div>
        <button type="button" class="pp-loc__copy" id="ppGuestCopyBtn" hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
            <span>주소복사</span>
        </button>
    </section>

    <div id="ppGuestNotFound" hidden style="text-align:center;padding:48px 16px;color:var(--pp-text-sub)">
        <div style="font-size:15px;margin-bottom:12px">장소를 찾을 수 없어요</div>
        <a href="/" class="pp-btn pp-btn--primary" style="display:inline-block;padding:10px 20px;background:var(--pp-primary);color:#fff;border-radius:999px;text-decoration:none;font-size:14px">홈으로</a>
    </div>

</div>

<div class="pp-guest-modal" id="ppGuestLoginModal" hidden aria-hidden="true" role="dialog" aria-labelledby="ppGuestLoginModalTitle">
    <div class="pp-guest-modal__backdrop" data-close></div>
    <div class="pp-guest-modal__card" role="document">
        <div class="pp-guest-modal__title" id="ppGuestLoginModalTitle">수정 · 삭제는 로그인 후<br>이용하실 수 있어요</div>
        <a href="{{ route('login') }}" class="pp-guest-modal__cta">로그인하고 내 지도 이어보기</a>
        <button type="button" class="pp-guest-modal__close" data-close>닫기</button>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 헤더 더보기 메뉴 + 로그인 유도 모달
(function(){
    const wrap = document.getElementById('ppMoreWrap');
    const btn = document.getElementById('ppMoreBtn');
    const menu = document.getElementById('ppMoreMenu');
    const modal = document.getElementById('ppGuestLoginModal');
    if (!wrap || !btn || !menu || !modal) return;

    function closeMenu(){ menu.hidden = true; btn.setAttribute('aria-expanded', 'false'); }
    function openModal(){
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(){
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const open = menu.hidden;
        menu.hidden = !open;
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) closeMenu(); });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (!modal.hidden) closeModal();
        else closeMenu();
    });

    menu.addEventListener('click', (e) => {
        const item = e.target.closest('[data-guest-action]');
        if (!item) return;
        closeMenu();
        openModal();
    });
    modal.addEventListener('click', (e) => {
        if (e.target.closest('[data-close]')) closeModal();
    });
})();

(function() {
    const root = document.getElementById('ppGuestShow');
    const localId = root.dataset.localId;
    const list = JSON.parse(localStorage.getItem('pinpick_guest_places') || '[]');
    const p = list.find(x => String(x.id) === String(localId));

    if (!p) {
        document.getElementById('ppGuestNotFound').hidden = false;
        document.querySelector('.pp-card').hidden = true;
        return;
    }

    function esc(s) { return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    document.title = (p.name || '장소') + ' · 핀픽';

    // 기본 정보
    document.getElementById('ppGuestName').textContent = p.name || '';
    document.getElementById('ppGuestCat').textContent = p.category_name || '기타';
    if (p.category_icon) document.getElementById('ppGuestIcon').textContent = p.category_icon;

    const badge = document.getElementById('ppGuestBadge');
    if (p.status === 'visited') {
        badge.textContent = '방문완료';
        badge.className = 'pp-badge pp-badge--visited';
    }

    const addr = p.road_address || p.address || '';
    if (addr) {
        document.getElementById('ppGuestAddr').textContent = addr;
        document.getElementById('ppGuestAddrRow').hidden = false;
    }

    if (p.phone) {
        const a = document.getElementById('ppGuestPhone');
        a.textContent = p.phone;
        a.href = 'tel:' + String(p.phone).replace(/[^0-9+]/g, '');
        document.getElementById('ppGuestPhoneRow').hidden = false;
    }

    if (p.memo) {
        document.getElementById('ppGuestMemo').textContent = p.memo;
        document.getElementById('ppGuestMemoWrap').hidden = false;
    }

    if (p.visited_at) {
        document.getElementById('ppGuestVisited').textContent = String(p.visited_at).slice(0, 10).replaceAll('-', '.');
        document.getElementById('ppGuestVisitedWrap').hidden = false;
    }

    // 위치/지도
    const lat = +p.lat, lng = +p.lng;
    if (Number.isFinite(lat) && Number.isFinite(lng)) {
        const isOv = !!p.is_overseas;
        const mapUrl = `/api/static-map?lat=${lat}&lng=${lng}&overseas=${isOv ? 1 : 0}&w=800&h=400`;

        // 상단 썸네일
        document.getElementById('ppGuestThumbImg').src = mapUrl;
        document.getElementById('ppGuestThumb').hidden = false;

        // 위치 섹션
        document.getElementById('ppGuestLoc').hidden = false;
        document.getElementById('ppGuestMapImg').src = mapUrl;

        if (addr) {
            document.getElementById('ppGuestLocAddr').textContent = addr;
            document.getElementById('ppGuestLocAddr').hidden = false;
        }

        // 길찾기 버튼
        const dirs = document.getElementById('ppGuestDirs');
        if (isOv) {
            dirs.classList.add('pp-dirs--solo');
            dirs.innerHTML = `
                <button type="button" class="pp-dirs__btn" data-provider="google">
                    <span class="pp-dirs__ico pp-dirs__ico--google">G</span>
                    <span class="pp-dirs__lab">구글지도 길찾기</span>
                </button>`;
            const g = document.getElementById('ppGuestGoogleLink');
            g.href = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
            g.hidden = false;
        } else {
            dirs.innerHTML = `
                <button type="button" class="pp-dirs__btn" data-provider="naver">
                    <span class="pp-dirs__ico pp-dirs__ico--naver">N</span>
                    <span class="pp-dirs__lab">네이버지도 길찾기</span>
                </button>
                <button type="button" class="pp-dirs__btn" data-provider="kakao">
                    <span class="pp-dirs__ico pp-dirs__ico--kakao">K</span>
                    <span class="pp-dirs__lab">카카오맵 길찾기</span>
                </button>`;
        }
        dirs.addEventListener('click', (e) => {
            const btn = e.target.closest('.pp-dirs__btn');
            if (!btn) return;
            openRoute(btn.dataset.provider, lat, lng, p.name || '');
        });

        // 주소복사
        if (addr) {
            const copyBtn = document.getElementById('ppGuestCopyBtn');
            copyBtn.hidden = false;
            copyBtn.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(addr);
                    copyBtn.querySelector('span').textContent = '복사됨';
                    setTimeout(() => { copyBtn.querySelector('span').textContent = '주소복사'; }, 1400);
                } catch (e) {}
            });
        }
    }

    function openRoute(provider, lat, lng, name) {
        const ua = navigator.userAgent || '';
        const isMobile = /iPhone|iPad|iPod|Android/i.test(ua);
        const encName = encodeURIComponent(name);
        let webUrl, appUrl;
        if (provider === 'google') {
            appUrl = `comgooglemaps://?daddr=${lat},${lng}&directionsmode=driving`;
            webUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
        } else if (provider === 'kakao') {
            appUrl = `kakaomap://route?ep=${lat},${lng}&by=CAR`;
            webUrl = `https://map.kakao.com/link/to/${encName},${lat},${lng}`;
        } else {
            appUrl = `nmap://route/car?dlat=${lat}&dlng=${lng}&dname=${encName}&appname=net.mypinpick`;
            webUrl = `https://map.naver.com/p/directions/-/${lng},${lat},${encName},,PLACE_POI/-/car`;
        }
        if (isMobile) {
            const t = Date.now();
            window.location.href = appUrl;
            setTimeout(() => { if (Date.now() - t < 1600 && !document.hidden) window.open(webUrl, '_blank'); }, 1200);
        } else {
            window.open(webUrl, '_blank');
        }
    }
})();
</script>
@endpush
