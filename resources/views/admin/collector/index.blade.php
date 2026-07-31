@extends('admin.layouts.app')
@section('title', '수집 도우미')

@section('content')
<style>
.cl-tabs { display: flex; gap: 0; margin-bottom: 20px; }
.cl-tab {
    flex: 1; padding: 10px; text-align: center; cursor: pointer;
    border: 1px solid var(--ad-border, #e0e0e0); background: #fafafa;
    font-size: 14px; font-weight: 600; color: var(--ad-text-sub); transition: .15s;
}
.cl-tab:first-child { border-radius: 8px 0 0 8px; }
.cl-tab:last-child { border-radius: 0 8px 8px 0; }
.cl-tab.is-active { background: var(--ad-primary, #1976d2); color: #fff; border-color: var(--ad-primary, #1976d2); }
.cl-panel { display: none; }
.cl-panel.is-active { display: block; }

.cl-input-row { display: flex; gap: 8px; align-items: center; }
.cl-input-row input, .cl-input-row textarea { flex: 1; }
.cl-textarea { width: 100%; min-height: 200px; resize: vertical; }

.cl-loading { text-align: center; padding: 40px; color: var(--ad-text-sub); }
.cl-loading::after {
    content: ''; display: inline-block; width: 20px; height: 20px;
    border: 2px solid #ccc; border-top-color: var(--ad-primary, #1976d2);
    border-radius: 50%; animation: clSpin .7s linear infinite; margin-left: 8px; vertical-align: middle;
}
@keyframes clSpin { to { transform: rotate(360deg); } }

.cl-result { margin-top: 20px; }
.cl-result-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px; }
.cl-result-header h3 { margin: 0; font-size: 15px; }
.cl-source-info { font-size: 12px; color: var(--ad-text-sub); margin-bottom: 16px; padding: 8px 12px; background: #f5f5f5; border-radius: 6px; }
.cl-source-info span { margin-right: 12px; }

.cl-meta-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px; }
.cl-meta-badge {
    font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px;
    background: #e8f5e9; color: #2e7d32;
}
.cl-meta-badge--warn { background: #fff3e0; color: #e65100; }
.cl-cat-warn { display: inline-block; font-size: 10px; font-weight: 600; padding: 1px 6px; border-radius: 4px; background: #ffebee; color: #c62828; margin-left: 4px; }

.cl-type-toggle { display: flex; gap: 0; margin-bottom: 16px; }
.cl-type-btn {
    padding: 6px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
    border: 1px solid var(--ad-border, #e0e0e0); background: #fafafa; color: var(--ad-text-sub);
}
.cl-type-btn:first-child { border-radius: 6px 0 0 6px; }
.cl-type-btn:last-child { border-radius: 0 6px 6px 0; }
.cl-type-btn.is-active { background: var(--ad-primary, #1976d2); color: #fff; border-color: var(--ad-primary, #1976d2); }

.cl-day-group { margin-bottom: 20px; }
.cl-day-header {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 12px; background: #f0f4ff; border-radius: 6px;
    font-size: 13px; font-weight: 700; color: #1565c0; margin-bottom: 8px;
}
.cl-day-header--unset { background: #fff3e0; color: #e65100; }

.cl-table-wrap { overflow-x: auto; }
.cl-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.cl-table th { background: #f9f9f9; font-weight: 600; text-align: left; padding: 8px 10px; border-bottom: 2px solid #e0e0e0; white-space: nowrap; }
.cl-table td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
.cl-table tr:hover { background: #fafafa; }
.cl-table tr.cl-no-match { opacity: .6; }

.cl-match-list { list-style: none; padding: 0; margin: 0; }
.cl-match-item { display: flex; align-items: center; gap: 6px; padding: 3px 0; cursor: pointer; }
.cl-match-item label { cursor: pointer; font-size: 12.5px; }
.cl-match-item .cl-addr { color: var(--ad-text-sub); font-size: 11.5px; }

.cl-research { display: inline-flex; align-items: center; gap: 4px; margin-top: 4px; }
.cl-research input { width: 140px; font-size: 12px; padding: 3px 6px; }
.cl-research button { font-size: 11px; padding: 3px 8px; }

.cl-context { font-size: 11.5px; color: #888; max-width: 200px; }

.cl-course-cell { white-space: nowrap; }
.cl-course-cell select { font-size: 12px; padding: 2px 4px; width: 60px; }
.cl-course-cell input { font-size: 12px; padding: 2px 4px; width: 50px; }
.cl-transit-input { font-size: 12px; padding: 2px 4px; width: 100px; margin-top: 2px; }

.cl-footer { margin-top: 20px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.cl-footer .cl-count { font-size: 13px; color: var(--ad-text-sub); }

.cl-cat-select { margin-right: 8px; }

.cl-empty { text-align: center; padding: 40px; color: var(--ad-text-sub); font-size: 14px; }
</style>

<div class="ad-card" style="padding: 20px;">
    <div class="cl-tabs">
        <div class="cl-tab is-active" onclick="switchTab('youtube')">유튜브 URL</div>
        <div class="cl-tab" onclick="switchTab('text')">텍스트 붙여넣기</div>
    </div>

    <div id="panelYoutube" class="cl-panel is-active">
        <div class="cl-input-row">
            <input class="ad-input" id="ytUrl" placeholder="유튜브 URL (예: https://www.youtube.com/watch?v=...)" style="flex:1">
            <button class="ad-btn ad-btn--primary" onclick="extractYoutube()" id="btnYt">추출하기</button>
        </div>
    </div>

    <div id="panelText" class="cl-panel">
        <textarea class="ad-input cl-textarea" id="rawText" placeholder="장소가 포함된 텍스트를 붙여넣으세요..."></textarea>
        <div style="margin-top:8px;text-align:right">
            <button class="ad-btn ad-btn--primary" onclick="extractText()" id="btnText">추출하기</button>
        </div>
    </div>

    <div id="loadingArea" class="cl-loading" style="display:none">장소를 추출하고 매칭하는 중...</div>
    <div id="resultArea"></div>
</div>

<script>
const CSRF = '{{ csrf_token() }}';
let extractedData = null;
let currentType = 'list';

function switchTab(tab) {
    document.querySelectorAll('.cl-tab').forEach((t, i) => {
        t.classList.toggle('is-active', (tab === 'youtube' ? i === 0 : i === 1));
    });
    document.getElementById('panelYoutube').classList.toggle('is-active', tab === 'youtube');
    document.getElementById('panelText').classList.toggle('is-active', tab === 'text');
}

function showLoading(show) {
    document.getElementById('loadingArea').style.display = show ? 'block' : 'none';
    if (show) document.getElementById('resultArea').innerHTML = '';
}

async function extractYoutube() {
    const url = document.getElementById('ytUrl').value.trim();
    if (!url) return alert('URL을 입력해주세요.');
    showLoading(true);
    try {
        const resp = await fetch('/admin/collector/extract-youtube', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ url }),
        });
        const data = await resp.json();
        if (!resp.ok) throw new Error(data.error || '오류가 발생했습니다.');
        extractedData = data;
        currentType = data.detected_type || 'list';
        renderResult(data);
    } catch (e) {
        alert(e.message);
    } finally {
        showLoading(false);
    }
}

async function extractText() {
    const text = document.getElementById('rawText').value.trim();
    if (!text || text.length < 10) return alert('텍스트를 10자 이상 입력해주세요.');
    showLoading(true);
    try {
        const resp = await fetch('/admin/collector/extract-text', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ text }),
        });
        const data = await resp.json();
        if (!resp.ok) throw new Error(data.error || '오류가 발생했습니다.');
        extractedData = data;
        currentType = data.detected_type || 'list';
        renderResult(data);
    } catch (e) {
        alert(e.message);
    } finally {
        showLoading(false);
    }
}

function setType(type) {
    currentType = type;
    document.querySelectorAll('.cl-type-btn').forEach(b => {
        b.classList.toggle('is-active', b.dataset.type === type);
    });
    const dr = document.getElementById('clDurationRow');
    if (dr) dr.style.display = type === 'course' ? 'flex' : 'none';
    if (extractedData) renderResult(extractedData);
}

function renderResult(data) {
    const area = document.getElementById('resultArea');
    if (!data.places || data.places.length === 0) {
        area.innerHTML = '<div class="cl-empty">추출된 장소가 없습니다.</div>';
        return;
    }

    const src = data.source || {};
    let sourceHtml = '';
    if (src.type === 'youtube') {
        sourceHtml = `<div class="cl-source-info">
            <span><b>채널:</b> ${esc(src.channel)}</span>
            <span><b>제목:</b> ${esc(src.title)}</span>
            <span><b>게시일:</b> ${esc(src.date)}</span>
        </div>`;
    }

    let metaHtml = '';
    if (data.has_chapters || data.has_pinned_comment) {
        metaHtml = '<div class="cl-meta-badges">';
        if (data.has_chapters) metaHtml += '<span class="cl-meta-badge">챕터 감지됨</span>';
        if (data.has_pinned_comment) metaHtml += '<span class="cl-meta-badge">댓글 수집됨</span>';
        metaHtml += '</div>';
    }

    const cats = @json(config('curation_categories'));
    let catOptions = '';
    for (const [k, v] of Object.entries(cats)) {
        const sel = k === 'travel' && currentType === 'course' ? 'selected' : '';
        catOptions += `<option value="${k}" ${sel}>${v.label}</option>`;
    }

    const typeToggle = `<div class="cl-type-toggle">
        <div class="cl-type-btn ${currentType === 'list' ? 'is-active' : ''}" data-type="list" onclick="setType('list')">목록형</div>
        <div class="cl-type-btn ${currentType === 'course' ? 'is-active' : ''}" data-type="course" onclick="setType('course')">코스형</div>
    </div>`;

    let draftNights = null, draftDays = null;
    if (currentType === 'course' && data.places.length > 0) {
        const maxDay = Math.max(...data.places.map(p => p.day || 0));
        if (maxDay > 0) {
            draftDays = maxDay;
            const title = (data.source?.title || '');
            const nightMatch = title.match(/(\d+)\s*박/);
            draftNights = nightMatch ? parseInt(nightMatch[1]) : Math.max(0, maxDay - 1);
        }
    }
    const durationRow = `<div id="clDurationRow" style="display:${currentType === 'course' ? 'flex' : 'none'};align-items:center;gap:6px;margin-bottom:12px;font-size:13px">
        <span style="font-weight:600">기간:</span>
        <input type="number" class="ad-input" id="clNights" min="0" max="30" value="${draftNights ?? ''}" placeholder="-" style="width:55px;padding:4px 6px;font-size:13px;text-align:center" oninput="clUpdateDuration()">
        <span>박</span>
        <input type="number" class="ad-input" id="clDaysField" min="1" max="31" value="${draftDays ?? ''}" placeholder="-" style="width:55px;padding:4px 6px;font-size:13px;text-align:center" oninput="clUpdateDuration()">
        <span>일</span>
        <span id="clDurationLabel" style="font-size:12px;color:#1976d2;font-weight:600;margin-left:4px"></span>
    </div>`;

    let bodyHtml = '';
    if (currentType === 'course') {
        bodyHtml = renderCourseView(data.places);
    } else {
        bodyHtml = renderListView(data.places);
    }

    area.innerHTML = `
        <div class="cl-result">
            ${sourceHtml}
            ${metaHtml}
            <div class="cl-result-header">
                <h3>추출 결과 (${data.places.length}곳)</h3>
                <label style="font-size:12px;cursor:pointer"><input type="checkbox" id="checkAll" checked onchange="toggleAll(this.checked)"> 전체선택</label>
            </div>
            ${typeToggle}
            ${durationRow}
            <div id="resultBody">${bodyHtml}</div>
            <div class="cl-footer">
                <select class="ad-input cl-cat-select" id="draftCategory">${catOptions}</select>
                <button class="ad-btn ad-btn--primary" onclick="createDraft()" id="btnDraft">선택한 <span id="selCount">${data.places.filter(p => p.matches?.length > 0).length}</span>곳으로 큐레이션 초안 만들기</button>
            </div>
        </div>`;

    document.querySelectorAll('.cl-check').forEach(cb => cb.addEventListener('change', updateCount));
    clUpdateDuration();
}

function renderListView(places) {
    let rows = '';
    places.forEach((p, idx) => {
        rows += buildRow(p, idx, false);
    });
    return `<div class="cl-table-wrap"><table class="cl-table">
        <thead><tr><th style="width:30px"></th><th>추출명</th><th>매칭 결과</th><th>맥락</th></tr></thead>
        <tbody>${rows}</tbody></table></div>`;
}

function renderCourseView(places) {
    const groups = {};
    const unset = [];
    places.forEach((p, idx) => {
        p._idx = idx;
        if (p.day != null) {
            if (!groups[p.day]) groups[p.day] = [];
            groups[p.day].push(p);
        } else {
            unset.push(p);
        }
    });

    let html = '';
    const dayKeys = Object.keys(groups).map(Number).sort((a, b) => a - b);

    dayKeys.forEach(day => {
        const items = groups[day].sort((a, b) => (a.order || 0) - (b.order || 0));
        html += `<div class="cl-day-group">
            <div class="cl-day-header">${day}일차 (${items.length}곳)</div>
            <div class="cl-table-wrap"><table class="cl-table">
                <thead><tr><th style="width:30px"></th><th>순서</th><th>추출명</th><th>매칭 결과</th><th>이동</th><th>맥락</th></tr></thead>
                <tbody>${items.map(p => buildRow(p, p._idx, true)).join('')}</tbody>
            </table></div></div>`;
    });

    if (unset.length > 0) {
        html += `<div class="cl-day-group">
            <div class="cl-day-header cl-day-header--unset">일자 미지정 (${unset.length}곳)</div>
            <div class="cl-table-wrap"><table class="cl-table">
                <thead><tr><th style="width:30px"></th><th>일자</th><th>순서</th><th>추출명</th><th>매칭 결과</th><th>이동</th><th>맥락</th></tr></thead>
                <tbody>${unset.map(p => buildRow(p, p._idx, true, true)).join('')}</tbody>
            </table></div></div>`;
    }

    if (!html) html = renderListView(places);
    return html;
}

const SUSPECT_CATS = ['전기차충전소','충전소','주차장','자동차정비','주유소','은행','병원','부동산','약국','편의점','ATM'];
function isSuspectCat(label) {
    if (!label) return false;
    return SUSPECT_CATS.some(s => label.includes(s));
}

function buildRow(p, idx, isCourse, showDaySelect) {
    const hasMatch = p.matches && p.matches.length > 0;
    const rowClass = hasMatch ? '' : 'cl-no-match';

    let matchHtml = '';
    if (hasMatch) {
        p.matches.forEach((m, mi) => {
            const checked = mi === 0 ? 'checked' : '';
            const catWarn = isSuspectCat(m.category_label) ? ` <span class="cl-cat-warn" title="${esc(m.category_label)}">이 카테고리가 맞나요?</span>` : '';
            matchHtml += `<div class="cl-match-item">
                <input type="radio" name="match_${idx}" value="${mi}" ${checked} id="m_${idx}_${mi}">
                <label for="m_${idx}_${mi}">${esc(m.place_name)} <span class="cl-addr">${esc(m.address)}</span>${catWarn}</label>
            </div>`;
        });
    } else {
        matchHtml = '<span style="color:#C62828;font-size:12px">매칭 실패</span>';
    }
    matchHtml += `<div class="cl-research">
        <input class="ad-input" placeholder="재검색" id="rs_${idx}" onkeydown="if(event.key==='Enter'){event.preventDefault();reSearch(${idx})}">
        <button class="ad-btn ad-btn--sm" onclick="reSearch(${idx})">검색</button>
    </div>`;

    let courseCells = '';
    if (isCourse) {
        if (showDaySelect) {
            courseCells += `<td class="cl-course-cell"><select class="ad-input" data-idx="${idx}" data-field="day" onchange="updatePlaceField(${idx},'day',this.value)">
                <option value="">미지정</option>
                ${[1,2,3,4,5,6,7].map(d => `<option value="${d}" ${p.day == d ? 'selected' : ''}>${d}일차</option>`).join('')}
            </select></td>`;
        }
        courseCells += `<td class="cl-course-cell"><input class="ad-input" type="number" min="1" value="${p.order || ''}" data-idx="${idx}" data-field="order" onchange="updatePlaceField(${idx},'order',this.value)" placeholder="#"></td>`;
        courseCells += `<td class="cl-course-cell"><input class="ad-input cl-transit-input" value="${esc(p.transit_hint || '')}" data-idx="${idx}" data-field="transit_hint" onchange="updatePlaceField(${idx},'transit_hint',this.value)" placeholder="예: 차로 20분"></td>`;
    }

    return `<tr class="${rowClass}" id="row_${idx}">
        <td><input type="checkbox" class="cl-check" data-idx="${idx}" ${hasMatch ? 'checked' : ''}></td>
        ${courseCells}
        <td>${esc(p.extracted_name)}${p.region_hint ? ' <span style="color:#999;font-size:11px">(' + esc(p.region_hint) + ')</span>' : ''}</td>
        <td class="cl-match-cell" id="matches_${idx}">${matchHtml}</td>
        ${isCourse && !showDaySelect ? '' : ''}<td class="cl-context">${esc(p.mention_context)}</td>
    </tr>`;
}

function updatePlaceField(idx, field, value) {
    if (!extractedData || !extractedData.places[idx]) return;
    if (field === 'day' || field === 'order') {
        extractedData.places[idx][field] = value ? parseInt(value) : null;
    } else {
        extractedData.places[idx][field] = value || null;
    }
}

function toggleAll(checked) {
    document.querySelectorAll('.cl-check').forEach(cb => { cb.checked = checked; });
    updateCount();
}

function updateCount() {
    const n = document.querySelectorAll('.cl-check:checked').length;
    const el = document.getElementById('selCount');
    if (el) el.textContent = n;
}

async function reSearch(idx) {
    const input = document.getElementById('rs_' + idx);
    const query = input.value.trim();
    if (!query) return;

    try {
        const resp = await fetch('/admin/collector/search-kakao?query=' + encodeURIComponent(query), {
            headers: { 'X-CSRF-TOKEN': CSRF },
        });
        const data = await resp.json();
        const results = data.results || [];

        if (extractedData && extractedData.places[idx]) {
            extractedData.places[idx].matches = results.slice(0, 3);
        }

        const cell = document.getElementById('matches_' + idx);
        let html = '';
        if (results.length > 0) {
            results.slice(0, 3).forEach((m, mi) => {
                const checked = mi === 0 ? 'checked' : '';
                const catWarn = isSuspectCat(m.category_label) ? ` <span class="cl-cat-warn" title="${esc(m.category_label)}">이 카테고리가 맞나요?</span>` : '';
                html += `<div class="cl-match-item">
                    <input type="radio" name="match_${idx}" value="${mi}" ${checked} id="m_${idx}_${mi}">
                    <label for="m_${idx}_${mi}">${esc(m.place_name)} <span class="cl-addr">${esc(m.address)}</span>${catWarn}</label>
                </div>`;
            });
            document.querySelector(`#row_${idx} .cl-check`).checked = true;
            document.getElementById('row_' + idx).classList.remove('cl-no-match');
        } else {
            html = '<span style="color:#C62828;font-size:12px">매칭 실패</span>';
        }
        html += `<div class="cl-research">
            <input class="ad-input" placeholder="재검색" id="rs_${idx}" onkeydown="if(event.key==='Enter'){event.preventDefault();reSearch(${idx})}">
            <button class="ad-btn ad-btn--sm" onclick="reSearch(${idx})">검색</button>
        </div>`;
        cell.innerHTML = html;
        updateCount();
    } catch (e) {
        alert('검색 오류: ' + e.message);
    }
}

function clUpdateDuration() {
    const el = document.getElementById('clDurationLabel');
    if (!el) return;
    const n = parseInt(document.getElementById('clNights')?.value);
    const d = parseInt(document.getElementById('clDaysField')?.value);
    if (isNaN(d)) { el.textContent = ''; return; }
    const nn = isNaN(n) ? 0 : n;
    if (nn === 0 && d === 1) el.textContent = '당일치기';
    else if (nn === 0) el.textContent = '무박 ' + d + '일';
    else el.textContent = nn + '박 ' + d + '일';
}

async function createDraft() {
    if (!extractedData) return;

    const checked = document.querySelectorAll('.cl-check:checked');
    if (checked.length === 0) return alert('장소를 1개 이상 선택해주세요.');

    const src = extractedData.source || {};
    const places = [];

    checked.forEach(cb => {
        const idx = parseInt(cb.dataset.idx);
        const p = extractedData.places[idx];
        if (!p || !p.matches || p.matches.length === 0) return;

        const radio = document.querySelector(`input[name="match_${idx}"]:checked`);
        const mi = radio ? parseInt(radio.value) : 0;
        const match = p.matches[mi];
        if (!match) return;

        const place = {
            place_name: match.place_name,
            address: match.address,
            latitude: match.latitude,
            longitude: match.longitude,
            category_label: match.category_label || null,
            external_place_id: match.external_place_id || null,
            phone: match.phone || null,
            editor_note: p.mention_context || null,
            day_number: p.day || null,
            sort_order: p.order || null,
            transit_hint: p.transit_hint || null,
        };

        if (src.type === 'youtube') {
            place.source_channel = src.channel || null;
            place.source_url = src.url || null;
            place.source_date = src.date || null;
        }

        places.push(place);
    });

    if (places.length === 0) return alert('유효한 장소가 없습니다.');

    places.sort((a, b) => {
        const da = a.day_number ?? 9999;
        const db = b.day_number ?? 9999;
        if (da !== db) return da - db;
        const oa = a.sort_order ?? 9999;
        const ob = b.sort_order ?? 9999;
        return oa - ob;
    });

    const btn = document.getElementById('btnDraft');
    btn.disabled = true;
    btn.textContent = '생성 중...';

    try {
        const resp = await fetch('/admin/collector/create-draft', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({
                places,
                source_title: src.title || null,
                category: document.getElementById('draftCategory').value,
                draft_type: currentType,
                nights: document.getElementById('clNights')?.value !== '' ? parseInt(document.getElementById('clNights').value) : null,
                days: document.getElementById('clDaysField')?.value !== '' ? parseInt(document.getElementById('clDaysField').value) : null,
            }),
        });
        const data = await resp.json();
        if (!resp.ok) throw new Error(data.error || '오류가 발생했습니다.');
        if (data.redirect) {
            window.location.href = data.redirect;
        }
    } catch (e) {
        alert(e.message);
        btn.disabled = false;
        btn.textContent = `선택한 ${checked.length}곳으로 큐레이션 초안 만들기`;
    }
}

function esc(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
</script>
@endsection
