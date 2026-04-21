@extends('layouts.app')
@section('title', '카테고리 관리')
@section('app_class', 'pp-app--form')

@section('header')
<header class="pp-header">
    <a href="{{ route('mypage') }}" class="pp-header__icon pp-header__back" aria-label="뒤로">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </a>
    <div class="pp-header__title">카테고리 관리</div>
    <div class="pp-header__spacer"></div>
</header>
@endsection

@section('content')
<div class="pp-catmgr">
    <p class="pp-catmgr__hint">카테고리 이름을 편집하거나 위/아래 버튼으로 순서를 바꾼 뒤 하단 저장 버튼을 눌러주세요.</p>

    <ul class="pp-catmgr__list" id="ppCatList">
        @foreach($categories as $c)
            <li class="pp-catmgr__item" data-id="{{ $c->id }}" data-default="{{ $c->is_default ? '1' : '0' }}" data-original-name="{{ $c->name }}">
                <button type="button" class="pp-catmgr__up" aria-label="위로">↑</button>
                <button type="button" class="pp-catmgr__down" aria-label="아래로">↓</button>
                <input type="text" class="pp-catmgr__name" value="{{ $c->name }}" maxlength="30">
                <button type="button" class="pp-catmgr__del" aria-label="삭제">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
            </li>
        @endforeach
    </ul>

    <button type="button" class="pp-catmgr__add" id="ppCatAdd">＋ 새 카테고리 추가</button>
</div>

<div class="pp-form-submit">
    <button type="button" class="pp-btn pp-btn--block" id="ppCatSave">저장</button>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const list = document.getElementById('ppCatList');
    const saveBtn = document.getElementById('ppCatSave');

    // 로컬 변경 추적
    const pendingNew = [];      // [{ tempId, name }]
    const pendingDelete = [];   // [id, ...]
    let nextTempId = -1;

    function toast(msg, err){
        const t = document.createElement('div');
        t.textContent = msg;
        t.style.cssText = 'position:fixed;left:50%;bottom:90px;transform:translateX(-50%);background:' + (err?'#b4443a':'#2b211e') + ';color:#fff;padding:11px 18px;border-radius:999px;font-size:13.5px;font-weight:600;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.22);';
        document.body.appendChild(t);
        setTimeout(()=>t.remove(), 2000);
    }

    function refreshArrows(){
        const items = Array.from(list.children);
        items.forEach((it, i) => {
            it.querySelector('.pp-catmgr__up').disabled = i === 0;
            it.querySelector('.pp-catmgr__down').disabled = i === items.length - 1;
        });
    }
    refreshArrows();

    list.addEventListener('click', (e) => {
        const li = e.target.closest('.pp-catmgr__item');
        if (!li) return;

        if (e.target.closest('.pp-catmgr__up') || e.target.closest('.pp-catmgr__down')) {
            const dir = e.target.closest('.pp-catmgr__up') ? -1 : 1;
            const items = Array.from(list.children);
            const idx = items.indexOf(li);
            const swap = idx + dir;
            if (swap < 0 || swap >= items.length) return;
            if (dir === -1) list.insertBefore(li, items[swap]);
            else list.insertBefore(items[swap], li);
            refreshArrows();
            li.classList.add('pp-catmgr__item--moved');
            setTimeout(()=>li.classList.remove('pp-catmgr__item--moved'), 500);
            return;
        }

        if (e.target.closest('.pp-catmgr__del')) {
            const name = li.querySelector('.pp-catmgr__name').value;
            if (!confirm(`'${name}' 카테고리를 삭제할까요? (저장 버튼을 눌러야 반영됩니다)`)) return;
            const id = +li.dataset.id;
            if (id > 0) pendingDelete.push(id);
            else {
                const idx = pendingNew.findIndex(n => n.tempId === id);
                if (idx >= 0) pendingNew.splice(idx, 1);
            }
            li.remove();
            refreshArrows();
        }
    });

    document.getElementById('ppCatAdd').addEventListener('click', () => {
        const name = prompt('새 카테고리 이름을 입력하세요');
        if (!name || !name.trim()) return;
        const trimmed = name.trim();
        const tempId = nextTempId--;
        pendingNew.push({ tempId, name: trimmed });
        const li = document.createElement('li');
        li.className = 'pp-catmgr__item';
        li.dataset.id = String(tempId);
        li.dataset.default = '0';
        li.dataset.originalName = '';
        li.innerHTML = `
            <button type="button" class="pp-catmgr__up" aria-label="위로">↑</button>
            <button type="button" class="pp-catmgr__down" aria-label="아래로">↓</button>
            <input type="text" class="pp-catmgr__name" value="${trimmed.replace(/"/g,'&quot;')}" maxlength="30">
            <button type="button" class="pp-catmgr__del" aria-label="삭제">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>`;
        list.prepend(li);
        refreshArrows();
    });

    async function doSave(){
        saveBtn.disabled = true;
        saveBtn.textContent = '저장 중...';
        try {
            // 1) 삭제
            for (const id of pendingDelete) {
                const r = await fetch(`/api/categories/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                });
                const j = await r.json();
                if (!j.ok) throw new Error(j.error || '삭제 실패');
            }

            // 2) 신규 추가 (tempId → 실제 id 매핑)
            const tempIdMap = {};
            for (const item of pendingNew) {
                const r = await fetch('{{ route('api.categories.store') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ name: item.name })
                });
                const j = await r.json();
                if (!j.ok) throw new Error('추가 실패');
                tempIdMap[item.tempId] = j.item.id;
                const li = list.querySelector(`[data-id="${item.tempId}"]`);
                if (li) li.dataset.id = String(j.item.id);
            }

            // 3) 이름 변경 (기존 항목 중 이름이 바뀐 것만)
            for (const li of list.children) {
                const id = +li.dataset.id;
                if (id <= 0) continue; // 방금 만든건 제외
                const curName = li.querySelector('.pp-catmgr__name').value.trim();
                const origName = li.dataset.originalName || '';
                if (curName && curName !== origName) {
                    const r = await fetch(`/api/categories/${id}`, {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ name: curName })
                    });
                    const j = await r.json();
                    if (!j.ok) throw new Error('이름 변경 실패');
                    li.dataset.originalName = curName;
                }
            }

            // 4) 순서 일괄 반영
            const ids = Array.from(list.children).map(li => +li.dataset.id).filter(n => n > 0);
            const r = await fetch('{{ route('api.categories.reorder') }}', {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ order: ids })
            });
            const j = await r.json();
            if (!j.ok) throw new Error('순서 저장 실패');

            pendingNew.length = 0;
            pendingDelete.length = 0;
            toast('저장되었어요');
        } catch (err) {
            toast(err.message || '저장 실패', true);
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = '저장';
        }
    }

    saveBtn.addEventListener('click', doSave);

    // 빈 이름 방지
    list.addEventListener('blur', (e) => {
        const input = e.target.closest && e.target.closest('.pp-catmgr__name');
        if (!input) return;
        if (!input.value.trim()) {
            input.value = input.closest('.pp-catmgr__item').dataset.originalName || '';
        }
    }, true);
})();
</script>
@endpush
