@extends('layouts.app')
@section('page_title', $collection->title . ' | 핀픽')
@section('app_class', 'pp-app--shared')

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@php $ogImg = $collection->places->first()?->thumbnail_url ?: asset('images/og-image.png'); @endphp
@section('og_title', $collection->title)
@section('og_description', '장소 ' . $collection->places->count() . '개 · 나만의 장소, 나만의 지도 핀픽')
@section('og_image', $ogImg)

@section('content')
<div class="pp-share">
    <div class="pp-share__header">
        <div class="pp-share__header-top">
            <h1 class="pp-share__title">{{ $collection->title }}</h1>
            <a href="/" class="pp-share__logo" aria-label="핀픽 홈">
                <img src="{{ asset('icon-192.png') }}" alt="" width="28" height="28" style="border-radius:6px">
            </a>
        </div>
        <p class="pp-share__meta">{{ $collection->user->name }}님이 공유한 장소 {{ $collection->places->count() }}개</p>
    </div>

    <div class="pp-share__app-banner" id="ppAppBanner" style="display:none">
        <div class="pp-share__app-banner-left">
            <img src="{{ asset('icon-192.png') }}" alt="" width="32" height="32" style="border-radius:8px">
            <span class="pp-share__app-banner-text">앱에서 더 편하게 저장하세요</span>
        </div>
        <div class="pp-share__app-banner-right">
            <button type="button" class="pp-share__app-banner-open" id="ppAppOpen">앱으로 열기</button>
            <button type="button" class="pp-share__app-banner-close" id="ppAppClose" aria-label="닫기">✕</button>
        </div>
    </div>

    <div class="pp-share__map" id="shareMap"></div>

    <div class="pp-share__list-header">
        <span class="pp-share__list-count">장소 {{ $collection->places->count() }}개</span>
        <button type="button" class="pp-share__select-all" id="shareSelectAll">전체선택</button>
    </div>

    <div class="pp-share__list" id="shareList">
        @foreach($collection->places as $i => $place)
        <div class="pp-share__card" data-idx="{{ $i }}" data-lat="{{ $place->latitude }}" data-lng="{{ $place->longitude }}" data-id="{{ $place->id }}">
            <div class="pp-share__card-thumb">
                @if($place->thumbnail_url)
                    <img src="{{ $place->thumbnail_url }}" alt="" loading="lazy">
                @else
                    <div class="pp-share__card-emoji">📍</div>
                @endif
                <span class="pp-share__card-num">{{ $i + 1 }}</span>
            </div>
            <div class="pp-share__card-body">
                <div class="pp-share__card-top">
                    <span class="pp-share__card-label">{{ $place->category_label }}</span>
                    @php
                        $isOverseas = $place->is_overseas;
                        $searchName = $place->original_place_name ?: $place->display_name;
                        if ($isOverseas) {
                            $mapUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($searchName . ' ' . $place->address);
                        } else {
                            $mapUrl = 'https://map.naver.com/p/search/' . urlencode($searchName . ' ' . $place->address);
                        }
                    @endphp
                    <a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="pp-share__card-maplink" onclick="event.stopPropagation()">지도앱에서 보기 ↗</a>
                </div>
                <h3 class="pp-share__card-name">{{ $place->display_name }}</h3>
                @if($place->address)
                    <p class="pp-share__card-addr">{{ $place->address }}</p>
                @endif
                @if($place->memo)
                    <p class="pp-share__card-memo">{{ $place->memo }}</p>
                @endif
            </div>
            <button type="button" class="pp-share__card-add" data-place-id="{{ $place->id }}" aria-label="담기">
                <svg class="pp-share__card-add-icon" width="22" height="22" viewBox="0 0 22 22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="11" y1="5" x2="11" y2="17"/><line x1="5" y1="11" x2="17" y2="11"/></svg>
                <svg class="pp-share__card-check-icon" width="22" height="22" viewBox="0 0 22 22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 11 10 15 16 7"/></svg>
            </button>
        </div>
        @endforeach
    </div>

    <div class="pp-share__cta-spacer"></div>
</div>

<div class="pp-share__cta" id="shareCta">
    <button type="button" class="pp-btn pp-share__cta-btn" id="shareSaveBtn">이 장소들 내 핀픽에 담기</button>
</div>

{{-- 게스트 5개 제한 안내 --}}
<div class="pp-share__select-sheet" id="shareGuestSheet">
    <div class="pp-share__select-backdrop" data-role="close"></div>
    <div class="pp-share__select-panel">
        <div class="pp-share__select-header">
            <h3>게스트 저장 안내</h3>
            <button type="button" class="pp-share__select-close" data-role="close">✕</button>
        </div>
        <div class="pp-share__select-info" id="shareGuestInfo"></div>
        <div class="pp-share__select-actions">
            <button type="button" class="pp-btn" id="shareGuestSave">저장하기</button>
            <button type="button" class="pp-btn pp-btn--ghost" id="shareGuestLogin">로그인하고 전부 저장</button>
        </div>
    </div>
</div>

{{-- 카테고리 선택 바텀시트 --}}
<div class="pp-share__cat-sheet" id="shareCatSheet">
    <div class="pp-share__select-backdrop" data-role="close"></div>
    <div class="pp-share__select-panel">
        <div class="pp-share__select-header">
            <h3>저장할 카테고리 선택</h3>
            <button type="button" class="pp-share__select-close" data-role="close">✕</button>
        </div>
        <div class="pp-share__cat-list" id="shareCatList">
            <label class="pp-share__cat-option">
                <input type="radio" name="share_cat" value="__new__" checked>
                <span class="pp-share__cat-name">새 카테고리 만들기</span>
            </label>
            <div class="pp-share__cat-input-wrap" id="shareCatInputWrap">
                <input type="text" id="shareCatInput" class="pp-share__cat-input" maxlength="30" placeholder="카테고리 이름">
                <div class="pp-share__cat-dup-hint" id="shareCatDupHint"></div>
            </div>
            <label class="pp-share__cat-option">
                <input type="radio" name="share_cat" value="__existing__">
                <span class="pp-share__cat-name">기존 카테고리에 저장</span>
            </label>
            <div class="pp-share__cat-existing" id="shareCatExisting" style="display:none"></div>
        </div>
        <div class="pp-share__select-actions">
            <button type="button" class="pp-btn" id="shareCatSaveBtn">저장하기</button>
        </div>
    </div>
</div>

@php
    $placesJson = $collection->places->map(function($p) {
        return [
            'id' => $p->id,
            'name' => $p->display_name,
            'original_name' => $p->original_place_name,
            'address' => $p->address,
            'phone' => $p->phone,
            'opening_hours' => $p->opening_hours,
            'lat' => $p->latitude,
            'lng' => $p->longitude,
            'category_label' => $p->category_label,
            'memo' => $p->memo,
            'thumbnail_url' => $p->thumbnail_url,
            'external_place_id' => $p->external_place_id,
            'is_overseas' => (bool) $p->is_overseas,
        ];
    });
@endphp
<script src="https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId={{ config('services.naver_map.client_id') }}"></script>
<script>
(function() {
    const csrf = '{{ csrf_token() }}';
    const token = '{{ $collection->token }}';
    const isAuth = {{ Auth::check() ? 'true' : 'false' }};
    const shareTitle = @json($collection->title);
    const places = @json($placesJson);
    const IS_APP_WEBVIEW = /MYPINPICK/i.test(navigator.userAgent);
    const FALLBACK_URL = null;
    const _qp = new URLSearchParams(location.search);
    const _qpSelected = _qp.get('selected');
    const _qpAction = _qp.get('action');

    function getSelectedSortOrders() {
        var orders = [];
        selected.forEach(function(id) {
            var p = places.find(function(pl) { return pl.id === id; });
            if (p) {
                var idx = places.indexOf(p);
                orders.push(idx + 1);
            }
        });
        return orders.sort(function(a,b){return a-b;}).join(',');
    }

    function buildSchemeUrl(withAction) {
        var url = 'pinpick://s/' + token;
        var params = [];
        var orders = getSelectedSortOrders();
        if (orders) params.push('selected=' + orders);
        if (withAction) params.push('action=save');
        if (params.length) url += '?' + params.join('&');
        return url;
    }

    // --- 앱 배너 ---
    (function initAppBanner() {
        const banner = document.getElementById('ppAppBanner');
        if (!banner || IS_APP_WEBVIEW) return;
        if (sessionStorage.getItem('pp_app_banner_closed')) return;
        banner.style.display = '';
        document.getElementById('ppAppOpen').addEventListener('click', function() {
            location.href = buildSchemeUrl(false);
            setTimeout(function() {
                if (document.hidden) return;
                if (FALLBACK_URL) location.href = FALLBACK_URL;
            }, 1500);
        });
        document.getElementById('ppAppClose').addEventListener('click', function() {
            banner.style.display = 'none';
            sessionStorage.setItem('pp_app_banner_closed', '1');
        });
    })();
    const totalCount = places.length;

    const selected = new Set();
    let activeMarkerIdx = -1;

    // --- Map ---
    let map = null, markers = [];
    const pinSize = 28;

    function pinHtml(num, highlight) {
        const bg = highlight ? '#e67e22' : 'var(--pp-primary,#2b211e)';
        const scale = highlight ? 'transform:scale(1.25);' : '';
        return '<div style="background:' + bg + ';color:#fff;width:' + pinSize + 'px;height:' + pinSize + 'px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3);transition:transform .15s;' + scale + '">' + num + '</div>';
    }

    if (places.length && typeof naver !== 'undefined') {
        const bounds = new naver.maps.LatLngBounds();
        places.forEach(function(p) { if (p.lat && p.lng) bounds.extend(new naver.maps.LatLng(p.lat, p.lng)); });

        map = new naver.maps.Map('shareMap', {
            center: bounds.getCenter(),
            zoomControl: false, scaleControl: false, logoControl: false, mapDataControl: false,
        });

        if (places.filter(function(p){return p.lat && p.lng;}).length === 1) {
            var fp = places.find(function(p){return p.lat && p.lng;});
            map.setCenter(new naver.maps.LatLng(fp.lat, fp.lng));
            map.setZoom(15);
        } else {
            map.fitBounds(bounds, { top: 40, right: 40, bottom: 40, left: 40 });
        }

        places.forEach(function(p, i) {
            if (!p.lat || !p.lng) { markers.push(null); return; }
            var m = new naver.maps.Marker({
                position: new naver.maps.LatLng(p.lat, p.lng),
                map: map,
                icon: { content: pinHtml(i + 1, false), anchor: new naver.maps.Point(pinSize/2, pinSize/2) },
                zIndex: 100,
            });
            markers.push(m);
        });
    }

    function highlightPin(idx) {
        if (activeMarkerIdx >= 0 && markers[activeMarkerIdx]) {
            markers[activeMarkerIdx].setIcon({ content: pinHtml(activeMarkerIdx + 1, false), anchor: new naver.maps.Point(pinSize/2, pinSize/2) });
            markers[activeMarkerIdx].setZIndex(100);
        }
        if (idx >= 0 && markers[idx]) {
            markers[idx].setIcon({ content: pinHtml(idx + 1, true), anchor: new naver.maps.Point(pinSize/2, pinSize/2) });
            markers[idx].setZIndex(200);
        }
        activeMarkerIdx = idx;
    }

    // --- Card body/thumb tap → panTo + highlight ---
    document.querySelectorAll('.pp-share__card-body, .pp-share__card-thumb').forEach(function(el) {
        el.addEventListener('click', function() {
            var card = el.closest('.pp-share__card');
            var idx = parseInt(card.dataset.idx);
            var lat = parseFloat(card.dataset.lat);
            var lng = parseFloat(card.dataset.lng);
            if (!map || !lat || !lng) return;
            map.panTo(new naver.maps.LatLng(lat, lng), { duration: 300 });
            map.setZoom(16);
            highlightPin(idx);
        });
    });

    // --- Add toggle buttons ---
    var saveBtn = document.getElementById('shareSaveBtn');
    var selectAllBtn = document.getElementById('shareSelectAll');
    var allCards = document.querySelectorAll('.pp-share__card');

    document.querySelectorAll('.pp-share__card-add').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var id = parseInt(btn.dataset.placeId);
            var card = btn.closest('.pp-share__card');
            if (selected.has(id)) {
                selected.delete(id);
                card.classList.remove('is-selected');
            } else {
                selected.add(id);
                card.classList.add('is-selected');
            }
            updateCtaText();
            updateSelectAllBtn();
        });
    });

    // --- Select all toggle ---
    selectAllBtn.addEventListener('click', function() {
        if (selected.size === totalCount) {
            selected.clear();
            allCards.forEach(function(c) { c.classList.remove('is-selected'); });
        } else {
            places.forEach(function(p) { selected.add(p.id); });
            allCards.forEach(function(c) { c.classList.add('is-selected'); });
        }
        updateCtaText();
        updateSelectAllBtn();
    });

    function updateSelectAllBtn() {
        selectAllBtn.textContent = selected.size === totalCount ? '선택해제' : '전체선택';
    }

    // --- 쿼리 파라미터 selected 복원 ---
    if (_qpSelected) {
        var sortOrders = _qpSelected.split(',').map(Number).filter(function(n) { return n > 0; });
        sortOrders.forEach(function(order) {
            var idx = order - 1;
            if (idx >= 0 && idx < places.length) {
                var placeId = places[idx].id;
                selected.add(placeId);
                var card = document.querySelector('.pp-share__card[data-idx="' + idx + '"]');
                if (card) card.classList.add('is-selected');
            }
        });
        updateCtaText();
        updateSelectAllBtn();
    }

    function updateCtaText() {
        if (selected.size > 0) {
            saveBtn.textContent = selected.size + '개 장소 담기';
        } else {
            saveBtn.textContent = '이 장소들 내 핀픽에 담기';
        }
    }

    function getSelectedIds() {
        return selected.size > 0 ? Array.from(selected) : places.map(function(p){return p.id;});
    }

    // --- CTA ---
    saveBtn.addEventListener('click', function() {
        if (isAuth) { openCategorySheet(); }
        else { handleGuestSave(); }
    });

    // --- action=save 자동 시작 ---
    if (_qpAction === 'save' && isAuth) {
        setTimeout(function() { openCategorySheet(); }, 300);
    }

    // --- Close sheets ---
    var guestSheet = document.getElementById('shareGuestSheet');
    var catSheet = document.getElementById('shareCatSheet');

    document.querySelectorAll('[data-role="close"]').forEach(function(el) {
        el.addEventListener('click', function() {
            guestSheet.classList.remove('is-open');
            catSheet.classList.remove('is-open');
        });
    });

    // --- Guest save ---
    var GUEST_KEY = 'pinpick_guest_places';

    function handleGuestSave() {
        var guestPlaces = JSON.parse(localStorage.getItem(GUEST_KEY) || '[]');
        var remaining = 5 - guestPlaces.length;
        var ids = getSelectedIds();
        var toSave = places.filter(function(p) { return ids.indexOf(p.id) !== -1; });

        if (toSave.length > remaining && remaining >= 0) {
            document.getElementById('shareGuestInfo').textContent =
                '비로그인은 최대 5개까지 저장할 수 있어요 (남은 ' + remaining + '개). 로그인하면 전부 저장돼요';
            guestSheet.classList.add('is-open');
            return;
        }
        doGuestSave(toSave, guestPlaces, remaining);
    }

    document.getElementById('shareGuestSave').addEventListener('click', function() {
        var guestPlaces = JSON.parse(localStorage.getItem(GUEST_KEY) || '[]');
        var remaining = 5 - guestPlaces.length;
        var ids = getSelectedIds();
        var toSave = places.filter(function(p) { return ids.indexOf(p.id) !== -1; });
        guestSheet.classList.remove('is-open');
        doGuestSave(toSave, guestPlaces, remaining);
    });

    document.getElementById('shareGuestLogin').addEventListener('click', function() {
        location.href = '/login';
    });

    function doGuestSave(toSave, guestPlaces, remaining) {
        var added = 0;
        var skipped = 0;
        toSave.forEach(function(p) {
            if (added >= remaining) return;
            var exists = guestPlaces.some(function(g) {
                return (p.external_place_id && g.kakao_place_id === p.external_place_id) ||
                    (g.name === p.name && g.address === p.address);
            });
            if (exists) { skipped++; return; }

            guestPlaces.push({
                id: 'g_' + Date.now() + '_' + added,
                name: p.name, category_id: '', category_name: '', category_icon: '📌',
                category_label: shareTitle,
                phone: p.phone || '', opening_hours: p.opening_hours || [],
                address: p.address || '', road_address: p.address || '',
                lat: p.lat, lng: p.lng, memo: p.memo || '', status: 'planned',
                visited_at: '', is_overseas: !!p.is_overseas,
                original_name: p.original_name || '', kakao_place_id: p.external_place_id || '',
                thumbnail_url: p.thumbnail_url || '',
            });
            added++;
        });
        localStorage.setItem(GUEST_KEY, JSON.stringify(guestPlaces));
        var msg;
        if (added === 0 && skipped > 0) {
            msg = '이미 저장된 장소에요';
        } else if (added > 0 && skipped > 0) {
            msg = added + '개 저장 완료! (' + skipped + '개는 이미 저장됨)';
        } else {
            msg = added + '개 장소가 저장됐어요!';
        }
        showDone(msg);
    }

    // --- Auth save ---
    var userCats = @json($userCategories);
    var catInput = document.getElementById('shareCatInput');
    var catDupHint = document.getElementById('shareCatDupHint');
    var catExisting = document.getElementById('shareCatExisting');
    var catInputWrap = document.getElementById('shareCatInputWrap');

    function findMatchingCat(name) {
        var lower = name.trim().toLowerCase();
        return userCats.find(function(c) { return c.name.toLowerCase() === lower; });
    }

    function openCategorySheet() {
        catSheet.classList.add('is-open');
        catInput.value = shareTitle.slice(0, 30);
        checkDupCat();

        catExisting.innerHTML = '';
        userCats.forEach(function(c) {
            var div = document.createElement('label');
            div.className = 'pp-share__cat-option pp-share__cat-option--sub';
            div.innerHTML = '<input type="radio" name="share_existing_cat" value="' + c.id + '">' +
                '<span class="pp-share__cat-name">' + (c.icon || '📌') + ' ' + c.name + '</span>';
            catExisting.appendChild(div);
        });

        document.querySelector('input[name="share_cat"][value="__new__"]').checked = true;
        catInputWrap.style.display = '';
        catExisting.style.display = 'none';
    }

    function checkDupCat() {
        var match = findMatchingCat(catInput.value);
        if (match) {
            catDupHint.textContent = "기존 '" + match.name + "' 카테고리에 추가돼요";
            catDupHint.style.display = '';
        } else {
            catDupHint.style.display = 'none';
            catDupHint.textContent = '';
        }
    }

    catInput.addEventListener('input', checkDupCat);

    document.querySelectorAll('input[name="share_cat"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.value === '__new__') {
                catInputWrap.style.display = '';
                catExisting.style.display = 'none';
            } else {
                catInputWrap.style.display = 'none';
                catExisting.style.display = '';
            }
        });
    });

    document.getElementById('shareCatSaveBtn').addEventListener('click', async function() {
        var sel = document.querySelector('input[name="share_cat"]:checked');
        if (!sel) return;

        var ids = getSelectedIds();
        var body = { place_ids: ids };

        if (sel.value === '__new__') {
            var inputName = catInput.value.trim();
            if (!inputName) { catInput.focus(); return; }
            var dup = findMatchingCat(inputName);
            if (dup) {
                body.category_id = dup.id;
            } else {
                body.new_category_name = inputName.slice(0, 30);
            }
        } else {
            var exSel = document.querySelector('input[name="share_existing_cat"]:checked');
            if (!exSel) { showToast('카테고리를 선택해주세요'); return; }
            body.category_id = parseInt(exSel.value);
        }

        var btn = document.getElementById('shareCatSaveBtn');
        btn.disabled = true;
        btn.textContent = '저장 중...';

        try {
            var res = await fetch('/s/' + token + '/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            var data = await res.json();
            if (data.success) {
                catSheet.classList.remove('is-open');
                var msg;
                if (data.saved === 0 && data.skipped > 0) {
                    msg = '이미 저장된 장소에요';
                } else if (data.saved > 0 && data.skipped > 0) {
                    msg = data.saved + '개 저장 완료! (' + data.skipped + '개는 이미 저장된 장소)';
                } else {
                    msg = data.saved + '개 장소가 저장됐어요!';
                }
                showDone(msg);
            }
        } catch (e) {
            showToast('저장에 실패했어요. 다시 시도해주세요.');
        } finally {
            btn.disabled = false;
            btn.textContent = '저장하기';
        }
    });

    // --- Done state ---
    function showDone(msg) {
        showToast(msg);
        saveBtn.textContent = '핀픽에서 보기';
        saveBtn.className = 'pp-btn pp-share__cta-btn pp-share__cta-btn--done';
        saveBtn.onclick = function() {
            if (IS_APP_WEBVIEW) {
                location.href = '/';
                return;
            }
            var schemeUrl = buildSchemeUrl(true);
            if (/Android/i.test(navigator.userAgent)) {
                var intentPath = schemeUrl.replace('pinpick://', '');
                location.href = 'intent://' + intentPath +
                    '#Intent;scheme=pinpick;S.browser_fallback_url=' +
                    encodeURIComponent(location.origin + '/') + ';end';
            } else {
                location.href = schemeUrl;
                setTimeout(function() {
                    if (document.hidden) return;
                    location.href = '/';
                }, 1500);
            }
        };
        document.querySelectorAll('.pp-share__card-add').forEach(function(b) { b.style.display = 'none'; });
        selectAllBtn.style.display = 'none';
    }

    function openPinpick(path) {
        if (IS_APP_WEBVIEW) {
            location.href = path;
            return;
        }

        var appScheme = 'pinpick://' + path.replace(/^\//, '');
        var webFallback = location.origin + path;

        if (/Android/i.test(navigator.userAgent)) {
            location.href = 'intent://' + path.replace(/^\//, '') +
                '#Intent;scheme=pinpick;S.browser_fallback_url=' +
                encodeURIComponent(webFallback) + ';end';
            return;
        }

        if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
            location.href = appScheme;
            setTimeout(function() {
                if (document.hidden) return;
                if (FALLBACK_URL) location.href = FALLBACK_URL;
            }, 1500);
            return;
        }

        location.href = path;
    }

    function showToast(msg) {
        var t = document.getElementById('ppToast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'ppToast';
            t.className = 'pp-toast';
            document.body.appendChild(t);
        }
        t.textContent = msg;
        t.classList.add('is-show');
        setTimeout(function() { t.classList.remove('is-show'); }, 2500);
    }
})();
</script>
@endsection
