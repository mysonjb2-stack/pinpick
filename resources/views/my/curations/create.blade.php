@extends('layouts.app')
@section('page_title', '리스트 만들기 | 핀픽')
@section('noindex', true)
@section('hide_nav', true)

@section('header')
<header class="pp-header">
    <button type="button" class="pp-header__back" id="mcBack" aria-label="뒤로">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <div class="pp-header__title" id="mcHeaderTitle">리스트 만들기</div>
    <div class="pp-header__step" id="mcHeaderStep"></div>
</header>
@endsection

@push('head')
<style>
.pp-app { padding-bottom: 0 !important; }
.pp-header__step { font-size: 12px; color: var(--pp-text-sub); padding-right: 16px; }

/* Step container */
.mc-step { display: none; padding: 20px 16px 100px; }
.mc-step.is-active { display: block; }
.mc-step__title { font-size: 18px; font-weight: 800; color: var(--pp-text); margin-bottom: 4px; }
.mc-step__sub { font-size: 13px; color: var(--pp-text-sub); margin-bottom: 20px; }

/* Progress bar */
.mc-progress { height: 3px; background: var(--pp-line); }
.mc-progress__bar { height: 100%; background: var(--pp-primary); transition: width .3s; }

/* Inputs */
.mc-field { margin-bottom: 16px; }
.mc-field__label { display: block; font-size: 13px; font-weight: 600; color: var(--pp-text); margin-bottom: 6px; }
.mc-field__count { float: right; font-weight: 400; color: var(--pp-text-sub); }
.mc-input {
  width: 100%; padding: 10px 12px; border: 1px solid var(--pp-line);
  border-radius: 10px; font-size: 14px; color: var(--pp-text);
  background: var(--pp-bg); outline: none; box-sizing: border-box;
}
.mc-input:focus { border-color: var(--pp-primary); }
.mc-textarea { resize: none; min-height: 70px; line-height: 1.5; }

/* Category chips */
.mc-cats { display: flex; flex-wrap: wrap; gap: 8px; }
.mc-cat {
  padding: 10px 18px; border-radius: 12px; border: 1.5px solid var(--pp-line);
  background: var(--pp-bg); font-size: 14px; font-weight: 600;
  color: var(--pp-text); cursor: pointer; transition: all .15s;
}
.mc-cat.is-selected {
  border-color: var(--pp-primary); background: var(--pp-primary-light, #FFF8F0);
  color: var(--pp-primary);
}

/* Source category selector */
.mc-src-cats { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
.mc-src-cat {
  padding: 6px 12px; border-radius: 20px; border: 1px solid var(--pp-line);
  background: var(--pp-bg); font-size: 13px; color: var(--pp-text-sub);
  cursor: pointer; white-space: nowrap;
}
.mc-src-cat.is-active { background: var(--pp-text); color: #fff; border-color: var(--pp-text); }

/* Place selection */
.mc-places { display: flex; flex-direction: column; gap: 6px; }
.mc-place {
  display: flex; align-items: center; gap: 10px; padding: 10px 12px;
  border-radius: 12px; border: 1.5px solid var(--pp-line);
  background: var(--pp-bg); cursor: pointer; transition: border-color .15s;
}
.mc-place.is-selected { border-color: var(--pp-primary); background: var(--pp-primary-light, #FFF8F0); }
.mc-place__check {
  width: 22px; height: 22px; border-radius: 6px; border: 1.5px solid var(--pp-line);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  transition: all .15s;
}
.mc-place.is-selected .mc-place__check {
  background: var(--pp-primary); border-color: var(--pp-primary);
}
.mc-place__check svg { display: none; }
.mc-place.is-selected .mc-place__check svg { display: block; }
.mc-place__thumb {
  width: 44px; height: 44px; border-radius: 8px; object-fit: cover;
  flex-shrink: 0; background: var(--pp-chip-bg);
}
.mc-place__thumb--empty {
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; color: var(--pp-text-sub);
}
.mc-place__info { flex: 1; min-width: 0; }
.mc-place__name { font-size: 14px; font-weight: 600; color: var(--pp-text); }
.mc-place__addr { font-size: 12px; color: var(--pp-text-sub); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mc-place__photo-badge {
  font-size: 10px; color: var(--pp-primary); background: var(--pp-primary-light, #FFF8F0);
  padding: 1px 5px; border-radius: 4px; margin-left: 4px;
}

/* Comment inputs */
.mc-comments { display: flex; flex-direction: column; gap: 10px; }
.mc-comment {
  padding: 12px; border-radius: 12px; border: 1px solid var(--pp-line); background: var(--pp-bg);
}
.mc-comment__name { font-size: 14px; font-weight: 600; color: var(--pp-text); margin-bottom: 6px; }
.mc-comment__input {
  width: 100%; border: none; outline: none; font-size: 13px; color: var(--pp-text);
  background: transparent; padding: 0; box-sizing: border-box;
}
.mc-comment__input::placeholder { color: var(--pp-text-sub); }
.mc-comment__count { text-align: right; font-size: 11px; color: var(--pp-text-sub); margin-top: 4px; }

/* Preview */
.mc-preview { border-radius: 14px; border: 1px solid var(--pp-line); overflow: hidden; background: var(--pp-bg-card, #fff); }
.mc-preview__head { padding: 14px 16px; }
.mc-preview__title { font-size: 16px; font-weight: 800; color: var(--pp-text); }
.mc-preview__desc { font-size: 13px; color: var(--pp-text-sub); margin-top: 4px; }
.mc-preview__cat { display: inline-block; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 6px; background: var(--pp-chip-bg); color: var(--pp-text-sub); margin-top: 6px; }
.mc-preview__places { padding: 0 16px 14px; }
.mc-preview__place {
  display: flex; gap: 10px; padding: 10px 0;
  border-bottom: 1px solid var(--pp-line);
}
.mc-preview__place:last-child { border-bottom: none; }
.mc-preview__pthumb { width: 50px; height: 50px; border-radius: 8px; object-fit: cover; flex-shrink: 0; }
.mc-preview__pinfo { flex: 1; min-width: 0; }
.mc-preview__pname { font-size: 14px; font-weight: 600; color: var(--pp-text); }
.mc-preview__paddr { font-size: 12px; color: var(--pp-text-sub); margin-top: 2px; }
.mc-preview__pnote { font-size: 12px; color: var(--pp-primary); margin-top: 4px; font-style: italic; }

/* Terms */
.mc-terms { margin-top: 16px; padding: 14px; border-radius: 12px; background: var(--pp-chip-bg); }
.mc-terms__warn { font-size: 12px; color: #E65100; line-height: 1.5; margin-bottom: 10px; }
.mc-terms__check { display: flex; align-items: flex-start; gap: 8px; font-size: 13px; color: var(--pp-text); cursor: pointer; line-height: 1.5; }
.mc-terms__check input { margin-top: 3px; accent-color: var(--pp-primary); }

/* Validation hint */
.mc-hint {
  padding: 10px 14px; border-radius: 10px; font-size: 12px;
  background: #FFF3E0; color: #E65100; margin-bottom: 14px; line-height: 1.5;
}
.mc-hint--ok { background: #E8F5E9; color: #2E7D32; }

/* Bottom bar */
.mc-bottom {
  position: fixed; bottom: 0; left: 0; right: 0;
  padding: 10px 16px calc(10px + env(safe-area-inset-bottom, 0px));
  background: var(--pp-bg); border-top: 1px solid var(--pp-line);
  display: flex; gap: 8px; z-index: 100;
  max-width: 480px; margin: 0 auto;
}
.mc-bottom__btn {
  flex: 1; padding: 12px; border-radius: 12px; font-size: 14px; font-weight: 700;
  border: none; cursor: pointer; text-align: center;
}
.mc-bottom__btn--ghost { background: var(--pp-chip-bg); color: var(--pp-text); }
.mc-bottom__btn--primary { background: var(--pp-primary); color: #fff; }
.mc-bottom__btn:disabled { opacity: .4; cursor: not-allowed; }
</style>
@endpush

@section('content')
<div class="mc-progress"><div class="mc-progress__bar" id="mcProgressBar" style="width:20%"></div></div>

<!-- Step 1: 제목/설명 -->
<div class="mc-step is-active" data-step="1">
    <div class="mc-step__title">리스트 정보</div>
    <div class="mc-step__sub">제목과 설명을 입력해주세요</div>
    <div class="mc-field">
        <label class="mc-field__label">제목 <span class="mc-field__count"><span id="mcTitleCount">0</span>/40</span></label>
        <input type="text" class="mc-input" id="mcTitle" maxlength="40" placeholder="예: 분당 주민 추천 맛집 리스트">
    </div>
    <div class="mc-field">
        <label class="mc-field__label">설명 (선택) <span class="mc-field__count"><span id="mcDescCount">0</span>/200</span></label>
        <textarea class="mc-input mc-textarea" id="mcDesc" maxlength="200" placeholder="리스트에 대한 간단한 설명"></textarea>
    </div>
</div>

<!-- Step 2: 카테고리 -->
<div class="mc-step" data-step="2">
    <div class="mc-step__title">카테고리 선택</div>
    <div class="mc-step__sub">리스트의 대표 카테고리를 선택해주세요</div>
    <div class="mc-cats" id="mcCats">
        @foreach($curationCategories as $slug => $cat)
        <div class="mc-cat" data-cat="{{ $slug }}">{{ $cat['icon'] }} {{ $cat['label'] }}</div>
        @endforeach
    </div>
</div>

<!-- Step 3: 장소 선택 -->
<div class="mc-step" data-step="3">
    <div class="mc-step__title">장소 선택</div>
    <div class="mc-step__sub">리스트에 담을 장소를 골라주세요 (3곳 이상)</div>
    <div class="mc-src-cats" id="mcSrcCats">
        <div class="mc-src-cat is-active" data-cat-id="all">전체</div>
        @foreach($categories as $cat)
        <div class="mc-src-cat" data-cat-id="{{ $cat->id }}">{{ $cat->icon ?? '📌' }} {{ $cat->name }}</div>
        @endforeach
    </div>
    <div id="mcPlaceHint"></div>
    <div class="mc-places" id="mcPlaces"></div>
</div>

<!-- Step 4: 장소별 코멘트 -->
<div class="mc-step" data-step="4">
    <div class="mc-step__title">코멘트 (선택)</div>
    <div class="mc-step__sub">각 장소에 한마디를 남겨보세요</div>
    <div class="mc-comments" id="mcComments"></div>
</div>

<!-- Step 5: 미리보기 & 제출 -->
<div class="mc-step" data-step="5">
    <div class="mc-step__title">미리보기</div>
    <div class="mc-step__sub">내용을 확인하고 제출해주세요</div>
    <div class="mc-preview" id="mcPreview"></div>

    <div class="mc-terms">
        <div class="mc-terms__warn">
            연예인·방송인 이름, 프로그램명은 제목에 쓸 수 없어요.<br>
            허위 정보나 타인의 권리를 침해하는 콘텐츠는 삭제될 수 있습니다.
        </div>
        <label class="mc-terms__check">
            <input type="checkbox" id="mcTerms">
            위 내용을 확인했으며, 게시에 따른 책임이 작성자에게 있음에 동의합니다.
        </label>
    </div>
</div>

<div class="mc-bottom">
    <button type="button" class="mc-bottom__btn mc-bottom__btn--ghost" id="mcPrev" style="display:none">이전</button>
    <button type="button" class="mc-bottom__btn mc-bottom__btn--primary" id="mcNext">다음</button>
</div>

<script>
(function() {
    const csrf = '{{ csrf_token() }}';
    const preselectedCatId = '{{ $preselectedCategoryId ?? '' }}';
    const suggestTitle = '{{ addslashes($suggestTitle ?? '') }}';
    const TOTAL_STEPS = 5;
    let step = 1;
    let allPlaces = [];
    let placesLoaded = false;

    // State
    let formTitle = '';
    let formDesc = '';
    let formCategory = '';
    let selectedPlaceIds = [];
    let comments = {};

    const $progress = document.getElementById('mcProgressBar');
    const $prev = document.getElementById('mcPrev');
    const $next = document.getElementById('mcNext');
    const $headerStep = document.getElementById('mcHeaderStep');

    function esc(s) {
        const d = document.createElement('div'); d.textContent = s; return d.innerHTML;
    }

    function showStep(n) {
        step = n;
        document.querySelectorAll('.mc-step').forEach(el => el.classList.toggle('is-active', +el.dataset.step === n));
        $progress.style.width = (n / TOTAL_STEPS * 100) + '%';
        $prev.style.display = n > 1 ? '' : 'none';
        $headerStep.textContent = n + ' / ' + TOTAL_STEPS;

        if (n === TOTAL_STEPS) {
            $next.textContent = '제출하기';
            renderPreview();
            updateSubmitState();
        } else {
            $next.textContent = '다음';
            $next.disabled = false;
        }

        if (n === 3 && !placesLoaded) loadPlaces();
        if (n === 4) renderComments();

        validateStep(n);
    }

    function validateStep(n) {
        if (n === 1) {
            $next.disabled = !document.getElementById('mcTitle').value.trim();
        } else if (n === 2) {
            $next.disabled = !formCategory;
        } else if (n === 3) {
            $next.disabled = selectedPlaceIds.length < 3;
            updatePlaceHint();
        }
    }

    function updatePlaceHint() {
        const hint = document.getElementById('mcPlaceHint');
        const count = selectedPlaceIds.length;
        const hasPhoto = selectedPlaceIds.some(id => {
            const p = allPlaces.find(x => x.id === id);
            return p && p.has_photo;
        });
        if (count < 3) {
            hint.innerHTML = '<div class="mc-hint">' + count + '곳 선택됨 · 3곳 이상 선택해주세요</div>';
        } else if (!hasPhoto) {
            hint.innerHTML = '<div class="mc-hint">사진이 있는 장소가 1곳 이상 필요합니다</div>';
        } else {
            hint.innerHTML = '<div class="mc-hint mc-hint--ok">' + count + '곳 선택 완료</div>';
        }
    }

    function updateSubmitState() {
        const terms = document.getElementById('mcTerms').checked;
        const hasPhoto = selectedPlaceIds.some(id => {
            const p = allPlaces.find(x => x.id === id);
            return p && p.has_photo;
        });
        $next.disabled = !terms || selectedPlaceIds.length < 3 || !hasPhoto;
    }

    // Step 1 inputs
    const $title = document.getElementById('mcTitle');
    const $desc = document.getElementById('mcDesc');
    $title.addEventListener('input', () => {
        formTitle = $title.value;
        document.getElementById('mcTitleCount').textContent = $title.value.length;
        validateStep(1);
    });
    $desc.addEventListener('input', () => {
        formDesc = $desc.value;
        document.getElementById('mcDescCount').textContent = $desc.value.length;
    });

    // Step 2 category
    document.getElementById('mcCats').addEventListener('click', e => {
        const cat = e.target.closest('.mc-cat');
        if (!cat) return;
        document.querySelectorAll('.mc-cat').forEach(c => c.classList.remove('is-selected'));
        cat.classList.add('is-selected');
        formCategory = cat.dataset.cat;
        validateStep(2);
    });

    // Step 3 load places
    let prefillDone = false;
    function loadPlaces(catId) {
        const url = catId && catId !== 'all'
            ? '/api/my/places?category_id=' + catId
            : '/api/my/places';
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                if (!catId || catId === 'all') allPlaces = data;
                placesLoaded = true;
                if (preselectedCatId && !prefillDone && catId === preselectedCatId) {
                    prefillDone = true;
                    selectedPlaceIds = data.map(p => p.id);
                    allPlaces = data;
                    fetch('/api/my/places', { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(all => { allPlaces = all; });
                }
                renderPlaces(data);
            });
    }

    function renderPlaces(places) {
        const $el = document.getElementById('mcPlaces');
        $el.innerHTML = places.map(p => {
            const sel = selectedPlaceIds.includes(p.id) ? ' is-selected' : '';
            const thumb = p.photos && p.photos[0]
                ? '<img class="mc-place__thumb" src="' + esc(p.photos[0]) + '" alt="" loading="lazy">'
                : '<div class="mc-place__thumb mc-place__thumb--empty">📍</div>';
            const photoBadge = p.has_photo ? '<span class="mc-place__photo-badge">사진</span>' : '';
            return '<div class="mc-place' + sel + '" data-id="' + p.id + '">'
                + '<div class="mc-place__check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>'
                + thumb
                + '<div class="mc-place__info"><div class="mc-place__name">' + esc(p.name) + photoBadge + '</div>'
                + '<div class="mc-place__addr">' + esc(p.address || '') + '</div></div></div>';
        }).join('');
        updatePlaceHint();
    }

    document.getElementById('mcPlaces').addEventListener('click', e => {
        const el = e.target.closest('.mc-place');
        if (!el) return;
        const id = +el.dataset.id;
        const idx = selectedPlaceIds.indexOf(id);
        if (idx >= 0) {
            selectedPlaceIds.splice(idx, 1);
            el.classList.remove('is-selected');
        } else {
            selectedPlaceIds.push(id);
            el.classList.add('is-selected');
        }
        validateStep(3);
    });

    document.getElementById('mcSrcCats').addEventListener('click', e => {
        const el = e.target.closest('.mc-src-cat');
        if (!el) return;
        document.querySelectorAll('.mc-src-cat').forEach(c => c.classList.remove('is-active'));
        el.classList.add('is-active');
        loadPlaces(el.dataset.catId);
    });

    // Step 4 comments
    function renderComments() {
        const $el = document.getElementById('mcComments');
        $el.innerHTML = selectedPlaceIds.map(id => {
            const p = allPlaces.find(x => x.id === id);
            if (!p) return '';
            return '<div class="mc-comment">'
                + '<div class="mc-comment__name">' + esc(p.name) + '</div>'
                + '<input class="mc-comment__input" data-id="' + id + '" maxlength="100" placeholder="이 장소에 대한 한마디 (선택)" value="' + esc(comments[id] || '') + '">'
                + '<div class="mc-comment__count"><span class="mc-comment__len">' + (comments[id] || '').length + '</span>/100</div>'
                + '</div>';
        }).join('');
    }

    document.getElementById('mcComments').addEventListener('input', e => {
        if (!e.target.classList.contains('mc-comment__input')) return;
        const id = +e.target.dataset.id;
        comments[id] = e.target.value;
        const counter = e.target.parentElement.querySelector('.mc-comment__len');
        if (counter) counter.textContent = e.target.value.length;
    });

    // Step 5 preview
    function renderPreview() {
        const catConfig = {!! json_encode($curationCategories, JSON_UNESCAPED_UNICODE) !!};
        const catLabel = catConfig[formCategory] ? catConfig[formCategory].label : formCategory;

        let placesHtml = selectedPlaceIds.map(id => {
            const p = allPlaces.find(x => x.id === id);
            if (!p) return '';
            const thumb = p.photos && p.photos[0]
                ? '<img class="mc-preview__pthumb" src="' + esc(p.photos[0]) + '" alt="">'
                : '';
            const note = comments[id] ? '<div class="mc-preview__pnote">"' + esc(comments[id]) + '"</div>' : '';
            return '<div class="mc-preview__place">'
                + thumb
                + '<div class="mc-preview__pinfo">'
                + '<div class="mc-preview__pname">' + esc(p.name) + '</div>'
                + '<div class="mc-preview__paddr">' + esc(p.address || '') + '</div>'
                + note
                + '</div></div>';
        }).join('');

        document.getElementById('mcPreview').innerHTML =
            '<div class="mc-preview__head">'
            + '<div class="mc-preview__title">' + esc(formTitle) + '</div>'
            + (formDesc ? '<div class="mc-preview__desc">' + esc(formDesc) + '</div>' : '')
            + '<span class="mc-preview__cat">' + esc(catLabel) + '</span>'
            + '</div>'
            + '<div class="mc-preview__places">' + placesHtml + '</div>';
    }

    document.getElementById('mcTerms').addEventListener('change', updateSubmitState);

    // Navigation
    $next.addEventListener('click', () => {
        if (step < TOTAL_STEPS) {
            showStep(step + 1);
        } else {
            submitCuration();
        }
    });
    $prev.addEventListener('click', () => {
        if (step > 1) showStep(step - 1);
    });
    document.getElementById('mcBack').addEventListener('click', () => {
        if (step > 1) {
            showStep(step - 1);
        } else {
            if (formTitle || selectedPlaceIds.length) {
                if (!confirm('작성 중인 내용이 사라져요. 나갈까요?')) return;
            }
            history.back();
        }
    });

    function submitCuration() {
        $next.disabled = true;
        $next.textContent = '제출 중...';

        const body = {
            title: formTitle,
            description: formDesc || null,
            category: formCategory,
            place_ids: selectedPlaceIds,
            comments: comments,
            terms_agreed: true,
        };

        fetch('{{ route("my.curations.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(body),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.href = '{{ route("my.curations") }}';
            } else {
                alert(data.error || '제출 실패');
                $next.disabled = false;
                $next.textContent = '제출하기';
            }
        })
        .catch(() => {
            alert('네트워크 오류');
            $next.disabled = false;
            $next.textContent = '제출하기';
        });
    }

    // Init
    if (suggestTitle) {
        $title.value = suggestTitle;
        formTitle = suggestTitle;
        document.getElementById('mcTitleCount').textContent = suggestTitle.length;
    }
    if (preselectedCatId) {
        setTimeout(() => {
            const srcCat = document.querySelector('.mc-src-cat[data-cat-id="' + preselectedCatId + '"]');
            if (srcCat) srcCat.click();
        }, 100);
    }
    showStep(1);
})();
</script>
@endsection
