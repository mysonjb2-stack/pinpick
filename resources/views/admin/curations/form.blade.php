@extends('admin.layouts.app')
@section('title', $curation ? '큐레이션 수정' : '큐레이션 생성')

@push('head')
<style>
.cur-form { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 900px) { .cur-form { grid-template-columns: 1fr; } }
.cur-left, .cur-right { display: flex; flex-direction: column; gap: 16px; }
.cur-cover { display: none; }
.cur-places { display: flex; flex-direction: column; gap: 8px; }
.cur-place { background: var(--ad-card); border: 1px solid var(--ad-border); border-radius: 8px; padding: 12px 14px; display: flex; gap: 12px; align-items: flex-start; }
.cur-place__num { width: 24px; height: 24px; border-radius: 50%; background: var(--ad-primary); color: #fff; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px; }
.cur-place__body { flex: 1; min-width: 0; }
.cur-place__name { font-weight: 600; font-size: 14px; }
.cur-place__addr { font-size: 12px; color: var(--ad-text-sub); margin-top: 2px; }
.cur-place__source { font-size: 12px; color: var(--ad-primary); margin-top: 4px; }
.cur-place__meta { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px; }
.cur-place__meta input { font-size: 12px; padding: 3px 8px; border: 1px solid var(--ad-border); border-radius: 4px; }
.cur-place__meta input.short { width: 120px; }
.cur-place__meta input.url { width: 200px; }
.cur-place__actions { display: flex; gap: 4px; flex-shrink: 0; }
.cur-search-wrap { position: relative; }
.cur-search-results { position: absolute; top: 100%; left: 0; right: 0; background: var(--ad-card); border: 1px solid var(--ad-border); border-radius: 8px; max-height: 300px; overflow-y: auto; z-index: 10; display: none; box-shadow: 0 4px 12px rgba(0,0,0,.1); }
.cur-search-results.is-open { display: block; }
.cur-sr { padding: 10px 14px; cursor: pointer; border-bottom: 1px solid var(--ad-border); font-size: 13px; }
.cur-sr:hover { background: #f8fafc; }
.cur-sr:last-child { border-bottom: none; }
.cur-sr__name { font-weight: 600; }
.cur-sr__addr { color: var(--ad-text-sub); font-size: 12px; }
.cur-preview-link { display: inline-flex; align-items: center; gap: 4px; }
textarea.ad-input { min-height: 80px; resize: vertical; }
.cur-place__photos { display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; align-items: center; }
.cur-place__photo { position: relative; width: 56px; height: 56px; border-radius: 6px; overflow: hidden; }
.cur-place__photo img { width: 100%; height: 100%; object-fit: cover; }
.cur-place__photo-del { position: absolute; top: 1px; right: 1px; width: 18px; height: 18px; border-radius: 50%; background: rgba(0,0,0,.6); color: #fff; font-size: 12px; line-height: 18px; text-align: center; border: none; cursor: pointer; padding: 0; }
.cur-place__photo-add { width: 56px; height: 56px; border-radius: 6px; border: 1.5px dashed var(--ad-border); background: #f8fafc; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--ad-text-sub); cursor: pointer; flex-shrink: 0; }
.cur-place__photo-add:hover { border-color: var(--ad-primary); color: var(--ad-primary); }
.cur-place__photos.is-dragover { background: #e8f4fd; outline: 2px dashed var(--ad-primary); outline-offset: -2px; border-radius: 8px; padding: 4px; }
.cur-place__photo { cursor: grab; transition: opacity .15s, transform .15s; }
.cur-place__photo.is-drag-src { opacity: .35; }
.cur-place__photo.is-drag-over { transform: scale(1.1); outline: 2px solid var(--ad-primary); outline-offset: 1px; border-radius: 6px; }
.cur-place__photo-loading { width: 56px; height: 56px; border-radius: 6px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 18px; animation: pulse-load 1s ease-in-out infinite; }
@keyframes pulse-load { 0%,100%{opacity:1} 50%{opacity:.5} }
.cur-place__url-row { display: flex; gap: 4px; width: 100%; margin-top: 4px; }
.cur-place__url-row textarea { flex: 1; min-width: 0; font-size: 12px; padding: 4px 8px; border: 1px solid var(--ad-border); border-radius: 4px; background: var(--ad-card); resize: vertical; min-height: 28px; max-height: 80px; line-height: 1.4; font-family: inherit; }
.cur-place__url-row textarea:focus { border-color: var(--ad-primary); outline: none; }
.cur-place__url-row .ad-btn { align-self: flex-end; }
.cur-place__hint { width: 100%; font-size: 11px; color: var(--ad-text-sub); margin-top: 2px; opacity: .7; }

/* Tour image modal */
.tour-modal { display:none; position:fixed; inset:0; z-index:9000; align-items:center; justify-content:center; background:rgba(0,0,0,.5); }
.tour-modal.is-open { display:flex; }
.tour-modal__panel { background:#fff; border-radius:12px; width:90%; max-width:640px; max-height:80vh; display:flex; flex-direction:column; }
.tour-modal__head { display:flex; align-items:center; justify-content:space-between; padding:16px 18px; border-bottom:1px solid var(--ad-border); }
.tour-modal__head h3 { margin:0; font-size:16px; }
.tour-modal__close { border:none; background:none; font-size:22px; cursor:pointer; color:var(--ad-text-sub); }
.tour-modal__body { flex:1; overflow-y:auto; padding:14px 18px; }
.tour-modal__grid { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
.tour-modal__img { position:relative; cursor:pointer; border-radius:8px; overflow:hidden; border:2px solid transparent; aspect-ratio:1; }
.tour-modal__img.is-selected { border-color:var(--ad-primary); }
.tour-modal__img img { width:100%; height:100%; object-fit:cover; }
.tour-modal__img-check { position:absolute; top:4px; right:4px; width:22px; height:22px; border-radius:50%; background:var(--ad-primary); color:#fff; font-size:14px; display:none; align-items:center; justify-content:center; }
.tour-modal__img.is-selected .tour-modal__img-check { display:flex; }
.tour-modal__source { position:absolute; bottom:0; left:0; right:0; padding:2px 6px; background:rgba(0,0,0,.6); color:#fff; font-size:10px; text-align:center; }
.tour-modal__foot { padding:12px 18px; border-top:1px solid var(--ad-border); display:flex; justify-content:space-between; align-items:center; }
.tour-modal__count { font-size:13px; color:var(--ad-text-sub); }
.tour-modal__empty { text-align:center; padding:40px 20px; color:var(--ad-text-sub); }
.tour-btn { font-size:11px; padding:3px 8px; border:1px solid var(--ad-primary); color:var(--ad-primary); border-radius:4px; background:#fff; cursor:pointer; white-space:nowrap; }
.tour-btn:hover { background:var(--ad-primary); color:#fff; }
.tour-btn:disabled { opacity:.4; cursor:default; }

/* Google Place ID */
.cur-place__gid { display:flex; align-items:center; gap:4px; margin-top:3px; }
.cur-place__gid-tag { font-size:11px; padding:1px 6px; border-radius:3px; background:#e8f5e9; color:#2e7d32; font-family:monospace; }
.cur-place__gid-btn { font-size:11px; padding:1px 6px; border:1px solid #ccc; border-radius:3px; background:#fff; cursor:pointer; color:var(--ad-text-sub); }
.cur-place__gid-btn:hover { border-color:var(--ad-primary); color:var(--ad-primary); }
.cur-place__gid-btn--del { color:#c00; border-color:#e0b0b0; }
.cur-place__gid-btn--del:hover { background:#fee; }

/* Opening hours display */
.cur-hours { font-size:12px; color:var(--ad-text-sub); line-height:1.6; max-width:220px; }
.cur-hours__line { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cur-hours__edit { font-size:11px; color:var(--ad-primary); cursor:pointer; border:none; background:none; padding:0; margin-top:2px; }
.cur-hours__raw { display:none; }

/* Drag handle & reorder */
.cur-place { position: relative; transition: box-shadow .15s, opacity .15s; }
.cur-place__handle { cursor: grab; font-size: 18px; color: var(--ad-text-sub); line-height: 1; user-select: none; flex-shrink: 0; padding: 2px 4px 2px 0; touch-action: none; }
.cur-place__handle:active { cursor: grabbing; }
.cur-place.is-place-dragging { opacity: .4; box-shadow: none; }
.cur-place.is-place-over { box-shadow: 0 -3px 0 0 var(--ad-primary); }
.cur-place__order-btns { display: flex; flex-direction: column; gap: 2px; flex-shrink: 0; }
.cur-place__order-btn { width: 22px; height: 22px; border: 1px solid var(--ad-border); border-radius: 4px; background: var(--ad-card); font-size: 13px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--ad-text-sub); padding: 0; }
.cur-place__order-btn:hover:not(:disabled) { border-color: var(--ad-primary); color: var(--ad-primary); }
.cur-place__order-btn:disabled { opacity: .3; cursor: default; }
.cur-day-divider { display:flex; align-items:center; justify-content:space-between; padding:8px 14px; background:#f0f4f8; border-radius:6px; font-size:13px; font-weight:600; color:#475569; margin:6px 0 2px; }
.cur-day-divider__count { font-weight:400; font-size:11px; color:var(--ad-text-sub); }
.cur-duration-wrap { display:none; }
.cur-duration-wrap.is-visible { display:block; }
.cur-duration-row { display:flex; align-items:center; gap:6px; }
.cur-duration-row input[type="number"] { width:60px; padding:6px 8px; font-size:13px; text-align:center; }
.cur-duration-row span.unit { font-size:13px; color:var(--ad-text-sub); }
.cur-duration-label { font-size:12px; color:var(--ad-primary); font-weight:600; margin-left:4px; white-space:nowrap; }
.cur-duration-err { font-size:11px; color:#C62828; margin-top:4px; }
.cur-place__name-input { font-weight:600; font-size:14px; border:none; background:transparent; padding:0; width:100%; outline:none; }
.cur-place__name-input:hover, .cur-place__name-input:focus { background:#f8fafc; border-radius:4px; padding:2px 4px; margin:-2px -4px; }
.cur-place__addr-input { font-size:12px; color:var(--ad-text-sub); border:none; background:transparent; padding:0; width:100%; outline:none; margin-top:2px; }
.cur-place__addr-input:hover, .cur-place__addr-input:focus { background:#f8fafc; border-radius:4px; padding:2px 4px; margin:-2px -4px; }
.cur-replace-btn { font-size:11px; padding:2px 8px; border:1px solid #93c5fd; border-radius:4px; background:#eff6ff; color:#1d4ed8; cursor:pointer; white-space:nowrap; }
.cur-replace-btn:hover { background:#dbeafe; border-color:#60a5fa; }
.cur-replace-modal { display:none; position:fixed; inset:0; z-index:9000; align-items:center; justify-content:center; background:rgba(0,0,0,.5); }
.cur-replace-modal.is-open { display:flex; }
.cur-replace-modal__panel { background:#fff; border-radius:12px; width:90%; max-width:560px; max-height:80vh; display:flex; flex-direction:column; }
.cur-replace-modal__head { display:flex; align-items:center; justify-content:space-between; padding:16px 18px; border-bottom:1px solid var(--ad-border); }
.cur-replace-modal__head h3 { margin:0; font-size:15px; }
.cur-replace-modal__close { border:none; background:none; font-size:20px; cursor:pointer; color:var(--ad-text-sub); }
.cur-replace-modal__body { flex:1; overflow-y:auto; padding:14px 18px; }
.cur-replace-modal__foot { padding:12px 18px; border-top:1px solid var(--ad-border); text-align:right; }
.cur-replace-cmp { display:grid; grid-template-columns:1fr auto 1fr; gap:8px; align-items:start; font-size:13px; margin-bottom:12px; }
.cur-replace-cmp__arrow { font-size:18px; color:var(--ad-text-sub); padding-top:8px; }
.cur-replace-cmp__col { padding:10px; border-radius:8px; }
.cur-replace-cmp__col--old { background:#fef2f2; border:1px solid #fecaca; }
.cur-replace-cmp__col--new { background:#f0fdf4; border:1px solid #bbf7d0; }
.cur-replace-cmp__label { font-size:11px; color:var(--ad-text-sub); margin-bottom:4px; }
.cur-replace-cmp__name { font-weight:600; }
.cur-replace-cmp__addr { font-size:12px; color:var(--ad-text-sub); margin-top:2px; }
.cur-manual-badge { display:inline-block; font-size:10px; padding:1px 5px; border-radius:3px; background:#fef3c7; color:#92400e; margin-left:4px; vertical-align:middle; }
.cur-day-select { width:80px; font-size:12px; padding:3px 4px; border:1px solid var(--ad-border); border-radius:4px; background:var(--ad-card); }
.cur-region-row { display:flex; gap:4px; align-items:center; margin-top:4px; flex-wrap:wrap; }
.cur-region-tag { font-size:10px; padding:1px 6px; border-radius:3px; background:#e3f2fd; color:#1565c0; }
.cur-region-edit { display:none; gap:4px; margin-top:4px; }
.cur-region-edit.is-open { display:flex; }
.cur-region-edit input { font-size:11px; padding:2px 6px; border:1px solid var(--ad-border); border-radius:3px; width:70px; }
.cur-region-edit input.wider { width:100px; }
</style>
@endpush

@section('content')
<form method="POST" action="{{ $curation ? route('admin.curations.update', $curation) : route('admin.curations.store') }}" enctype="multipart/form-data" id="curForm">
    @csrf
    @if($curation) @method('PUT') @endif

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:8px">
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <button type="submit" class="ad-btn ad-btn--primary">저장</button>
            @if($curation)
                @if($curation->author_type === 'admin' || ($curation->author && $curation->author->is_operator_persona))
                    <button type="button" class="ad-btn {{ $curation->status === 'approved' ? 'ad-btn--danger' : '' }}" id="togglePublishBtn">
                        {{ $curation->status === 'approved' ? '발행 취소' : '발행하기' }}
                    </button>
                @endif
                @if($curation->status === 'pending')
                    <button type="button" class="ad-btn" style="background:#4CAF50;color:#fff" onclick="if(confirm('승인하시겠습니까?'))document.getElementById('approveForm').submit()">승인</button>
                    <button type="button" class="ad-btn" style="color:#C62828" onclick="rejectThis()">반려</button>
                @endif
                @if($curation->status === 'approved' && $curation->author_type === 'user')
                    <button type="button" class="ad-btn ad-btn--danger" onclick="if(confirm('강제 비공개 처리?'))document.getElementById('suspendForm').submit()">강제 비공개</button>
                @endif
                @if(in_array($curation->status, ['approved', 'pending']))
                    <a href="{{ route('curation.show', $curation->id) }}" target="_blank" class="ad-btn cur-preview-link">미리보기 ↗</a>
                @endif
                @if($curation->author_type === 'user' && $curation->author && !$curation->author->is_operator_persona)
                    <span class="ad-badge ad-badge--blue" style="margin-left:8px">UGC: {{ $curation->author->name }}</span>
                @endif
            @endif
        </div>
        @if($curation)
            <button type="button" class="ad-btn ad-btn--danger ad-btn--sm" id="deleteCurBtn">삭제</button>
        @endif
    </div>

    @if($errors->any())
        <div class="ad-alert ad-alert--error">{{ $errors->first() }}</div>
    @endif

    <div class="cur-form">
        <div class="cur-left">
            <div class="ad-card">
                <div class="ad-form-group">
                    <label>제목 *</label>
                    <input class="ad-input" name="title" value="{{ old('title', $curation?->title) }}" required>
                </div>
                <div class="ad-form-group">
                    <label>타입</label>
                    <select class="ad-input" name="type" id="curType">
                        <option value="list" {{ old('type', $curation?->type) === 'list' ? 'selected' : '' }}>리스트 (맛지도형)</option>
                        <option value="course" {{ old('type', $curation?->type) === 'course' ? 'selected' : '' }}>코스 (여행코스형)</option>
                    </select>
                </div>
                <div class="ad-form-group cur-duration-wrap{{ old('type', $curation?->type) === 'course' ? ' is-visible' : '' }}" id="durationWrap">
                    <label>코스 기간</label>
                    <div class="cur-duration-row">
                        <input type="number" class="ad-input" name="nights" id="curNights" min="0" max="30" value="{{ old('nights', $curation?->nights) }}" placeholder="-">
                        <span class="unit">박</span>
                        <input type="number" class="ad-input" name="days" id="curDaysField" min="1" max="31" value="{{ old('days', $curation?->days) }}" placeholder="-">
                        <span class="unit">일</span>
                        <span class="cur-duration-label" id="durationLabel"></span>
                    </div>
                    <div class="cur-duration-err" id="durationErr" style="display:none"></div>
                </div>
                <div class="ad-form-group">
                    <label>카테고리 *</label>
                    <select class="ad-input" name="category" required>
                        <option value="">선택하세요</option>
                        @foreach(config('curation_categories') as $slug => $cat)
                            <option value="{{ $slug }}" {{ old('category', $curation?->category) === $slug ? 'selected' : '' }}>
                                {{ $cat['icon'] }} {{ $cat['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="ad-form-group">
                    <label>작성자</label>
                    @php
                        $currentAuthor = 'official';
                        if ($curation && $curation->author_type === 'user' && $curation->author_user_id) {
                            $currentAuthor = $curation->author_user_id;
                        }
                    @endphp
                    <select class="ad-input" name="author_select">
                        <option value="official" {{ $currentAuthor === 'official' ? 'selected' : '' }}>핀픽 공식</option>
                        @foreach($personas as $p)
                            <option value="{{ $p->id }}" {{ $currentAuthor == $p->id ? 'selected' : '' }}>{{ $p->name }}{{ $p->bio ? " — {$p->bio}" : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ad-form-group">
                    <label>설명</label>
                    <textarea class="ad-input" name="description">{{ old('description', $curation?->description) }}</textarea>
                </div>
                <div class="ad-form-group">
                    <label>지역 라벨</label>
                    @php $hasRegionCodes = $curation && $curation->region_codes && count($curation->region_codes) > 0; @endphp
                    <input class="ad-input" name="region_label" id="regionLabelInput" value="{{ old('region_label', $curation?->region_label) }}" {{ $hasRegionCodes ? 'readonly' : '' }} placeholder="장소 추가 시 자동 생성됩니다" style="{{ $hasRegionCodes ? 'background:#f5f5f5;' : '' }}">
                    <div style="display:flex;align-items:center;gap:8px;margin-top:4px">
                        <small style="color:var(--ad-text-sub);font-size:11px">{{ $hasRegionCodes ? '장소 기반 자동 도출' : '쉼표로 구분하면 개별 칩으로 표시됩니다' }}</small>
                        @if($hasRegionCodes)
                        <label style="font-size:11px;cursor:pointer;color:var(--ad-primary)">
                            <input type="checkbox" id="regionManualToggle" style="margin-right:2px" onchange="toggleRegionManual(this.checked)">직접 지정
                        </label>
                        @endif
                    </div>
                </div>
            </div>

            @if($curation)
            <div class="ad-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                    <strong>통계</strong>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;font-size:13px">
                    <div>조회수 <strong>{{ number_format($curation->view_count) }}</strong></div>
                    <div>담기수 <strong>{{ number_format($curation->save_count) }}</strong></div>
                    <div>장소수 <strong>{{ $curation->places->count() }}</strong></div>
                </div>
            </div>
            @endif
        </div>

        <div class="cur-right">
            @if($curation)
            <div class="ad-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                    <strong>장소 관리</strong>
                    <span class="ad-badge ad-badge--gray" id="placeCount">{{ $curation->places->count() }}개</span>
                </div>

                <div style="display:flex;gap:6px;margin-bottom:8px">
                    <button type="button" class="ad-btn ad-btn--sm" id="searchTabDomestic" style="background:var(--ad-primary);color:#fff;border-color:var(--ad-primary)">국내</button>
                    <button type="button" class="ad-btn ad-btn--sm" id="searchTabOverseas">해외</button>
                </div>
                <div class="cur-search-wrap" style="margin-bottom:12px">
                    <input class="ad-input" id="placeSearch" placeholder="장소명 검색 (국내 · 카카오)" autocomplete="off">
                    <div class="cur-search-results" id="searchResults"></div>
                </div>

                <div class="cur-places" id="placeList">
                    @foreach($curation->places as $i => $p)
                    <div class="cur-place" data-place-id="{{ $p->id }}">
                        <div class="cur-place__handle" title="드래그하여 순서 변경">≡</div>
                        <div class="cur-place__num">{{ $i + 1 }}</div>
                        <div class="cur-place__body">
                            <input class="cur-place__name-input" data-field="place_name" value="{{ $p->place_name }}">
                            <input class="cur-place__addr-input" data-field="address" value="{{ $p->address }}">
                            @if($p->is_manual)<span class="cur-manual-badge">수동 수정</span>@endif
                            @if($p->region_l1)
                            <div class="cur-region-row">
                                <span class="cur-region-tag">{{ $p->country_code }}</span>
                                <span class="cur-region-tag">{{ $p->region_l1 }}</span>
                                @if($p->region_l2)<span class="cur-region-tag">{{ $p->region_l2 }}</span>@endif
                                <button type="button" style="font-size:10px;border:none;background:none;color:var(--ad-text-sub);cursor:pointer;padding:0" onclick="toggleRegionEdit({{ $p->id }})">✏️</button>
                            </div>
                            <div class="cur-region-edit" id="regionEdit{{ $p->id }}">
                                <input data-region="country_code" value="{{ $p->country_code }}" placeholder="국가" maxlength="2">
                                <input class="wider" data-region="region_l1" value="{{ $p->region_l1 }}" placeholder="시도/주">
                                <input class="wider" data-region="region_l2" value="{{ $p->region_l2 }}" placeholder="시군구/도시">
                                <button type="button" class="ad-btn ad-btn--sm" style="font-size:10px;padding:2px 6px" onclick="saveRegion({{ $p->id }})">저장</button>
                            </div>
                            @endif
                            @if($p->category_label)
                            @php
                                $rawCat = $p->category_label;
                                $mappedCat = '';
                                if ($p->is_overseas) {
                                    $typeMap = config('google_type_labels', []);
                                    foreach (explode(',', $rawCat) as $t) {
                                        $t = trim($t);
                                        if (isset($typeMap[$t]) && $typeMap[$t] !== null) {
                                            $mappedCat = $typeMap[$t];
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            <div style="font-size:10px;color:var(--ad-text-sub);margin-top:2px">
                                업종: <code style="background:var(--ad-bg);padding:1px 4px;border-radius:3px">{{ $rawCat }}</code>
                                @if($p->is_overseas)
                                → <strong style="color:var(--ad-text)">{{ $mappedCat ?: '(매핑없음)' }}</strong>
                                @endif
                            </div>
                            @endif
                            <div class="cur-place__gid" data-gid-row>
                                @if($p->google_place_id)
                                <span class="cur-place__gid-tag" title="{{ $p->google_place_id }}">G {{ \Illuminate\Support\Str::limit($p->google_place_id, 20) }}</span>
                                <button type="button" class="cur-place__gid-btn" onclick="matchGoogle({{ $p->id }}, this)" title="재검색">🔄</button>
                                <button type="button" class="cur-place__gid-btn cur-place__gid-btn--del" onclick="clearGoogle({{ $p->id }}, this)" title="해제">✕</button>
                                @else
                                <button type="button" class="cur-place__gid-btn" onclick="matchGoogle({{ $p->id }}, this)">G 매칭</button>
                                @endif
                            </div>
                            @if($p->source_channel)
                            <div class="cur-place__source">{{ $p->source_channel }}@if($p->source_date) ({{ $p->source_date->format('Y.m.d') }})@endif</div>
                            @endif
                            <div class="cur-place__photos" data-pid="{{ $p->id }}" tabindex="0">
                                @if($p->photos)
                                    @foreach($p->photos as $pi => $photo)
                                    <div class="cur-place__photo" draggable="true" data-pidx="{{ $pi }}">
                                        <img src="{{ config('app.url') . '/storage/' . \App\Services\ImageProcessor::thumbPathFor($photo) }}" alt="">
                                        <button type="button" class="cur-place__photo-del" onclick="deletePhoto({{ $p->id }}, {{ $pi }}, this)">✕</button>
                                    </div>
                                    @endforeach
                                @endif
                                @if(!$p->photos || count($p->photos) < config('curation.place_photos_max', 6))
                                <label class="cur-place__photo-add">
                                    +
                                    <input type="file" accept="image/*" multiple hidden onchange="uploadPhotos({{ $p->id }}, this)">
                                </label>
                                <div class="cur-place__url-row">
                                    <textarea rows="3" placeholder="이미지 URL (여러 줄 가능)" class="cur-url-input"></textarea>
                                    <button type="button" class="ad-btn ad-btn--sm cur-url-btn" onclick="uploadFromUrl({{ $p->id }}, this)">URL</button>
                                </div>
                                <div class="cur-place__hint">URL 여러 개 입력 시 줄바꿈으로 구분 · URL 여러 개 줄바꿈 입력 → Ctrl+Enter 또는 URL 버튼 · Ctrl+V 붙여넣기 · 드래그앤드롭</div>
                                @endif
                                @unless($p->is_overseas)
                                <button type="button" class="tour-btn" onclick="searchTourImages({{ $p->id }})">🔍 이미지 찾기</button>
                                @endunless
                            </div>
                            <div class="cur-place__meta">
                                <input class="short" data-field="phone" value="{{ $p->phone }}" placeholder="전화번호">
                                <input class="short" data-field="building_name" value="{{ $p->building_name }}" placeholder="건물명">
                                @php
                                    $hoursRaw = is_array($p->opening_hours) ? json_encode($p->opening_hours, JSON_UNESCAPED_UNICODE) : ($p->opening_hours ?? '');
                                    $hoursArr = is_array($p->opening_hours) ? $p->opening_hours : null;
                                @endphp
                                <div class="cur-hours-wrap">
                                    @if($hoursArr)
                                        <div class="cur-hours" title="{{ implode("\n", $hoursArr) }}">
                                            @foreach(array_slice($hoursArr, 0, 3) as $line)
                                                <div class="cur-hours__line">{{ $line }}</div>
                                            @endforeach
                                            @if(count($hoursArr) > 3)
                                                <div class="cur-hours__line">…외 {{ count($hoursArr) - 3 }}일</div>
                                            @endif
                                            <button type="button" class="cur-hours__edit" onclick="toggleHoursEdit(this)">원본 수정</button>
                                        </div>
                                    @endif
                                    <input class="url cur-hours__raw{{ $hoursArr ? '' : ' cur-hours__raw--visible' }}" data-field="opening_hours" value="{{ $hoursRaw }}" placeholder="영업시간"@if($hoursArr) style="display:none"@endif>
                                </div>
                            </div>
                            <div class="cur-place__meta">
                                <input class="short" data-field="source_channel" value="{{ $p->source_channel }}" placeholder="출처 채널">
                                <input class="url" data-field="source_url" value="{{ $p->source_url }}" placeholder="출처 URL">
                                <input class="short" data-field="source_date" type="date" value="{{ $p->source_date?->format('Y-m-d') }}">
                                @if($curation->type === 'course')
                                <select class="cur-day-select" data-field="day_number">
                                    <option value="">미지정</option>
                                    @for($d = 1; $d <= ($curation->days ?? 7); $d++)
                                    <option value="{{ $d }}" {{ $p->day_number == $d ? 'selected' : '' }}>{{ $d }}일차</option>
                                    @endfor
                                </select>
                                @endif
                                <input class="short" data-field="editor_note" value="{{ $p->editor_note }}" placeholder="코멘트">
                            </div>
                        </div>
                        <div class="cur-place__order-btns">
                            <button type="button" class="cur-place__order-btn" onclick="movePlaceUp(this)" title="위로">↑</button>
                            <button type="button" class="cur-place__order-btn" onclick="movePlaceDown(this)" title="아래로">↓</button>
                        </div>
                        <div class="cur-place__actions">
                            <button type="button" class="cur-replace-btn" onclick="openReplace({{ $p->id }}, this)">교체</button>
                            <button type="button" class="ad-btn ad-btn--sm" onclick="savePlace({{ $p->id }}, this)">저장</button>
                            <button type="button" class="ad-btn ad-btn--sm ad-btn--danger" onclick="removePlace({{ $p->id }}, this)">삭제</button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <div class="ad-card" style="text-align:center;padding:40px;color:var(--ad-text-sub)">
                큐레이션을 먼저 저장하면 장소를 추가할 수 있습니다.
            </div>
            @endif
        </div>
    </div>
</form>

<!-- TourAPI 이미지 검색 모달 -->
<div class="tour-modal" id="tourModal">
    <div class="tour-modal__panel">
        <div class="tour-modal__head">
            <h3 id="tourModalTitle">관광 이미지 검색</h3>
            <button type="button" class="tour-modal__close" onclick="closeTourModal()">✕</button>
        </div>
        <div class="tour-modal__body" id="tourModalBody">
            <div class="tour-modal__empty">검색 중...</div>
        </div>
        <div class="tour-modal__foot">
            <span class="tour-modal__count" id="tourModalCount">0장 선택</span>
            <button type="button" class="ad-btn ad-btn--primary ad-btn--sm" id="tourModalConfirm" onclick="confirmTourImages()" disabled>선택한 이미지 등록</button>
        </div>
    </div>
</div>

<!-- 장소 교체 모달 -->
<div class="cur-replace-modal" id="replaceModal">
    <div class="cur-replace-modal__panel">
        <div class="cur-replace-modal__head">
            <h3>장소 교체</h3>
            <button type="button" class="cur-replace-modal__close" onclick="closeReplace()">✕</button>
        </div>
        <div class="cur-replace-modal__body" id="replaceBody">
            <div style="display:flex;gap:6px;margin-bottom:8px">
                <button type="button" class="ad-btn ad-btn--sm" id="replTabDom" onclick="setReplaceTab(false)" style="background:var(--ad-primary);color:#fff;border-color:var(--ad-primary)">국내</button>
                <button type="button" class="ad-btn ad-btn--sm" id="replTabOvs" onclick="setReplaceTab(true)">해외</button>
                <input class="ad-input" id="replaceSearch" placeholder="장소명 검색 (국내 · 카카오)" autocomplete="off" style="flex:1">
                <button type="button" class="ad-btn ad-btn--sm" id="replaceSearchBtn" onclick="doReplaceSearch()">검색</button>
            </div>
            <div style="margin-bottom:12px"><button type="button" class="ad-btn ad-btn--sm" style="font-size:12px;color:var(--ad-text-sub)" onclick="showManualReplace()">검색 결과가 없나요? 수동 입력</button></div>
            <div id="replaceResults"></div>
            <div id="replaceManual" style="display:none">
                <div style="font-size:13px;font-weight:600;margin-bottom:8px;color:var(--ad-text)">수동 입력</div>
                <input class="ad-input" id="manualPlaceName" placeholder="장소명" style="margin-bottom:6px">
                <input class="ad-input" id="manualAddress" placeholder="주소" style="margin-bottom:6px">
                <input class="ad-input" id="manualPhone" placeholder="전화번호 (선택)" style="margin-bottom:6px">
            </div>
            <div id="replaceCompare" style="display:none"></div>
        </div>
        <div class="cur-replace-modal__foot" id="replaceFoot" style="display:none">
            <button type="button" class="ad-btn" onclick="closeReplace()" style="margin-right:8px">취소</button>
            <button type="button" class="ad-btn ad-btn--primary" id="replaceConfirmBtn" onclick="confirmReplace()">교체 확정</button>
        </div>
    </div>
</div>

@if($curation)
    @if($curation->status === 'pending')
        <form id="approveForm" method="POST" action="{{ route('admin.curations.approve', $curation) }}" style="display:none">@csrf</form>
    @endif
    @if($curation->status === 'approved' && $curation->author_type === 'user')
        <form id="suspendForm" method="POST" action="{{ route('admin.curations.suspend', $curation) }}" style="display:none">@csrf</form>
    @endif
@endif
@endsection

@if($curation)
@push('scripts')
<script>
const csrf = '{{ csrf_token() }}';
const curationId = {{ $curation->id }};
const cType = '{{ $curation->type }}';
const PHOTO_MAX = {{ config('curation.place_photos_max', 6) }};
let searchTimer;
let searchMode = 'domestic';
let curDaysVal = {{ $curation->days ?? 0 }};
let daysAutoFill = true;

function getCurDays() { return parseInt(document.getElementById('curDaysField')?.value) || 0; }
function getCurNights() { const v = document.getElementById('curNights')?.value; return v === '' ? null : parseInt(v); }

function buildDaySelect(val) {
    const dur = getCurDays() || curDaysVal || 7;
    let h = '<select class="cur-day-select" data-field="day_number"><option value="">미지정</option>';
    for (let d = 1; d <= dur; d++) h += '<option value="' + d + '"' + (val == d ? ' selected' : '') + '>' + d + '일차</option>';
    return h + '</select>';
}

function updateDurationLabel() {
    const el = document.getElementById('durationLabel');
    const err = document.getElementById('durationErr');
    if (!el) return;
    const n = getCurNights(), d = getCurDays();
    if (d === 0 && n === null) { el.textContent = ''; err.style.display = 'none'; return; }
    const nn = n ?? 0;
    if (nn === 0 && d === 1) el.textContent = '당일치기';
    else if (nn === 0 && d > 1) el.textContent = '무박 ' + d + '일';
    else if (d > 0) el.textContent = nn + '박 ' + d + '일';
    else el.textContent = '';
    if (d > 0 && nn >= d) {
        err.textContent = nn + '박이면 일수는 ' + (nn + 1) + '일 이상이어야 합니다';
        err.style.display = 'block';
    } else {
        err.style.display = 'none';
    }
}

document.getElementById('curNights')?.addEventListener('input', function() {
    const n = parseInt(this.value);
    if (!isNaN(n) && daysAutoFill) {
        document.getElementById('curDaysField').value = n + 1;
        curDaysVal = n + 1;
        rebuildDaySelects();
        rebuildDayGroups();
    }
    updateDurationLabel();
});

document.getElementById('curDaysField')?.addEventListener('input', function() {
    daysAutoFill = false;
    const newDays = parseInt(this.value) || 0;
    if (newDays > 0 && newDays < curDaysVal) {
        const stranded = {};
        document.querySelectorAll('.cur-place').forEach(card => {
            const sel = card.querySelector('[data-field="day_number"]');
            const val = sel ? parseInt(sel.value) : 0;
            if (val > newDays) {
                if (!stranded[val]) stranded[val] = 0;
                stranded[val]++;
            }
        });
        const msgs = Object.entries(stranded).map(([d, c]) => d + '일차에 장소 ' + c + '곳');
        if (msgs.length) {
            alert('아래 일차에 장소가 남아있어 축소할 수 없습니다:\n' + msgs.join('\n') + '\n\n해당 장소의 일차를 먼저 변경해주세요.');
            this.value = curDaysVal;
            return;
        }
    }
    curDaysVal = newDays;
    rebuildDaySelects();
    rebuildDayGroups();
    updateDurationLabel();
});

document.getElementById('curType').addEventListener('change', function() {
    const wrap = document.getElementById('durationWrap');
    const isCourse = this.value === 'course';
    if (isCourse) {
        wrap.classList.add('is-visible');
        if (!document.querySelector('.cur-day-select')) {
            document.querySelectorAll('.cur-place').forEach(card => {
                const metas = card.querySelectorAll('.cur-place__meta');
                const meta = metas[1] || metas[0];
                if (meta && !meta.querySelector('.cur-day-select')) {
                    const noteInput = meta.querySelector('[data-field="editor_note"]');
                    if (noteInput) noteInput.insertAdjacentHTML('beforebegin', buildDaySelect(''));
                }
            });
        } else {
            document.querySelectorAll('.cur-day-select').forEach(s => s.style.display = '');
        }
        rebuildDayGroups();
    } else {
        wrap.classList.remove('is-visible');
        document.querySelectorAll('.cur-day-divider').forEach(d => d.remove());
        document.querySelectorAll('.cur-day-select').forEach(s => s.style.display = 'none');
        renumber();
    }
});

function rebuildDaySelects() {
    const dur = getCurDays();
    document.querySelectorAll('.cur-day-select').forEach(sel => {
        const curVal = sel.value;
        let html = '<option value="">미지정</option>';
        for (let d = 1; d <= dur; d++) {
            html += '<option value="' + d + '"' + (curVal == d ? ' selected' : '') + '>' + d + '일차</option>';
        }
        sel.innerHTML = html;
        if (curVal && parseInt(curVal) > dur) sel.value = '';
        sel.disabled = dur === 0;
    });
}

function rebuildDayGroups() {
    const list = document.getElementById('placeList');
    if (!list || document.getElementById('curType').value !== 'course') return;
    const dur = getCurDays();
    list.querySelectorAll('.cur-day-divider').forEach(d => d.remove());
    if (!dur) { renumber(); return; }
    const cards = Array.from(list.querySelectorAll('.cur-place'));
    const groups = {};
    cards.forEach(card => {
        const sel = card.querySelector('[data-field="day_number"]');
        const day = sel ? (sel.value || '0') : '0';
        if (!groups[day]) groups[day] = [];
        groups[day].push(card);
    });
    const keys = Object.keys(groups).sort((a, b) => {
        if (a === '0') return -1;
        if (b === '0') return 1;
        return parseInt(a) - parseInt(b);
    });
    keys.forEach(day => {
        const label = day === '0' ? '미지정' : day + '일차';
        const count = groups[day].length;
        const div = document.createElement('div');
        div.className = 'cur-day-divider';
        div.dataset.day = day;
        div.innerHTML = '<span>' + label + '</span><span class="cur-day-divider__count">' + count + '곳</span>';
        list.appendChild(div);
        groups[day].forEach(card => list.appendChild(card));
    });
    renumber();
}

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('cur-day-select')) {
        rebuildDayGroups();
        saveOrder();
    }
});

const searchInput = document.getElementById('placeSearch');
const searchResults = document.getElementById('searchResults');
const tabDomestic = document.getElementById('searchTabDomestic');
const tabOverseas = document.getElementById('searchTabOverseas');

function setSearchTab(mode) {
    searchMode = mode;
    searchResults.classList.remove('is-open');
    searchInput.value = '';
    if (mode === 'domestic') {
        tabDomestic.style.cssText = 'background:var(--ad-primary);color:#fff;border-color:var(--ad-primary)';
        tabOverseas.style.cssText = '';
        searchInput.placeholder = '장소명 검색 (국내 · 카카오)';
    } else {
        tabOverseas.style.cssText = 'background:var(--ad-primary);color:#fff;border-color:var(--ad-primary)';
        tabDomestic.style.cssText = '';
        searchInput.placeholder = '장소명 검색 (해외 · Google)';
    }
    searchInput.focus();
}
tabDomestic.addEventListener('click', () => setSearchTab('domestic'));
tabOverseas.addEventListener('click', () => setSearchTab('overseas'));

searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const q = searchInput.value.trim();
    if (q.length < 2) { searchResults.classList.remove('is-open'); return; }
    searchTimer = setTimeout(() => doSearch(q), 500);
});

searchInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(searchTimer);
        const q = searchInput.value.trim();
        if (q.length >= 1) doSearch(q);
    }
});

async function doSearch(q) {
    const endpoint = searchMode === 'overseas'
        ? '/api/search/overseas?q=' + encodeURIComponent(q)
        : '/api/search?q=' + encodeURIComponent(q);
    try {
        searchResults.innerHTML = '<div class="cur-sr" style="color:var(--ad-text-sub);cursor:default">검색 중...</div>';
        searchResults.classList.add('is-open');

        const r = await fetch(endpoint);
        if (!r.ok) {
            searchResults.innerHTML = '<div class="cur-sr" style="color:#c00;cursor:default">API 오류 (HTTP ' + r.status + ')</div>';
            return;
        }
        const data = await r.json();
        if (data.error === 'no_key') {
            searchResults.innerHTML = '<div class="cur-sr" style="color:#c00;cursor:default">API 키가 설정되지 않았습니다.</div>';
            return;
        }
        const docs = data.documents || [];
        if (!docs.length) {
            searchResults.innerHTML = '<div class="cur-sr" style="color:var(--ad-text-sub);cursor:default">검색 결과가 없습니다.</div>';
            return;
        }
        const isOverseas = searchMode === 'overseas';
        searchResults.innerHTML = docs.slice(0, 10).map((d, i) => {
            return '<div class="cur-sr" data-idx="' + i + '">'
                + '<div class="cur-sr__name">' + esc(d.place_name) + ' <small>' + esc(d.category_group_name || '') + '</small></div>'
                + '<div class="cur-sr__addr">' + esc(d.road_address_name || d.address_name || '') + '</div>'
                + '</div>';
        }).join('');
        const docsRef = docs.slice(0, 10);
        searchResults.querySelectorAll('.cur-sr').forEach(el => {
            el.addEventListener('click', () => {
                const d = docsRef[parseInt(el.dataset.idx)];
                addPlaceFromSearch(d, isOverseas);
                searchResults.classList.remove('is-open');
                searchInput.value = '';
            });
        });
    } catch(e) {
        searchResults.innerHTML = '<div class="cur-sr" style="color:#c00;cursor:default">네트워크 오류: ' + esc(e.message) + '</div>';
    }
}

function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

async function addPlaceFromSearch(d, isOverseas) {
    const addr = d.road_address_name || d.address_name || '';
    const body = {
        place_name: d.place_name,
        address: addr,
        jibeon_address: d.address_name || '',
        latitude: d.y,
        longitude: d.x,
        category_label: d.category_group_name || '',
        external_place_id: d.id || '',
        phone: d.phone || '',
        is_overseas: isOverseas,
    };
    if (isOverseas && d.id) body.google_place_id = d.id;
    if (isOverseas && d.address_components) body.address_components = d.address_components;
    if (d.opening_hours) body.opening_hours = d.opening_hours;

    if (!isOverseas && addr) {
        try {
            const br = await fetch('/api/building-name?road_address=' + encodeURIComponent(addr));
            const bd = await br.json();
            if (bd.building_name) body.building_name = bd.building_name;
        } catch(e) {}
    }

    try {
        const r = await fetch('/admin/curations/' + curationId + '/places', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(body),
        });
        const data = await r.json();
        if (data.success) {
            appendPlaceCard(data.place);
            if (!isOverseas) enrichNaverData(data.place.id, d.place_name, d.y, d.x, addr);
        } else {
            alert(data.message || '추가 실패');
        }
    } catch(e) { alert('추가 실패: ' + e.message); }
}

async function enrichNaverData(placeId, name, lat, lng, address) {
    try {
        const r = await fetch('/admin/curations/places/' + placeId + '/enrich-naver', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ name, lat, lng, address }),
        });
        const data = await r.json();
        if (data.success) {
            const card = document.querySelector('[data-place-id="' + placeId + '"]');
            if (!card) return;
            if (data.phone) {
                const phoneInp = card.querySelector('[data-field="phone"]');
                if (phoneInp && !phoneInp.value) phoneInp.value = data.phone;
            }
            if (data.building_name) {
                const bnInp = card.querySelector('[data-field="building_name"]');
                if (bnInp && !bnInp.value) bnInp.value = data.building_name;
            }
            if (data.opening_hours) {
                const hoursInp = card.querySelector('[data-field="opening_hours"]');
                if (hoursInp && !hoursInp.value) {
                    const val = Array.isArray(data.opening_hours) ? JSON.stringify(data.opening_hours) : data.opening_hours;
                    hoursInp.value = val;
                    if (Array.isArray(data.opening_hours)) {
                        const wrap = hoursInp.closest('.cur-hours-wrap');
                        if (wrap) {
                            const display = document.createElement('div');
                            display.className = 'cur-hours';
                            display.title = data.opening_hours.join('\n');
                            data.opening_hours.slice(0, 3).forEach(l => {
                                const d = document.createElement('div');
                                d.className = 'cur-hours__line';
                                d.textContent = l;
                                display.appendChild(d);
                            });
                            if (data.opening_hours.length > 3) {
                                const d = document.createElement('div');
                                d.className = 'cur-hours__line';
                                d.textContent = '…외 ' + (data.opening_hours.length - 3) + '일';
                                display.appendChild(d);
                            }
                            const editBtn = document.createElement('button');
                            editBtn.type = 'button';
                            editBtn.className = 'cur-hours__edit';
                            editBtn.textContent = '원본 수정';
                            editBtn.onclick = function() { toggleHoursEdit(this); };
                            display.appendChild(editBtn);
                            wrap.insertBefore(display, hoursInp);
                            hoursInp.style.display = 'none';
                        }
                    }
                }
            }
        }
    } catch(e) {}
}

function appendPlaceCard(p) {
    const list = document.getElementById('placeList');
    const idx = list.querySelectorAll('.cur-place').length;
    const dayInput = cType === 'course' ? buildDaySelect('') : '';
    const html = '<div class="cur-place" data-place-id="' + p.id + '">'
        + '<div class="cur-place__handle" title="드래그하여 순서 변경">≡</div>'
        + '<div class="cur-place__num">' + (idx + 1) + '</div>'
        + '<div class="cur-place__body">'
        + '<input class="cur-place__name-input" data-field="place_name" value="' + esc(p.place_name) + '">'
        + '<input class="cur-place__addr-input" data-field="address" value="' + esc(p.address || '') + '">'
        + '<div class="cur-place__gid" data-gid-row>'
        + (p.google_place_id
            ? '<span class="cur-place__gid-tag" title="' + esc(p.google_place_id) + '">G ' + esc(p.google_place_id.substring(0,20)) + '</span>'
              + '<button type="button" class="cur-place__gid-btn" onclick="matchGoogle(' + p.id + ',this)" title="재검색">🔄</button>'
              + '<button type="button" class="cur-place__gid-btn cur-place__gid-btn--del" onclick="clearGoogle(' + p.id + ',this)" title="해제">✕</button>'
            : '<button type="button" class="cur-place__gid-btn" onclick="matchGoogle(' + p.id + ',this)">G 매칭</button>')
        + '</div>'
        + '<div class="cur-place__photos" data-pid="' + p.id + '" tabindex="0">'
        + '<label class="cur-place__photo-add">+<input type="file" accept="image/*" multiple hidden onchange="uploadPhotos(' + p.id + ',this)"></label>'
        + '<div class="cur-place__url-row"><textarea rows="3" placeholder="이미지 URL (여러 줄 가능)" class="cur-url-input"></textarea><button type="button" class="ad-btn ad-btn--sm cur-url-btn" onclick="uploadFromUrl(' + p.id + ',this)">URL</button></div>'
        + '<div class="cur-place__hint">URL 여러 개 줄바꿈 입력 → Ctrl+Enter 또는 URL 버튼 · Ctrl+V 붙여넣기 · 드래그앤드롭</div>'
        + (p.is_overseas ? '' : '<button type="button" class="tour-btn" onclick="searchTourImages(' + p.id + ')">🔍 이미지 찾기</button>')
        + '</div>'
        + '<div class="cur-place__meta">'
        + '<input class="short" data-field="phone" value="' + esc(p.phone || '') + '" placeholder="전화번호">'
        + '<input class="short" data-field="building_name" value="' + esc(p.building_name || '') + '" placeholder="건물명">'
        + '<div class="cur-hours-wrap"><input class="url" data-field="opening_hours" value="" placeholder="영업시간"></div>'
        + '</div>'
        + '<div class="cur-place__meta">'
        + '<input class="short" data-field="source_channel" value="" placeholder="출처 채널">'
        + '<input class="url" data-field="source_url" value="" placeholder="출처 URL">'
        + '<input class="short" data-field="source_date" type="date" value="">'
        + dayInput
        + '<input class="short" data-field="editor_note" value="" placeholder="코멘트">'
        + '</div></div>'
        + '<div class="cur-place__order-btns">'
        + '<button type="button" class="cur-place__order-btn" onclick="movePlaceUp(this)" title="위로">↑</button>'
        + '<button type="button" class="cur-place__order-btn" onclick="movePlaceDown(this)" title="아래로">↓</button>'
        + '</div>'
        + '<div class="cur-place__actions">'
        + '<button type="button" class="cur-replace-btn" onclick="openReplace(' + p.id + ', this)">교체</button>'
        + '<button type="button" class="ad-btn ad-btn--sm" onclick="savePlace(' + p.id + ', this)">저장</button>'
        + '<button type="button" class="ad-btn ad-btn--sm ad-btn--danger" onclick="removePlace(' + p.id + ', this)">삭제</button>'
        + '</div></div>';
    list.insertAdjacentHTML('beforeend', html);
    if (cType === 'course' && curDaysVal) rebuildDayGroups();
    else renumber();
}

async function savePlace(placeId, btn) {
    const card = btn.closest('.cur-place');
    const fields = {};
    card.querySelectorAll('[data-field]').forEach(inp => {
        fields[inp.dataset.field] = inp.value;
    });
    try {
        const r = await fetch('/admin/curations/places/' + placeId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(fields),
        });
        if ((await r.json()).success) {
            btn.textContent = '완료';
            setTimeout(() => { btn.textContent = '저장'; }, 1000);
        }
    } catch(e) { alert('저장 실패'); }
}

async function removePlace(placeId, btn) {
    if (!confirm('이 장소를 삭제할까요?')) return;
    try {
        const r = await fetch('/admin/curations/places/' + placeId, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        });
        if ((await r.json()).success) {
            btn.closest('.cur-place').remove();
            if (document.getElementById('curType').value === 'course' && curDaysVal) rebuildDayGroups();
            else renumber();
        }
    } catch(e) { alert('삭제 실패'); }
}

async function matchGoogle(placeId, btn) {
    const orig = btn.textContent;
    btn.textContent = '…';
    btn.disabled = true;
    try {
        const r = await fetch('/admin/curations/places/' + placeId + '/match-google', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: '{}',
        });
        const data = await r.json();
        const row = btn.closest('[data-gid-row]');
        if (data.success && data.google_place_id) {
            const short = data.google_place_id.length > 20 ? data.google_place_id.substring(0, 20) + '…' : data.google_place_id;
            const info = data.review_count ? ' (' + data.rating + '점/' + data.review_count + '개)' : '';
            row.innerHTML = '<span class="cur-place__gid-tag" title="' + esc(data.google_place_id) + '">G ' + esc(short) + info + '</span>'
                + '<button type="button" class="cur-place__gid-btn" onclick="matchGoogle(' + placeId + ',this)" title="재검색">🔄</button>'
                + '<button type="button" class="cur-place__gid-btn cur-place__gid-btn--del" onclick="clearGoogle(' + placeId + ',this)" title="해제">✕</button>';
        } else {
            btn.textContent = '매칭 실패';
            setTimeout(() => { btn.textContent = orig; btn.disabled = false; }, 1500);
        }
    } catch(e) { btn.textContent = '오류'; setTimeout(() => { btn.textContent = orig; btn.disabled = false; }, 1500); }
}

async function clearGoogle(placeId, btn) {
    try {
        const r = await fetch('/admin/curations/places/' + placeId + '/clear-google', {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        });
        if ((await r.json()).success) {
            const row = btn.closest('[data-gid-row]');
            row.innerHTML = '<button type="button" class="cur-place__gid-btn" onclick="matchGoogle(' + placeId + ',this)">G 매칭</button>';
        }
    } catch(e) { alert('해제 실패'); }
}

// ── 장소 교체 모달 ──
let replaceTargetId = null;
let replaceTargetCard = null;
let replaceIsOverseas = false;
let replaceSearchResults = [];
let replaceSelectedData = null;
let replaceManualMode = false;

function showManualReplace() {
    document.getElementById('replaceResults').innerHTML = '';
    document.getElementById('replaceCompare').style.display = 'none';
    document.getElementById('replaceManual').style.display = '';
    replaceManualMode = true;
    replaceSelectedData = null;

    const card = replaceTargetCard;
    const searchVal = document.getElementById('replaceSearch').value.trim();
    const manualName = document.getElementById('manualPlaceName');
    const manualAddr = document.getElementById('manualAddress');
    const manualPhone = document.getElementById('manualPhone');
    if (!manualName.value) manualName.value = searchVal || (card.querySelector('[data-field="place_name"]')?.value || '');
    if (!manualAddr.value) manualAddr.value = card.querySelector('[data-field="address"]')?.value || '';
    if (!manualPhone.value) manualPhone.value = card.querySelector('[data-field="phone"]')?.value || '';
    manualName.focus();

    document.getElementById('replaceFoot').style.display = 'flex';
    document.getElementById('replaceConfirmBtn').textContent = '수동 교체 확정';
}

function setReplaceTab(overseas) {
    replaceIsOverseas = overseas;
    const domBtn = document.getElementById('replTabDom');
    const ovsBtn = document.getElementById('replTabOvs');
    const search = document.getElementById('replaceSearch');
    if (overseas) {
        ovsBtn.style.cssText = 'background:var(--ad-primary);color:#fff;border-color:var(--ad-primary)';
        domBtn.style.cssText = '';
        search.placeholder = '장소명 검색 (해외 · Google)';
    } else {
        domBtn.style.cssText = 'background:var(--ad-primary);color:#fff;border-color:var(--ad-primary)';
        ovsBtn.style.cssText = '';
        search.placeholder = '장소명 검색 (국내 · 카카오)';
    }
    search.focus();
}

function openReplace(placeId, btn) {
    replaceTargetId = placeId;
    replaceTargetCard = btn.closest('.cur-place');
    const nameInp = replaceTargetCard.querySelector('[data-field="place_name"]');
    replaceIsOverseas = false;
    const card = replaceTargetCard;
    const addrInp = card.querySelector('[data-field="address"]');
    const addr = addrInp ? addrInp.value : '';
    if (addr && !/[가-힣]/.test(addr)) replaceIsOverseas = true;

    setReplaceTab(replaceIsOverseas);

    document.getElementById('replaceSearch').value = nameInp ? nameInp.value : '';
    document.getElementById('replaceResults').innerHTML = '';
    document.getElementById('replaceManual').style.display = 'none';
    document.getElementById('manualPlaceName').value = '';
    document.getElementById('manualAddress').value = '';
    document.getElementById('manualPhone').value = '';
    document.getElementById('replaceCompare').style.display = 'none';
    document.getElementById('replaceFoot').style.display = 'none';
    replaceSelectedData = null;
    replaceManualMode = false;

    document.getElementById('replaceModal').classList.add('is-open');
    document.getElementById('replaceSearch').focus();
}

async function doReplaceSearch() {
    const q = document.getElementById('replaceSearch').value.trim();
    if (!q) return;
    const resultsEl = document.getElementById('replaceResults');
    const compareEl = document.getElementById('replaceCompare');
    compareEl.style.display = 'none';
    document.getElementById('replaceManual').style.display = 'none';
    document.getElementById('replaceFoot').style.display = 'none';
    replaceManualMode = false;
    replaceSelectedData = null;

    resultsEl.innerHTML = '<div style="padding:8px;color:var(--ad-text-sub)">검색 중...</div>';

    const endpoint = replaceIsOverseas
        ? '/api/search/overseas?q=' + encodeURIComponent(q)
        : '/api/search?q=' + encodeURIComponent(q);

    try {
        const r = await fetch(endpoint);
        const data = await r.json();
        const docs = data.documents || [];
        if (!docs.length) {
            resultsEl.innerHTML = '<div style="padding:8px;color:var(--ad-text-sub)">검색 결과가 없습니다.</div>';
            return;
        }
        replaceSearchResults = docs.slice(0, 10);
        resultsEl.innerHTML = replaceSearchResults.map((d, i) =>
            '<div class="cur-sr" data-ridx="' + i + '" style="cursor:pointer">'
            + '<div class="cur-sr__name">' + esc(d.place_name) + ' <small>' + esc(d.category_group_name || '') + '</small></div>'
            + '<div class="cur-sr__addr">' + esc(d.road_address_name || d.address_name || '') + '</div>'
            + '</div>'
        ).join('');
        resultsEl.querySelectorAll('.cur-sr').forEach(el => {
            el.addEventListener('click', () => selectReplaceResult(parseInt(el.dataset.ridx)));
        });
    } catch(e) {
        resultsEl.innerHTML = '<div style="padding:8px;color:#c00">검색 오류: ' + esc(e.message) + '</div>';
    }
}

document.getElementById('replaceSearch')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); doReplaceSearch(); }
});

function selectReplaceResult(idx) {
    const d = replaceSearchResults[idx];
    if (!d) return;
    replaceSelectedData = d;

    const oldCard = replaceTargetCard;
    const oldName = oldCard.querySelector('[data-field="place_name"]')?.value || '';
    const oldAddr = oldCard.querySelector('[data-field="address"]')?.value || '';
    const oldPhone = oldCard.querySelector('[data-field="phone"]')?.value || '';

    const newAddr = d.road_address_name || d.address_name || '';
    const newPhone = d.phone || '';

    const compareEl = document.getElementById('replaceCompare');
    compareEl.innerHTML = '<table class="cur-replace-cmp">'
        + '<thead><tr><th></th><th>기존</th><th>신규</th></tr></thead>'
        + '<tbody>'
        + '<tr><td>장소명</td><td>' + esc(oldName) + '</td><td>' + esc(d.place_name) + '</td></tr>'
        + '<tr><td>주소</td><td>' + esc(oldAddr) + '</td><td>' + esc(newAddr) + '</td></tr>'
        + '<tr><td>전화번호</td><td>' + esc(oldPhone) + '</td><td>' + esc(newPhone) + '</td></tr>'
        + '</tbody></table>';
    compareEl.style.display = '';

    document.getElementById('replaceResults').innerHTML = '';
    document.getElementById('replaceFoot').style.display = 'flex';
}

async function confirmReplace() {
    if (!replaceTargetId) return;
    const btn = document.getElementById('replaceConfirmBtn');
    btn.disabled = true;
    btn.textContent = '교체 중...';

    if (replaceManualMode) {
        const name = document.getElementById('manualPlaceName').value.trim();
        const addr = document.getElementById('manualAddress').value.trim();
        const phone = document.getElementById('manualPhone').value.trim();
        if (!name) { alert('장소명을 입력해주세요.'); btn.disabled = false; btn.textContent = '수동 교체 확정'; return; }

        const fields = { place_name: name, address: addr, phone: phone };
        try {
            const r = await fetch('/admin/curations/places/' + replaceTargetId, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(fields),
            });
            const data = await r.json();
            if (data.success) {
                const card = replaceTargetCard;
                const nameInp = card.querySelector('[data-field="place_name"]');
                const addrInp = card.querySelector('[data-field="address"]');
                const phoneInp = card.querySelector('[data-field="phone"]');
                if (nameInp) nameInp.value = name;
                if (addrInp) addrInp.value = addr;
                if (phoneInp) phoneInp.value = phone;

                if (!card.querySelector('.cur-manual-badge')) {
                    const badge = document.createElement('span');
                    badge.className = 'cur-manual-badge';
                    badge.textContent = '수동 수정';
                    const addrEl = card.querySelector('[data-field="address"]');
                    if (addrEl) addrEl.after(badge);
                }

                closeReplace();
            } else {
                alert(data.message || '저장 실패');
                btn.disabled = false; btn.textContent = '수동 교체 확정';
            }
        } catch(e) {
            alert('저장 실패: ' + e.message);
            btn.disabled = false; btn.textContent = '수동 교체 확정';
        }
        return;
    }

    if (!replaceSelectedData) return;
    const d = replaceSelectedData;

    const body = {
        place_name: d.place_name,
        address: d.road_address_name || d.address_name || '',
        jibeon_address: d.address_name || '',
        latitude: d.y,
        longitude: d.x,
        phone: d.phone || '',
        building_name: '',
        external_place_id: d.id || '',
        is_overseas: replaceIsOverseas,
    };
    if (replaceIsOverseas && d.address_components) {
        body.address_components = d.address_components;
    }

    try {
        const r = await fetch('/admin/curations/places/' + replaceTargetId + '/replace', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(body),
        });
        const data = await r.json();
        if (data.success && data.place) {
            const p = data.place;
            const card = replaceTargetCard;
            const nameInp = card.querySelector('[data-field="place_name"]');
            const addrInp = card.querySelector('[data-field="address"]');
            const phoneInp = card.querySelector('[data-field="phone"]');
            const bnInp = card.querySelector('[data-field="building_name"]');
            if (nameInp) nameInp.value = p.place_name || '';
            if (addrInp) addrInp.value = p.address || '';
            if (phoneInp) phoneInp.value = p.phone || '';
            if (bnInp) bnInp.value = p.building_name || '';

            card.querySelector('.cur-manual-badge')?.remove();

            const gidRow = card.querySelector('[data-gid-row]');
            if (gidRow) {
                if (data.google?.google_place_id) {
                    const short = data.google.google_place_id.length > 20 ? data.google.google_place_id.substring(0, 20) + '…' : data.google.google_place_id;
                    const info = data.google.review_count ? ' (' + data.google.rating + '점/' + data.google.review_count + '개)' : '';
                    gidRow.innerHTML = '<span class="cur-place__gid-tag" title="' + esc(data.google.google_place_id) + '">G ' + esc(short) + info + '</span>'
                        + '<button type="button" class="cur-place__gid-btn" onclick="matchGoogle(' + replaceTargetId + ',this)" title="재검색">🔄</button>'
                        + '<button type="button" class="cur-place__gid-btn cur-place__gid-btn--del" onclick="clearGoogle(' + replaceTargetId + ',this)" title="해제">✕</button>';
                } else {
                    gidRow.innerHTML = '<button type="button" class="cur-place__gid-btn" onclick="matchGoogle(' + replaceTargetId + ',this)">G 매칭</button>';
                }
            }

            if (!replaceIsOverseas) {
                enrichNaverData(replaceTargetId, p.place_name, p.latitude, p.longitude, p.address);
            }

            closeReplace();
        } else {
            alert(data.message || '교체 실패');
            btn.disabled = false;
            btn.textContent = '교체 확정';
        }
    } catch(e) {
        alert('교체 실패: ' + e.message);
        btn.disabled = false;
        btn.textContent = '교체 확정';
    }
}

function closeReplace() {
    document.getElementById('replaceModal').classList.remove('is-open');
    document.getElementById('replaceConfirmBtn').textContent = '교체 확정';
    document.getElementById('replaceConfirmBtn').disabled = false;
    replaceTargetId = null;
    replaceTargetCard = null;
    replaceSelectedData = null;
    replaceSearchResults = [];
}

document.getElementById('replaceModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeReplace();
});

function renumber() {
    const cards = document.querySelectorAll('.cur-place');
    const isCourse = document.getElementById('curType').value === 'course';
    cards.forEach((card, i) => {
        card.querySelector('.cur-place__num').textContent = i + 1;
        const btns = card.querySelectorAll('.cur-place__order-btn');
        if (isCourse) {
            const myDay = card.querySelector('[data-field="day_number"]')?.value || '';
            let prev = card.previousElementSibling;
            while (prev && prev.classList.contains('cur-day-divider')) prev = prev.previousElementSibling;
            const canUp = prev && prev.classList.contains('cur-place') && (prev.querySelector('[data-field="day_number"]')?.value || '') === myDay;
            let next = card.nextElementSibling;
            while (next && next.classList.contains('cur-day-divider')) next = next.nextElementSibling;
            const canDown = next && next.classList.contains('cur-place') && (next.querySelector('[data-field="day_number"]')?.value || '') === myDay;
            if (btns[0]) btns[0].disabled = !canUp;
            if (btns[1]) btns[1].disabled = !canDown;
        } else {
            if (btns[0]) btns[0].disabled = i === 0;
            if (btns[1]) btns[1].disabled = i === cards.length - 1;
        }
    });
    document.getElementById('placeCount').textContent = cards.length + '개';
}

async function saveOrder() {
    const ids = Array.from(document.querySelectorAll('.cur-place')).map(c => +c.dataset.placeId);
    try {
        const r = await fetch('/admin/curations/' + curationId + '/reorder', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ ids }),
        });
        const d = await r.json();
        if (!d.success) throw new Error('저장 실패');
    } catch (e) {
        alert('순서 저장 실패: ' + e.message);
        location.reload();
    }
}

function movePlaceUp(btn) {
    const card = btn.closest('.cur-place');
    let prev = card.previousElementSibling;
    while (prev && prev.classList.contains('cur-day-divider')) prev = prev.previousElementSibling;
    if (!prev || !prev.classList.contains('cur-place')) return;
    if (document.getElementById('curType').value === 'course') {
        const myDay = card.querySelector('[data-field="day_number"]')?.value || '';
        if ((prev.querySelector('[data-field="day_number"]')?.value || '') !== myDay) return;
    }
    card.parentNode.insertBefore(card, prev);
    renumber();
    saveOrder();
}

function movePlaceDown(btn) {
    const card = btn.closest('.cur-place');
    let next = card.nextElementSibling;
    while (next && next.classList.contains('cur-day-divider')) next = next.nextElementSibling;
    if (!next || !next.classList.contains('cur-place')) return;
    if (document.getElementById('curType').value === 'course') {
        const myDay = card.querySelector('[data-field="day_number"]')?.value || '';
        if ((next.querySelector('[data-field="day_number"]')?.value || '') !== myDay) return;
    }
    card.parentNode.insertBefore(next, card);
    renumber();
    saveOrder();
}

// Place drag-and-drop (handle only)
(function() {
    const list = document.getElementById('placeList');
    if (!list) return;
    let dragEl = null;
    let handleGrabbed = false;

    list.addEventListener('mousedown', function(e) {
        handleGrabbed = !!e.target.closest('.cur-place__handle');
    });

    list.addEventListener('dragstart', function(e) {
        if (e.target.closest('.cur-place__photo')) return;
        if (!handleGrabbed) { e.preventDefault(); return; }
        dragEl = e.target.closest('.cur-place');
        if (!dragEl) { e.preventDefault(); return; }
        handleGrabbed = false;
        dragEl.classList.add('is-place-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', dragEl.dataset.placeId);
    });

    list.addEventListener('dragover', function(e) {
        if (!dragEl || e.dataTransfer.types.includes('text/x-photo-reorder')) return;
        const target = e.target.closest('.cur-place');
        if (!target || target === dragEl) return;
        if (document.getElementById('curType').value === 'course') {
            const dragDay = dragEl.querySelector('[data-field="day_number"]')?.value || '';
            const targetDay = target.querySelector('[data-field="day_number"]')?.value || '';
            if (dragDay !== targetDay) return;
        }
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        list.querySelectorAll('.cur-place').forEach(c => c.classList.remove('is-place-over'));
        target.classList.add('is-place-over');
    });

    list.addEventListener('dragleave', function(e) {
        const target = e.target.closest('.cur-place');
        if (target) target.classList.remove('is-place-over');
    });

    list.addEventListener('drop', function(e) {
        if (!dragEl || e.dataTransfer.types.includes('text/x-photo-reorder')) return;
        e.preventDefault();
        const target = e.target.closest('.cur-place');
        if (!target || target === dragEl) return;
        if (document.getElementById('curType').value === 'course') {
            const dragDay = dragEl.querySelector('[data-field="day_number"]')?.value || '';
            const targetDay = target.querySelector('[data-field="day_number"]')?.value || '';
            if (dragDay !== targetDay) return;
        }
        const cards = Array.from(list.querySelectorAll('.cur-place'));
        const fromIdx = cards.indexOf(dragEl);
        const toIdx = cards.indexOf(target);
        if (fromIdx < toIdx) {
            target.parentNode.insertBefore(dragEl, target.nextSibling);
        } else {
            target.parentNode.insertBefore(dragEl, target);
        }
        list.querySelectorAll('.cur-place').forEach(c => c.classList.remove('is-place-over'));
        renumber();
        saveOrder();
    });

    list.addEventListener('dragend', function() {
        if (dragEl) dragEl.classList.remove('is-place-dragging');
        list.querySelectorAll('.cur-place').forEach(c => c.classList.remove('is-place-over'));
        dragEl = null;
    });

    // Make place cards draggable via handle
    list.querySelectorAll('.cur-place').forEach(c => c.setAttribute('draggable', 'true'));
    new MutationObserver(() => {
        list.querySelectorAll('.cur-place:not([draggable])').forEach(c => c.setAttribute('draggable', 'true'));
    }).observe(list, { childList: true });
})();

document.addEventListener('click', (e) => {
    if (!e.target.closest('.cur-search-wrap')) searchResults.classList.remove('is-open');
});

// ── 사진 관리 ──

function rebuildPhotos(pid, photos) {
    const wrap = document.querySelector('.cur-place__photos[data-pid="'+pid+'"]');
    if (!wrap) return;
    let h = '';
    photos.forEach((p, i) => {
        h += '<div class="cur-place__photo" draggable="true" data-pidx="'+i+'"><img src="'+esc(p.thumb)+'" alt=""><button type="button" class="cur-place__photo-del" onclick="deletePhoto('+pid+','+i+',this)">✕</button></div>';
    });
    if (photos.length < PHOTO_MAX) {
        h += '<label class="cur-place__photo-add">+<input type="file" accept="image/*" multiple hidden onchange="uploadPhotos('+pid+',this)"></label>';
        h += '<div class="cur-place__url-row"><textarea rows="3" placeholder="이미지 URL (여러 줄 가능)" class="cur-url-input"></textarea><button type="button" class="ad-btn ad-btn--sm cur-url-btn" onclick="uploadFromUrl('+pid+',this)">URL</button></div>';
        h += '<div class="cur-place__hint">URL 여러 개 줄바꿈 입력 → Ctrl+Enter 또는 URL 버튼 · Ctrl+V 붙여넣기 · 드래그앤드롭</div>';
    }
    wrap.innerHTML = h;
}

function photoCount(pid) {
    const w = document.querySelector('.cur-place__photos[data-pid="'+pid+'"]');
    return w ? w.querySelectorAll('.cur-place__photo').length : 0;
}

function addLoader(wrap) {
    const el = document.createElement('div');
    el.className = 'cur-place__photo-loading';
    el.textContent = '⏳';
    const add = wrap.querySelector('.cur-place__photo-add');
    if (add) wrap.insertBefore(el, add); else wrap.prepend(el);
    return el;
}

async function uploadFiles(pid, fileList) {
    const wrap = document.querySelector('.cur-place__photos[data-pid="'+pid+'"]');
    if (!wrap) return;
    const remaining = PHOTO_MAX - photoCount(pid);
    if (remaining <= 0) { alert('최대 ' + PHOTO_MAX + '장까지 등록할 수 있습니다.'); return; }
    const imgs = Array.from(fileList).filter(f => f.type.startsWith('image/'));
    if (!imgs.length) return;
    const toUpload = imgs.slice(0, remaining);
    if (imgs.length > remaining) alert('최대 ' + PHOTO_MAX + '장 제한으로 ' + toUpload.length + '장만 업로드합니다.');
    const loader = addLoader(wrap);
    const fd = new FormData();
    toUpload.forEach(f => fd.append('photos[]', f));
    try {
        const r = await fetch('/admin/curations/places/'+pid+'/photos', {
            method: 'POST', headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}, body: fd,
        });
        const data = await r.json();
        if (data.success) rebuildPhotos(pid, data.photos);
        else { loader.remove(); alert(data.error || '업로드 실패'); }
    } catch(e) { loader.remove(); alert('업로드 실패: '+e.message); }
}

function uploadPhotos(pid, input) { uploadFiles(pid, input.files); input.value = ''; }

async function uploadFromUrl(pid, btn) {
    const wrap = btn.closest('.cur-place__photos') || document.querySelector('.cur-place__photos[data-pid="'+pid+'"]');
    const ta = wrap.querySelector('.cur-url-input');
    const urls = ta.value.split('\n').map(s => s.trim()).filter(Boolean);
    if (!urls.length) { ta.focus(); return; }
    const bad = urls.find(u => !/^https?:\/\//i.test(u));
    if (bad) { alert('유효하지 않은 URL:\n' + bad); return; }
    const remain = PHOTO_MAX - photoCount(pid);
    if (remain <= 0) { alert('최대 ' + PHOTO_MAX + '장까지 등록할 수 있습니다.'); return; }
    const batch = urls.slice(0, remain);
    if (batch.length < urls.length) alert((urls.length - batch.length) + '개 URL은 ' + PHOTO_MAX + '장 제한으로 건너뜁니다.');
    const origText = btn.textContent;
    btn.disabled = true; btn.textContent = '0/' + batch.length + ' 처리중…';
    const loaders = batch.map(() => addLoader(wrap));
    let lastPhotos = null, fail = [];
    for (let i = 0; i < batch.length; i++) {
        btn.textContent = (i+1) + '/' + batch.length + ' 처리중…';
        try {
            const r = await fetch('/admin/curations/places/'+pid+'/photos/url', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
                body: JSON.stringify({url: batch[i]}),
            });
            const data = await r.json();
            if (data.success) { lastPhotos = data.photos; loaders[i]?.remove(); }
            else { loaders[i]?.remove(); fail.push((i+1) + ': ' + (data.error || '실패')); }
        } catch(e) { loaders[i]?.remove(); fail.push((i+1) + ': ' + e.message); }
    }
    if (lastPhotos) rebuildPhotos(pid, lastPhotos);
    else loaders.forEach(l => l?.remove());
    if (fail.length) alert('일부 실패:\n' + fail.join('\n'));
    btn.disabled = false; btn.textContent = origText;
}

async function deletePhoto(pid, idx, btn) {
    if (!confirm('이 사진을 삭제할까요?')) return;
    try {
        const r = await fetch('/admin/curations/places/'+pid+'/photos', {
            method: 'DELETE',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
            body: JSON.stringify({index: idx}),
        });
        const data = await r.json();
        if (data.success) rebuildPhotos(pid, data.photos);
    } catch(e) { alert('삭제 실패'); }
}

async function reorderPhotos(pid, fromIdx, toIdx) {
    const wrap = document.querySelector('.cur-place__photos[data-pid="'+pid+'"]');
    const count = wrap.querySelectorAll('.cur-place__photo').length;
    const order = Array.from({length: count}, (_, i) => i);
    const [moved] = order.splice(fromIdx, 1);
    order.splice(toIdx, 0, moved);
    try {
        const r = await fetch('/admin/curations/places/'+pid+'/photos/reorder', {
            method: 'PUT',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
            body: JSON.stringify({order}),
        });
        const data = await r.json();
        if (data.success) rebuildPhotos(pid, data.photos);
    } catch(e) { alert('순서 변경 실패'); }
}

// ── 드래그앤드롭 · 붙여넣기 ──
let photoDragPid = null, photoDragFrom = -1;

document.addEventListener('dragstart', function(e) {
    const ph = e.target.closest('.cur-place__photo[draggable]');
    if (!ph) return;
    photoDragPid = parseInt(ph.closest('.cur-place__photos').dataset.pid);
    photoDragFrom = parseInt(ph.dataset.pidx);
    ph.classList.add('is-drag-src');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/x-photo-reorder', '1');
});

document.addEventListener('dragend', function() {
    photoDragPid = null; photoDragFrom = -1;
    document.querySelectorAll('.is-drag-src,.is-drag-over,.is-dragover').forEach(el =>
        el.classList.remove('is-drag-src','is-drag-over','is-dragover'));
});

document.addEventListener('dragover', function(e) {
    if (photoDragPid !== null) {
        const ph = e.target.closest('.cur-place__photo[draggable]');
        if (ph && parseInt(ph.closest('.cur-place__photos').dataset.pid) === photoDragPid) {
            e.preventDefault(); e.dataTransfer.dropEffect = 'move';
            document.querySelectorAll('.is-drag-over').forEach(el => el.classList.remove('is-drag-over'));
            if (parseInt(ph.dataset.pidx) !== photoDragFrom) ph.classList.add('is-drag-over');
        }
        return;
    }
    if (e.dataTransfer.types.includes('Files')) {
        const wrap = e.target.closest('.cur-place__photos');
        if (wrap) { e.preventDefault(); e.dataTransfer.dropEffect = 'copy'; wrap.classList.add('is-dragover'); }
    }
});

document.addEventListener('dragleave', function(e) {
    const wrap = e.target.closest('.cur-place__photos');
    if (wrap && !wrap.contains(e.relatedTarget)) wrap.classList.remove('is-dragover');
});

document.addEventListener('drop', function(e) {
    if (photoDragPid !== null) {
        const ph = e.target.closest('.cur-place__photo[draggable]');
        if (ph) {
            e.preventDefault();
            const toIdx = parseInt(ph.dataset.pidx);
            if (photoDragFrom !== toIdx) reorderPhotos(photoDragPid, photoDragFrom, toIdx);
        }
        return;
    }
    const wrap = e.target.closest('.cur-place__photos');
    if (wrap && e.dataTransfer.files.length) {
        e.preventDefault(); wrap.classList.remove('is-dragover');
        uploadFiles(parseInt(wrap.dataset.pid), e.dataTransfer.files);
    }
});

document.addEventListener('paste', function(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
    const wrap = e.target.closest('.cur-place__photos');
    if (!wrap) return;
    const images = [];
    for (const item of (e.clipboardData?.items || [])) {
        if (item.type.startsWith('image/')) { const f = item.getAsFile(); if (f) images.push(f); }
    }
    if (images.length) { e.preventDefault(); uploadFiles(parseInt(wrap.dataset.pid), images); }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey) && e.target.classList.contains('cur-url-input')) {
        e.preventDefault();
        const wrap = e.target.closest('.cur-place__photos');
        const btn = wrap.querySelector('.cur-url-btn');
        uploadFromUrl(parseInt(wrap.dataset.pid), btn);
    }
});

// 통합 저장: 메인 폼 제출 시 모든 장소 변경사항도 함께 저장
document.getElementById('curForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = this;

    if (document.getElementById('curType').value === 'course') {
        const n = getCurNights(), d = getCurDays();
        if (n !== null && d > 0 && n >= d) {
            alert(n + '박이면 일수는 ' + (n + 1) + '일 이상이어야 합니다.');
            return;
        }
        if (d > 0) {
            const overflow = [];
            document.querySelectorAll('.cur-place').forEach(card => {
                const sel = card.querySelector('[data-field="day_number"]');
                const val = sel ? parseInt(sel.value) : 0;
                if (val > d) overflow.push(card.querySelector('[data-field="place_name"]').value);
            });
            if (overflow.length) {
                alert(d + '일차를 초과하는 장소:\n' + overflow.join(', ') + '\n\n일차를 재지정해주세요.');
                return;
            }
        }
    }

    const cards = document.querySelectorAll('.cur-place');
    const promises = [];

    cards.forEach(card => {
        const placeId = card.dataset.placeId;
        if (!placeId) return;

        const fields = {};
        let hasField = false;
        card.querySelectorAll('[data-field]').forEach(inp => {
            fields[inp.dataset.field] = inp.value;
            hasField = true;
        });
        if (!hasField) return;

        promises.push(
            fetch('/admin/curations/places/' + placeId, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(fields),
            }).catch(() => null)
        );
    });

    if (promises.length) await Promise.all(promises);
    form.submit();
});

// 발행 토글
const toggleBtn = document.getElementById('togglePublishBtn');
if (toggleBtn) {
    toggleBtn.addEventListener('click', async () => {
        toggleBtn.disabled = true;
        if (document.getElementById('curType').value === 'course' && toggleBtn.textContent.includes('발행하기')) {
            const dur = getCurDays();
            const cards = document.querySelectorAll('.cur-place');
            let unassigned = 0;
            const dayUsed = new Set();
            cards.forEach(card => {
                const sel = card.querySelector('[data-field="day_number"]');
                if (!sel || !sel.value) unassigned++;
                else dayUsed.add(parseInt(sel.value));
            });
            const warnings = [];
            if (unassigned > 0) warnings.push('일차 미지정 장소 ' + unassigned + '곳');
            if (dur) {
                const empty = [];
                for (let d = 1; d <= dur; d++) { if (!dayUsed.has(d)) empty.push(d + '일차'); }
                if (empty.length) warnings.push('장소 없는 일차: ' + empty.join(', '));
            }
            if (warnings.length && !confirm('코스 확인사항:\n- ' + warnings.join('\n- ') + '\n\n그래도 발행할까요?')) {
                toggleBtn.disabled = false;
                return;
            }
        }
        try {
            const r = await fetch('/admin/curations/' + curationId + '/toggle-publish', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            location.reload();
        } catch(e) { alert('처리 실패'); toggleBtn.disabled = false; }
    });
}

function rejectThis() {
    const reason = prompt('반려 사유를 입력해주세요:');
    if (!reason) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/curations/' + curationId + '/reject';
    form.innerHTML = '<input type="hidden" name="_token" value="' + csrf + '">'
        + '<input type="hidden" name="reason" value="' + reason.replace(/"/g, '&quot;') + '">';
    document.body.appendChild(form);
    form.submit();
}

// 삭제
const delBtn = document.getElementById('deleteCurBtn');
if (delBtn) {
    delBtn.addEventListener('click', async () => {
        if (!confirm('정말 삭제할까요?')) return;
        try {
            const r = await fetch('/admin/curations/' + curationId, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            location.href = '/admin/curations';
        } catch(e) { alert('삭제 실패'); }
    });
}

// ── 영업시간 토글 ──
function toggleHoursEdit(btn) {
    const wrap = btn.closest('.cur-hours-wrap');
    const display = wrap.querySelector('.cur-hours');
    const input = wrap.querySelector('[data-field="opening_hours"]');
    display.style.display = 'none';
    input.style.display = '';
    input.focus();
}

// ── TourAPI 이미지 검색 ──
let tourPlaceId = null;
let tourSelected = [];

async function searchTourImages(pid) {
    tourPlaceId = pid;
    tourSelected = [];
    const modal = document.getElementById('tourModal');
    const body = document.getElementById('tourModalBody');
    const count = document.getElementById('tourModalCount');
    const confirm = document.getElementById('tourModalConfirm');
    const remaining = 3 - photoCount(pid);

    body.innerHTML = '<div class="tour-modal__empty">🔍 검색 중...</div>';
    count.textContent = '0장 선택 (최대 ' + remaining + '장)';
    confirm.disabled = true;
    modal.classList.add('is-open');

    if (remaining <= 0) {
        body.innerHTML = '<div class="tour-modal__empty">이미 3장이 등록되어 있어요. 기존 사진을 삭제한 뒤 시도해주세요.</div>';
        return;
    }

    try {
        const r = await fetch('/admin/curations/places/' + pid + '/tour-images', {
            headers: { 'Accept': 'application/json' },
        });
        const data = await r.json();
        if (data.error) {
            body.innerHTML = '<div class="tour-modal__empty">' + esc(data.error) + '</div>';
            return;
        }
        if (!data.images || !data.images.length) {
            body.innerHTML = '<div class="tour-modal__empty">📭 ' + esc(data.message || '공식 이미지를 찾지 못했어요') + '<br><small>수동으로 URL을 등록해주세요</small></div>';
            return;
        }

        body.innerHTML = '<div class="tour-modal__grid">' + data.images.map((img, i) =>
            '<div class="tour-modal__img" data-idx="' + i + '" data-url="' + esc(img.original) + '" onclick="toggleTourImg(this,' + remaining + ')">'
            + '<img src="' + esc(img.thumbnail) + '" alt="" loading="lazy">'
            + '<div class="tour-modal__img-check">✓</div>'
            + '<div class="tour-modal__source">' + esc(img.source) + '</div>'
            + '</div>'
        ).join('') + '</div>';
    } catch(e) {
        body.innerHTML = '<div class="tour-modal__empty">네트워크 오류: ' + esc(e.message) + '</div>';
    }
}

function toggleTourImg(el, max) {
    const url = el.dataset.url;
    if (el.classList.contains('is-selected')) {
        el.classList.remove('is-selected');
        tourSelected = tourSelected.filter(u => u !== url);
    } else {
        if (tourSelected.length >= max) {
            alert('최대 ' + max + '장까지 선택할 수 있어요.');
            return;
        }
        el.classList.add('is-selected');
        tourSelected.push(url);
    }
    const remaining = 3 - photoCount(tourPlaceId);
    document.getElementById('tourModalCount').textContent = tourSelected.length + '장 선택 (최대 ' + remaining + '장)';
    document.getElementById('tourModalConfirm').disabled = tourSelected.length === 0;
}

async function confirmTourImages() {
    if (!tourSelected.length || !tourPlaceId) return;
    const btn = document.getElementById('tourModalConfirm');
    btn.disabled = true;
    btn.textContent = '등록 중...';

    const wrap = document.querySelector('.cur-place__photos[data-pid="'+tourPlaceId+'"]');
    let fail = [];
    for (let i = 0; i < tourSelected.length; i++) {
        btn.textContent = (i+1) + '/' + tourSelected.length + ' 처리중…';
        try {
            const r = await fetch('/admin/curations/places/'+tourPlaceId+'/photos/url', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
                body: JSON.stringify({ url: tourSelected[i], source: '한국관광공사' }),
            });
            const data = await r.json();
            if (data.success) { rebuildPhotos(tourPlaceId, data.photos); }
            else { fail.push((i+1) + ': ' + (data.error || '실패')); }
        } catch(e) { fail.push((i+1) + ': ' + e.message); }
    }

    closeTourModal();
    if (fail.length) alert('일부 실패:\n' + fail.join('\n'));
}

function closeTourModal() {
    document.getElementById('tourModal').classList.remove('is-open');
    document.getElementById('tourModalConfirm').textContent = '선택한 이미지 등록';
    tourPlaceId = null;
    tourSelected = [];
}

document.getElementById('tourModal').addEventListener('click', function(e) {
    if (e.target === this) closeTourModal();
});

renumber();
if (cType === 'course' && curDaysVal) rebuildDayGroups();
updateDurationLabel();
if (document.getElementById('curNights')?.value !== '' && document.getElementById('curDaysField')?.value !== '') daysAutoFill = false;

function toggleRegionEdit(placeId) {
    const el = document.getElementById('regionEdit' + placeId);
    if (el) el.classList.toggle('is-open');
}

async function saveRegion(placeId) {
    const el = document.getElementById('regionEdit' + placeId);
    if (!el) return;
    const cc = el.querySelector('[data-region="country_code"]').value.trim();
    const l1 = el.querySelector('[data-region="region_l1"]').value.trim();
    const l2 = el.querySelector('[data-region="region_l2"]').value.trim();
    try {
        const r = await fetch('/admin/curations/places/' + placeId + '/region', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ country_code: cc, region_l1: l1, region_l2: l2 }),
        });
        const data = await r.json();
        if (data.success) {
            el.classList.remove('is-open');
            const row = el.previousElementSibling;
            if (row && row.classList.contains('cur-region-row')) {
                const tags = row.querySelectorAll('.cur-region-tag');
                if (tags[0]) tags[0].textContent = cc;
                if (tags[1]) tags[1].textContent = l1;
                if (tags[2]) { tags[2].textContent = l2; tags[2].style.display = l2 ? '' : 'none'; }
                else if (l2) {
                    const tag = document.createElement('span');
                    tag.className = 'cur-region-tag';
                    tag.textContent = l2;
                    row.querySelector('button').before(tag);
                }
            }
        }
    } catch(e) { alert('저장 실패'); }
}

function toggleRegionManual(on) {
    const inp = document.getElementById('regionLabelInput');
    if (on) {
        inp.removeAttribute('readonly');
        inp.style.background = '';
    } else {
        inp.setAttribute('readonly', '');
        inp.style.background = '#f5f5f5';
    }
}
</script>
@endpush
@endif
