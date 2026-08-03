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

.cl-search-ctrl {
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    padding: 10px 14px; background: #f0f7ff; border-radius: 8px; margin-bottom: 16px;
    border: 1px solid #bbdefb; font-size: 13px;
}
.cl-search-ctrl select, .cl-search-ctrl input { font-size: 13px; padding: 4px 8px; }
.cl-search-ctrl .cl-vp-status { font-size: 11px; color: #2e7d32; font-weight: 600; }
.cl-search-ctrl .cl-vp-status--none { color: #e65100; }

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

.cl-match-item { display: flex; align-items: center; gap: 6px; padding: 3px 0; cursor: pointer; }
.cl-match-item label { cursor: pointer; font-size: 12.5px; }
.cl-match-item .cl-addr { color: var(--ad-text-sub); font-size: 11.5px; }

.cl-match-stage {
    display: inline-block; font-size: 10px; font-weight: 600; padding: 1px 6px;
    border-radius: 4px; margin-left: 4px;
}
.cl-match-stage--ko { background: #e8f5e9; color: #2e7d32; }
.cl-match-stage--local { background: #e3f2fd; color: #1565c0; }
.cl-match-stage--en { background: #fce4ec; color: #c62828; }
.cl-match-stage--context { background: #fff3e0; color: #e65100; }

.cl-research { display: inline-flex; align-items: center; gap: 4px; margin-top: 4px; }
.cl-research input { width: 140px; font-size: 12px; padding: 3px 6px; }
.cl-research button { font-size: 11px; padding: 3px 8px; }

.cl-names-sub { font-size: 10.5px; color: #999; line-height: 1.3; margin-top: 2px; }

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
let rawPlaces = null;
let currentType = 'list';

let searchCtrl = {
    isOverseas: false,
    countryCode: null,
    regionHint: null,
    viewport: null,
    vpName: null,
};

const COUNTRY_OPTIONS = {
    'JP':'일본','VN':'베트남','TH':'태국','SG':'싱가포르','MY':'말레이시아',
    'ID':'인도네시아','PH':'필리핀','TW':'대만','CN':'중국','HK':'홍콩',
    'MO':'마카오','US':'미국','CA':'캐나다','AU':'호주','NZ':'뉴질랜드',
    'GB':'영국','FR':'프랑스','DE':'독일','IT':'이탈리아','ES':'스페인',
    'PT':'포르투갈','CH':'스위스','AT':'오스트리아','CZ':'체코','HR':'크로아티아',
    'GR':'그리스','TR':'튀르키예','IN':'인도','KH':'캄보디아','MX':'멕시코',
};

const STAGE_LABELS = {
    ko: '한국어로 매칭됨',
    local: '현지어로 매칭됨',
    en: '영문으로 매칭됨',
    context: '맥락 검색으로 매칭됨',
};
const STAGE_CLASS = { ko: 'ko', local: 'local', en: 'en', context: 'context' };

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
        rawPlaces = JSON.parse(JSON.stringify(data.places));
        currentType = data.detected_type || 'list';

        searchCtrl.isOverseas = !!data.is_overseas;
        searchCtrl.countryCode = data.detected_country || null;
        searchCtrl.regionHint = data.detected_region || null;
        searchCtrl.viewport = data.viewport || null;
        searchCtrl.vpName = data.viewport ? (data.detected_region || null) : null;

        if (searchCtrl.isOverseas && searchCtrl.regionHint && !searchCtrl.viewport) {
            await resolveViewport();
        }
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
        rawPlaces = JSON.parse(JSON.stringify(data.places));
        currentType = data.detected_type || 'list';

        searchCtrl.isOverseas = !!data.is_overseas;
        searchCtrl.countryCode = data.detected_country || null;
        searchCtrl.regionHint = data.detected_region || null;
        searchCtrl.viewport = data.viewport || null;
        searchCtrl.vpName = data.viewport ? (data.detected_region || null) : null;

        if (searchCtrl.isOverseas && searchCtrl.regionHint && !searchCtrl.viewport) {
            await resolveViewport();
        }
        renderResult(data);
    } catch (e) {
        alert(e.message);
    } finally {
        showLoading(false);
    }
}

async function resolveViewport() {
    const region = searchCtrl.regionHint;
    if (!region) { searchCtrl.viewport = null; searchCtrl.vpName = null; return; }
    try {
        const resp = await fetch('/admin/collector/geocode-region', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ region, country_code: searchCtrl.countryCode }),
        });
        const data = await resp.json();
        if (data.found && data.viewport) {
            searchCtrl.viewport = data.viewport;
            searchCtrl.vpName = data.name;
        } else {
            searchCtrl.viewport = null;
            searchCtrl.vpName = null;
        }
    } catch (e) {
        searchCtrl.viewport = null;
        searchCtrl.vpName = null;
    }
}

function onOverseasToggle(val) {
    searchCtrl.isOverseas = val;
    updateSearchCtrlUI();
}

function onCountryChange(val) {
    searchCtrl.countryCode = val || null;
}

async function onRegionChange() {
    const input = document.getElementById('clRegionInput');
    searchCtrl.regionHint = input.value.trim() || null;
    if (searchCtrl.regionHint) {
        await resolveViewport();
    } else {
        searchCtrl.viewport = null;
        searchCtrl.vpName = null;
    }
    updateVpStatus();
}

function updateSearchCtrlUI() {
    const overseasRow = document.getElementById('clOverseasRow');
    if (overseasRow) overseasRow.style.display = searchCtrl.isOverseas ? 'flex' : 'none';
    updateVpStatus();
}

function updateVpStatus() {
    const el = document.getElementById('clVpStatus');
    if (!el) return;
    if (searchCtrl.viewport && searchCtrl.vpName) {
        el.className = 'cl-vp-status';
        el.textContent = esc(searchCtrl.vpName) + ' · 검색 범위 확인됨';
    } else if (searchCtrl.regionHint) {
        el.className = 'cl-vp-status cl-vp-status--none';
        el.textContent = '범위 미확인';
    } else {
        el.textContent = '';
    }
}

async function rematchAll() {
    if (!rawPlaces || rawPlaces.length === 0) return;
    const btn = document.getElementById('btnRematch');
    btn.disabled = true; btn.textContent = '재매칭 중...';

    const placesForRematch = rawPlaces.map(p => ({
        name: p.extracted_name,
        name_local: p.name_local || null,
        name_en: p.name_en || null,
        region_hint: p.region_hint || searchCtrl.regionHint || null,
        mention_context: p.mention_context || '',
        day: p.day,
        order: p.order,
        transit_hint: p.transit_hint || null,
    }));

    try {
        const resp = await fetch('/admin/collector/rematch-all', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({
                places: placesForRematch,
                is_overseas: searchCtrl.isOverseas,
                country_code: searchCtrl.countryCode,
                region_hint: searchCtrl.regionHint,
                viewport: searchCtrl.viewport,
            }),
        });
        const data = await resp.json();
        if (data.places) {
            extractedData.places = data.places;
            rawPlaces = JSON.parse(JSON.stringify(data.places));
            renderResult(extractedData);
        }
    } catch (e) {
        alert('재매칭 오류: ' + e.message);
    } finally {
        btn.disabled = false; btn.textContent = '전체 재매칭';
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

    let metaHtml = '<div class="cl-meta-badges">';
    if (data.has_chapters) metaHtml += '<span class="cl-meta-badge">챕터 감지됨</span>';
    if (data.has_pinned_comment) metaHtml += '<span class="cl-meta-badge">댓글 수집됨</span>';
    if (data.detected_country && data.detected_country !== 'KR') {
        metaHtml += `<span class="cl-meta-badge" style="background:#e3f2fd;color:#1565c0">해외: ${esc(COUNTRY_OPTIONS[data.detected_country] || data.detected_country)}</span>`;
    }
    if (data.detected_region) {
        metaHtml += `<span class="cl-meta-badge" style="background:#fce4ec;color:#ad1457">지역: ${esc(data.detected_region)}</span>`;
    }
    metaHtml += '</div>';

    let countryOpts = '<option value="">선택</option>';
    for (const [k, v] of Object.entries(COUNTRY_OPTIONS)) {
        const sel = searchCtrl.countryCode === k ? 'selected' : '';
        countryOpts += `<option value="${k}" ${sel}>${v} (${k})</option>`;
    }

    const searchCtrlHtml = `<div class="cl-search-ctrl">
        <span style="font-weight:600">검색 기준:</span>
        <label style="cursor:pointer"><input type="radio" name="clOverseas" value="0" ${!searchCtrl.isOverseas ? 'checked' : ''} onchange="onOverseasToggle(false)"> 국내</label>
        <label style="cursor:pointer"><input type="radio" name="clOverseas" value="1" ${searchCtrl.isOverseas ? 'checked' : ''} onchange="onOverseasToggle(true)"> 해외</label>
        <div id="clOverseasRow" style="display:${searchCtrl.isOverseas ? 'flex' : 'none'};align-items:center;gap:6px;flex-wrap:wrap">
            <select class="ad-input" onchange="onCountryChange(this.value)" style="width:130px">${countryOpts}</select>
            <input class="ad-input" id="clRegionInput" value="${esc(searchCtrl.regionHint || '')}" placeholder="기준 지역 (예: 오키나와)" style="width:140px">
            <button class="ad-btn ad-btn--sm" onclick="onRegionChange()">범위 확인</button>
            <span id="clVpStatus" class="${searchCtrl.viewport ? 'cl-vp-status' : 'cl-vp-status cl-vp-status--none'}">${searchCtrl.vpName ? esc(searchCtrl.vpName) + ' · 검색 범위 확인됨' : ''}</span>
        </div>
        <button class="ad-btn ad-btn--sm" id="btnRematch" onclick="rematchAll()" style="margin-left:auto">전체 재매칭</button>
    </div>`;

    const matchedCount = data.places.filter(p => p.matches && p.matches.length > 0).length;
    const totalCount = data.places.length;
    const stageStats = {};
    data.places.forEach(p => { if (p.match_stage) stageStats[p.match_stage] = (stageStats[p.match_stage] || 0) + 1; });
    let statsHtml = `<span style="font-size:12px;color:var(--ad-text-sub);margin-left:8px">매칭 ${matchedCount}/${totalCount}`;
    for (const [s, cnt] of Object.entries(stageStats)) {
        statsHtml += ` · ${STAGE_LABELS[s] ? s.toUpperCase() : s}:${cnt}`;
    }
    statsHtml += '</span>';

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
            ${searchCtrlHtml}
            <div class="cl-result-header">
                <h3>추출 결과 (${data.places.length}곳)${statsHtml}</h3>
                <label style="font-size:12px;cursor:pointer"><input type="checkbox" id="checkAll" checked onchange="toggleAll(this.checked)"> 전체선택</label>
            </div>
            ${typeToggle}
            ${durationRow}
            <div id="resultBody">${bodyHtml}</div>
            <div class="cl-footer">
                <select class="ad-input cl-cat-select" id="draftCategory">${catOptions}</select>
                <button class="ad-btn ad-btn--primary" onclick="createDraft()" id="btnDraft">선택한 <span id="selCount">${matchedCount}</span>곳으로 큐레이션 초안 만들기</button>
            </div>
        </div>`;

    document.querySelectorAll('.cl-check').forEach(cb => cb.addEventListener('change', updateCount));
    clUpdateDuration();
}

function renderListView(places) {
    let rows = '';
    places.forEach((p, idx) => { rows += buildRow(p, idx, false); });
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

const SUSPECT_CATS_KR = ['전기차충전소','충전소','주차장','자동차정비','주유소','은행','병원','부동산'];
const SUSPECT_TYPES_GOOGLE = ['electric_vehicle_charging_station','parking','gas_station','car_repair','bank','hospital','real_estate_agency'];
function isSuspectCat(label) {
    if (!label) return false;
    const lower = label.toLowerCase();
    if (SUSPECT_CATS_KR.some(s => label.includes(s))) return true;
    if (SUSPECT_TYPES_GOOGLE.some(s => lower.includes(s))) return true;
    return false;
}

function buildRow(p, idx, isCourse, showDaySelect) {
    const hasMatch = p.matches && p.matches.length > 0;
    const rowClass = hasMatch ? '' : 'cl-no-match';

    let matchHtml = '';
    if (hasMatch) {
        if (p.match_stage && STAGE_LABELS[p.match_stage]) {
            matchHtml += `<span class="cl-match-stage cl-match-stage--${STAGE_CLASS[p.match_stage]}">${STAGE_LABELS[p.match_stage]}</span> `;
        }
        p.matches.forEach((m, mi) => {
            const checked = mi === 0 ? 'checked' : '';
            const catWarn = isSuspectCat(m.category_label) ? ` <span class="cl-cat-warn" title="${esc(m.category_label)}">카테고리 확인</span>` : '';
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

    let nameHtml = esc(p.extracted_name);
    if (p.region_hint) nameHtml += ` <span style="color:#999;font-size:11px">(${esc(p.region_hint)})</span>`;
    const altNames = [p.name_local, p.name_en].filter(Boolean);
    if (altNames.length > 0) {
        nameHtml += `<div class="cl-names-sub">${altNames.map(n => esc(n)).join(' / ')}</div>`;
    }

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
        <td>${nameHtml}</td>
        <td class="cl-match-cell" id="matches_${idx}">${matchHtml}</td>
        <td class="cl-context">${esc(p.mention_context)}</td>
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
        let results = [];
        if (searchCtrl.isOverseas) {
            const params = new URLSearchParams({ query, country_code: searchCtrl.countryCode || '' });
            if (searchCtrl.viewport) params.set('viewport', JSON.stringify(searchCtrl.viewport));
            const resp = await fetch('/admin/collector/search-google?' + params, {
                headers: { 'X-CSRF-TOKEN': CSRF },
            });
            const data = await resp.json();
            results = data.results || [];
        } else {
            const resp = await fetch('/admin/collector/search-kakao?query=' + encodeURIComponent(query), {
                headers: { 'X-CSRF-TOKEN': CSRF },
            });
            const data = await resp.json();
            results = data.results || [];
        }

        if (extractedData && extractedData.places[idx]) {
            extractedData.places[idx].matches = results.slice(0, 3);
            extractedData.places[idx].match_stage = results.length > 0 ? 'ko' : null;
        }

        const cell = document.getElementById('matches_' + idx);
        let html = '';
        if (results.length > 0) {
            results.slice(0, 3).forEach((m, mi) => {
                const checked = mi === 0 ? 'checked' : '';
                const catWarn = isSuspectCat(m.category_label) ? ` <span class="cl-cat-warn" title="${esc(m.category_label)}">카테고리 확인</span>` : '';
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
            google_place_id: match.google_place_id || null,
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
        return (a.sort_order ?? 9999) - (b.sort_order ?? 9999);
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
        if (data.redirect) window.location.href = data.redirect;
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
