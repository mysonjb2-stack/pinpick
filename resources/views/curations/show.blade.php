@extends('layouts.app')
@section('page_title', $curation->title . ' | 핀픽')
@section('app_class', 'pp-app--shared pp-app--curpage')

@section('og_title', $curation->title)
@section('og_description', $curation->description ?: ('장소 ' . $curation->places->count() . '곳 · 나만의 장소, 나만의 지도 핀픽'))
@section('og_image', $curation->cover_image ? asset('storage/' . $curation->cover_image) : asset('images/og-image.png'))

@push('head')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "ItemList",
    "name": @json($curation->title),
    "description": @json($curation->description ?: ''),
    "numberOfItems": {{ $curation->places->count() }},
    "itemListElement": [
        @foreach($curation->places as $i => $p)
        {
            "@@type": "ListItem",
            "position": {{ $i + 1 }},
            "item": {
                "@@type": "Place",
                "name": @json($p->place_name),
                "address": @json($p->address ?: '')
            }
        }@if(!$loop->last),@endif
        @endforeach
    ]
}
</script>
<style>
/* ── Container: 480px app shell ── */
.pp-app--curpage {
  overflow: hidden; height: 100vh; height: 100dvh;
  position: relative; padding-bottom: 0;
}
.pp-app--curpage .pp-nav { display: none; }

/* ── Map: fills container ── */
.pp-cur-map { position: absolute; inset: 0; z-index: 1; }
.pp-cur-map__el { width: 100%; height: 100%; }

/* ── Header overlay: inside container ── */
.pp-cur-hdr {
  position: absolute; top: 0; left: 0; right: 0; z-index: 300;
  display: flex; align-items: center; justify-content: space-between;
  height: 50px; padding: 0 6px;
  pointer-events: none;
}
.pp-cur-hdr__btn {
  pointer-events: auto;
  width: 38px; height: 38px; border-radius: 50%;
  background: rgba(255,255,255,.92); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
  border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
  color: var(--pp-text); box-shadow: 0 1px 4px rgba(0,0,0,.15);
}

/* ── Bottom Sheet: inside container ── */
.pp-cur-sheet {
  position: absolute; left: 0; right: 0; bottom: 0; z-index: 200;
  height: 88%;
  background: var(--pp-bg, #fff);
  border-radius: 16px 16px 0 0;
  box-shadow: 0 -2px 16px rgba(0,0,0,.12);
  display: flex; flex-direction: column;
  will-change: transform;
  transition: transform .32s cubic-bezier(.22,1,.36,1);
  transform: translateY(41%);
}
.pp-cur-sheet.is-dragging { transition: none; }

.pp-cur-sheet__handle {
  flex-shrink: 0; padding: 10px 0 4px; cursor: grab;
  display: flex; justify-content: center; touch-action: none;
}
.pp-cur-sheet__handle::after {
  content: ''; width: 36px; height: 4px; border-radius: 2px;
  background: var(--pp-line, #ddd);
}

.pp-cur-sheet__scroll {
  flex: 1; overflow-y: auto; overflow-x: hidden;
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch;
}
.pp-cur-sheet.is-peek .pp-cur-sheet__scroll { overflow-y: hidden; }

/* Sheet header */
.pp-cur-sheet__hdr { padding: 4px 18px 12px; cursor: grab; touch-action: none; user-select: none; }
.pp-cur-sheet__title {
  font-size: 20px; font-weight: 800; color: var(--pp-text);
  margin: 0; letter-spacing: -0.02em; line-height: 1.3;
}
.pp-cur-sheet__summary {
  font-size: 13px; color: var(--pp-text-sub); margin: 4px 0 0;
}

/* Detail area */
.pp-cur-sheet__detail { padding: 0 18px 12px; }
.pp-cur-sheet.is-peek .pp-cur-sheet__detail { display: none; }

.pp-cur__regions { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 8px; }
.pp-cur__region-chip {
  display: inline-block; padding: 3px 10px; border-radius: 6px;
  background: var(--pp-chip-bg); font-size: 12px; font-weight: 600;
  color: var(--pp-text-sub);
}
.pp-cur__desc {
  font-size: 14px; color: var(--pp-text); line-height: 1.6;
  margin: 0 0 6px; white-space: pre-line;
}

/* Day filter */
.pp-cur__days {
  display: flex; gap: 6px; padding: 6px 18px 10px;
  overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none;
}
.pp-cur__days::-webkit-scrollbar { display: none; }
.pp-cur__day-btn {
  flex-shrink: 0; padding: 5px 14px; border-radius: 999px;
  border: 1.5px solid var(--pp-line); background: var(--pp-bg);
  font-size: 13px; font-weight: 600; color: var(--pp-text-sub); cursor: pointer;
}
.pp-cur__day-btn.is-active {
  background: var(--pp-primary); border-color: var(--pp-primary); color: #fff;
}

/* List header */
.pp-cur-sheet__list-hdr {
  display: flex; align-items: center; justify-content: space-between;
  padding: 8px 18px 4px;
}
.pp-cur-sheet__list-count { font-size: 13px; font-weight: 600; color: var(--pp-text-sub); }
.pp-cur-sheet__sel-all {
  border: none; background: none; font-size: 13px; font-weight: 600;
  color: var(--pp-primary); cursor: pointer; padding: 4px 0;
}

/* ── Place Card ── */
.pp-cur-card {
  margin: 0 14px 12px; padding: 14px;
  border: 1px solid var(--pp-line); border-radius: 14px;
  transition: border-color .15s, background .15s;
}
.pp-cur-card.is-selected { border-color: var(--pp-primary); background: rgba(43,33,30,.04); }
.pp-cur-card.is-focused { border-color: var(--pp-primary, #e67e22); background: #fef9f4; }

.pp-cur-card__top {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 8px;
}
.pp-cur-card__info { flex: 1; min-width: 0; cursor: pointer; }
.pp-cur-card__name {
  font-size: 15px; font-weight: 700; color: var(--pp-text); margin: 0;
  display: flex; align-items: center; gap: 6px;
}
.pp-cur-card__num {
  display: inline-flex; align-items: center; justify-content: center;
  width: 20px; height: 20px; border-radius: 50%;
  background: var(--pp-primary, #2b211e); color: #fff;
  font-size: 11px; font-weight: 700; flex-shrink: 0;
}
.pp-cur-card__sub {
  font-size: 12px; color: var(--pp-text-sub); margin: 2px 0 0;
  display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
}
.pp-cur-card__cat {
  font-size: 11px; font-weight: 600; color: var(--pp-text-sub);
  background: var(--pp-chip-bg); border-radius: 4px; padding: 2px 6px;
}
.pp-cur-card__maplink {
  font-size: 11px; color: var(--pp-primary); text-decoration: none; font-weight: 500;
}

.pp-cur-card__add {
  flex-shrink: 0;
  width: 34px; height: 34px; border-radius: 50%;
  border: 1.5px solid var(--pp-line); background: #fff;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all .15s; color: var(--pp-text-sub);
}
.pp-cur-card__add:active { transform: scale(.9); }
.pp-cur-card__check { display: none; }
.pp-cur-card.is-selected .pp-cur-card__add { background: var(--pp-primary); border-color: var(--pp-primary); color: #fff; }
.pp-cur-card.is-selected .pp-cur-card__plus { display: none; }
.pp-cur-card.is-selected .pp-cur-card__check { display: block; }

/* Photo strip */
.pp-cur-card__photos {
  display: flex; gap: 6px; margin-top: 10px;
  overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none;
}
.pp-cur-card__photos::-webkit-scrollbar { display: none; }
.pp-cur-card__photo {
  width: 100px; height: 100px; border-radius: 10px;
  overflow: hidden; flex-shrink: 0; background: var(--pp-chip-bg); cursor: pointer;
}
.pp-cur-card__photo img { width: 100%; height: 100%; object-fit: cover; display: block; }

/* Lightbox */
.pp-cur-lb { position: fixed; inset: 0; z-index: 10000; display: none; align-items: center; justify-content: center; background: rgba(0,0,0,.88); }
.pp-cur-lb.is-open { display: flex; }
.pp-cur-lb__close { position: absolute; top: 14px; right: 14px; width: 36px; height: 36px; border: none; background: rgba(255,255,255,.15); border-radius: 50%; color: #fff; font-size: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 2; }
.pp-cur-lb__img { max-width: 92vw; max-height: 85vh; object-fit: contain; border-radius: 8px; user-select: none; -webkit-user-select: none; }
.pp-cur-lb__nav { position: absolute; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; border: none; background: rgba(255,255,255,.18); border-radius: 50%; color: #fff; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.pp-cur-lb__nav--prev { left: 10px; }
.pp-cur-lb__nav--next { right: 10px; }
.pp-cur-lb__counter { position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); color: rgba(255,255,255,.7); font-size: 13px; font-weight: 600; }

/* Editor note */
.pp-cur-card__note {
  font-size: 13px; color: var(--pp-text-sub); margin: 8px 0 0;
  padding: 8px 12px; border-left: 3px solid var(--pp-primary, #e67e22);
  background: var(--pp-bg-soft, #faf7f5); border-radius: 0 6px 6px 0;
  font-style: italic; line-height: 1.5;
}
.pp-cur-card__source {
  font-size: 11.5px; color: var(--pp-text-sub); margin-top: 6px;
}
.pp-cur-card__source a { color: var(--pp-accent, #2f6fed); text-decoration: underline; text-underline-offset: 2px; }
.pp-cur-card__day {
  display: inline-block; padding: 2px 8px; border-radius: 4px;
  background: #dbeafe; color: #1d4ed8; font-size: 11px; font-weight: 600; margin-top: 4px;
}

/* ── CTA bar: inside container ── */
.pp-cur-cta {
  position: absolute; bottom: 0; left: 0; right: 0; z-index: 250;
  padding: 10px 18px calc(10px + env(safe-area-inset-bottom, 0px));
  background: rgba(255,255,255,.97); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
  border-top: 1px solid var(--pp-line);
}
.pp-cur-cta__btn { width: 100%; }
.pp-cur-cta__hint {
  text-align: center; font-size: 11.5px; color: var(--pp-text-sub, #999);
  margin: 6px 0 0; line-height: 1.4;
}

/* ── Map fit button: inside container ── */
.pp-cur-fit {
  position: absolute; z-index: 210;
  top: 58px; right: 10px;
  padding: 6px 14px; border-radius: 999px; border: none;
  background: #fff; color: var(--pp-text); font-size: 12px; font-weight: 600;
  box-shadow: 0 1px 5px rgba(0,0,0,.18); cursor: pointer;
  display: none;
}

/* ── Modal sheets: viewport fixed but width-clamped ── */
.pp-share__select-sheet, .pp-share__cat-sheet {
  position: fixed; inset: 0; z-index: 9000; display: none;
  align-items: flex-end; justify-content: center;
}
.pp-share__select-sheet.is-open, .pp-share__cat-sheet.is-open { display: flex; }
.pp-share__select-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.45); }
.pp-share__select-panel {
  position: relative; z-index: 1; width: 100%; max-width: 480px;
  background: var(--pp-bg); border-radius: 16px 16px 0 0;
  padding: 20px 18px calc(20px + env(safe-area-inset-bottom, 0px));
  max-height: 75vh; overflow-y: auto;
}
.pp-share__select-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.pp-share__select-header h3 { font-size: 16px; font-weight: 700; color: var(--pp-text); margin: 0; }
.pp-share__select-close { border: none; background: none; font-size: 20px; color: var(--pp-text-sub); cursor: pointer; padding: 4px; }
.pp-share__select-info { font-size: 14px; color: var(--pp-text); line-height: 1.6; margin-bottom: 14px; white-space: pre-line; }
.pp-share__select-actions { display: flex; flex-direction: column; gap: 8px; }
.pp-share__cat-list { flex: 1; overflow-y: auto; margin-bottom: 14px; }
.pp-share__cat-option { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--pp-line); cursor: pointer; }
.pp-share__cat-option:last-child { border-bottom: none; }
.pp-share__cat-option input { accent-color: var(--pp-primary); }
.pp-share__cat-name { font-size: 14px; color: var(--pp-text); }
.pp-share__cat-input-wrap { padding: 0 0 8px 28px; }
.pp-share__cat-input {
  width: 100%; padding: 8px 12px; border: 1.5px solid var(--pp-line);
  border-radius: 8px; font-size: 14px; background: var(--pp-bg);
  color: var(--pp-text);
}
.pp-share__cat-input:focus { border-color: var(--pp-primary); outline: none; }
.pp-share__cat-dup-hint { font-size: 12px; color: #e74c3c; margin-top: 4px; }
.pp-share__cat-existing { padding-left: 0; }

.pp-cur-sheet__bottom-pad { height: 80px; flex-shrink: 0; }
</style>
@endpush

@section('content')
{{-- Full-screen map --}}
<div class="pp-cur-map">
    <div class="pp-cur-map__el" id="curMap"></div>
</div>

{{-- Header overlay --}}
<div class="pp-cur-hdr">
    <button type="button" class="pp-cur-hdr__btn" id="curBack" aria-label="뒤로">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <button type="button" class="pp-cur-hdr__btn" id="curShareBtn" aria-label="공유">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
    </button>
</div>

{{-- Fit button --}}
<button type="button" class="pp-cur-fit" id="curFitBtn">전체 보기</button>

{{-- Bottom Sheet --}}
<div class="pp-cur-sheet is-mid" id="curSheet">
    <div class="pp-cur-sheet__handle" id="curSheetHandle"></div>

    <div class="pp-cur-sheet__scroll" id="curSheetScroll">
        {{-- Peek: always visible --}}
        <div class="pp-cur-sheet__hdr" id="curSheetHdr">
            <h1 class="pp-cur-sheet__title">{{ $curation->title }}</h1>
            <p class="pp-cur-sheet__summary">
                장소 {{ $curation->places->count() }}곳
                @if($curation->save_count > 0) · {{ number_format($curation->save_count) }}명이 담아갔어요 @endif
            </p>
        </div>

        {{-- Detail: visible from mid --}}
        <div class="pp-cur-sheet__detail">
            @if($curation->region_label)
                <div class="pp-cur__regions">
                    @foreach(array_map('trim', explode(',', $curation->region_label)) as $tag)
                        <span class="pp-cur__region-chip">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
            @if($curation->description)
                <p class="pp-cur__desc">{{ $curation->description }}</p>
            @endif
        </div>

        @if($curation->type === 'course')
        <div class="pp-cur__days" id="curDays">
            @php $days = $curation->places->pluck('day_number')->filter()->unique()->sort(); @endphp
            <button type="button" class="pp-cur__day-btn is-active" data-day="all">전체</button>
            @foreach($days as $d)
            <button type="button" class="pp-cur__day-btn" data-day="{{ $d }}">Day {{ $d }}</button>
            @endforeach
        </div>
        @endif

        <div class="pp-cur-sheet__list-hdr">
            <span class="pp-cur-sheet__list-count">장소 {{ $curation->places->count() }}곳</span>
            <button type="button" class="pp-cur-sheet__sel-all" id="curSelAll">전체선택</button>
        </div>

        {{-- Place cards --}}
        @foreach($curation->places as $i => $place)
        <div class="pp-cur-card" data-idx="{{ $i }}" data-lat="{{ $place->latitude }}" data-lng="{{ $place->longitude }}" data-id="{{ $place->id }}" data-day="{{ $place->day_number }}">
            <div class="pp-cur-card__top">
                <div class="pp-cur-card__info">
                    <h3 class="pp-cur-card__name">
                        <span class="pp-cur-card__num">{{ $i + 1 }}</span>
                        {{ $place->place_name }}
                    </h3>
                    <div class="pp-cur-card__sub">
                        @if($place->category_label)<span class="pp-cur-card__cat">{{ $place->category_label }}</span>@endif
                        @if($place->address)<span>{{ $place->address }}</span>@endif
                        @php
                            if ($place->is_overseas) {
                                $mapUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($place->place_name . ' ' . $place->address);
                            } else {
                                $mapUrl = 'https://map.naver.com/p/search/' . urlencode($place->place_name . ' ' . $place->address);
                            }
                        @endphp
                        <a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="pp-cur-card__maplink" onclick="event.stopPropagation()">지도 ↗</a>
                    </div>
                </div>
                <button type="button" class="pp-cur-card__add" data-place-id="{{ $place->id }}" aria-label="담기">
                    <svg class="pp-cur-card__plus" width="20" height="20" viewBox="0 0 22 22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="11" y1="5" x2="11" y2="17"/><line x1="5" y1="11" x2="17" y2="11"/></svg>
                    <svg class="pp-cur-card__check" width="20" height="20" viewBox="0 0 22 22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 11 10 15 16 7"/></svg>
                </button>
            </div>
            @if($place->photos && count($place->photos) > 0)
            <div class="pp-cur-card__photos">
                @foreach($place->photos as $photo)
                <div class="pp-cur-card__photo" data-full="{{ asset('storage/' . $photo) }}" onclick="openLightbox(this)">
                    <img src="{{ asset('storage/' . \App\Services\ImageProcessor::thumbPathFor($photo)) }}" alt="" loading="lazy">
                </div>
                @endforeach
            </div>
            @endif
            @if($place->editor_note)
                <p class="pp-cur-card__note">"{{ $place->editor_note }}"</p>
            @endif
            @if($place->source_channel)
                <p class="pp-cur-card__source">
                    {{ $place->source_channel }}
                    @if($place->source_date) {{ $place->source_date->format('Y.m.d') }} 소개@endif
                    @if($place->source_url)
                        · <a href="{{ $place->source_url }}" target="_blank" rel="noopener" onclick="event.stopPropagation()">원본 보기 ↗</a>
                    @endif
                </p>
            @endif
            @if($curation->type === 'course' && $place->day_number)
                <span class="pp-cur-card__day">Day {{ $place->day_number }}</span>
            @endif
        </div>
        @endforeach

        <div class="pp-cur-sheet__bottom-pad"></div>
    </div>
</div>

{{-- CTA --}}
<div class="pp-cur-cta" id="curCta">
    <button type="button" class="pp-btn pp-cur-cta__btn" id="curSaveBtn">이 장소들 내 핀픽에 담기</button>
    <p class="pp-cur-cta__hint" id="curGuestHint" style="display:none"></p>
</div>

{{-- Lightbox --}}
<div class="pp-cur-lb" id="curLb">
    <button type="button" class="pp-cur-lb__close" id="curLbClose">&times;</button>
    <button type="button" class="pp-cur-lb__nav pp-cur-lb__nav--prev" id="curLbPrev">&#8249;</button>
    <img class="pp-cur-lb__img" id="curLbImg" src="" alt="">
    <button type="button" class="pp-cur-lb__nav pp-cur-lb__nav--next" id="curLbNext">&#8250;</button>
    <div class="pp-cur-lb__counter" id="curLbCounter"></div>
</div>

{{-- Share sheet --}}
<div class="pp-share__select-sheet" id="curShareSheet">
    <div class="pp-share__select-backdrop" data-role="close"></div>
    <div class="pp-share__select-panel">
        <div class="pp-share__select-header">
            <h3>공유하기</h3>
            <button type="button" class="pp-share__select-close" data-role="close">✕</button>
        </div>
        <div class="pp-share__select-actions" style="gap:10px">
            <button type="button" class="pp-btn" id="curShareKakao">카카오톡 공유</button>
            <button type="button" class="pp-btn pp-btn--ghost" id="curShareCopy">링크 복사</button>
            <button type="button" class="pp-btn pp-btn--ghost" id="curShareNative" style="display:none">다른 앱으로 공유</button>
        </div>
    </div>
</div>

{{-- Guest sheet --}}
<div class="pp-share__select-sheet" id="curGuestSheet">
    <div class="pp-share__select-backdrop" data-role="close"></div>
    <div class="pp-share__select-panel">
        <div class="pp-share__select-header">
            <h3>게스트 저장 안내</h3>
            <button type="button" class="pp-share__select-close" data-role="close">✕</button>
        </div>
        <div class="pp-share__select-info" id="curGuestInfo"></div>
        <div class="pp-share__select-actions">
            <button type="button" class="pp-btn" id="curGuestPick">직접 고르기</button>
            <button type="button" class="pp-btn pp-btn--ghost" id="curGuestLogin">로그인하고 전부 저장</button>
        </div>
    </div>
</div>

{{-- Category sheet --}}
<div class="pp-share__cat-sheet" id="curCatSheet">
    <div class="pp-share__select-backdrop" data-role="close"></div>
    <div class="pp-share__select-panel">
        <div class="pp-share__select-header">
            <h3>저장할 카테고리 선택</h3>
            <button type="button" class="pp-share__select-close" data-role="close">✕</button>
        </div>
        <div class="pp-share__cat-list" id="curCatList">
            <label class="pp-share__cat-option">
                <input type="radio" name="cur_cat" value="__new__" checked>
                <span class="pp-share__cat-name">새 카테고리 만들기</span>
            </label>
            <div class="pp-share__cat-input-wrap" id="curCatInputWrap">
                <input type="text" id="curCatInput" class="pp-share__cat-input" maxlength="30" value="{{ $curation->title }}">
                <div class="pp-share__cat-dup-hint" id="curCatDupHint"></div>
            </div>
            <label class="pp-share__cat-option">
                <input type="radio" name="cur_cat" value="__existing__">
                <span class="pp-share__cat-name">기존 카테고리에 저장</span>
            </label>
            <div class="pp-share__cat-existing" id="curCatExisting" style="display:none"></div>
        </div>
        <div class="pp-share__select-actions">
            <button type="button" class="pp-btn" id="curCatSaveBtn">저장하기</button>
        </div>
    </div>
</div>

@php
    $placesJson = $curation->places->map(function($p) {
        return [
            'id' => $p->id,
            'name' => $p->place_name,
            'address' => $p->address,
            'building_name' => $p->building_name,
            'phone' => $p->phone,
            'lat' => $p->latitude,
            'lng' => $p->longitude,
            'category_label' => $p->category_label,
            'thumbnail_url' => $p->thumb_url,
            'external_place_id' => $p->external_place_id,
            'naver_place_id' => $p->naver_place_id,
            'google_place_id' => $p->google_place_id,
            'is_overseas' => (bool) $p->is_overseas,
            'day_number' => $p->day_number,
            'source_channel' => $p->source_channel,
        ];
    });
@endphp
<script src="https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId={{ config('services.naver_map.client_id') }}"></script>
<script src="https://t1.kakaocdn.net/kakao_js_sdk/2.7.4/kakao.min.js" integrity="sha384-DKYJZ8NLiK8MN4/C5P2dtSmLQ4KwPaoqAfyA/DQ/7hV+E1NASW+/MNlHjao0fzm" crossorigin="anonymous"></script>
<script>
(function() {
    const csrf = '{{ csrf_token() }}';
    const curationId = {{ $curation->id }};
    const curationType = '{{ $curation->type }}';
    const isAuth = {{ Auth::check() ? 'true' : 'false' }};
    const curTitle = @json($curation->title);
    const curDesc = @json($curation->description ?: '');
    const curUrl = location.href.split('?')[0];
    const curOgImage = @json($curation->cover_image ? asset('storage/' . $curation->cover_image) : asset('images/og-image.png'));
    const places = @json($placesJson);
    const userCats = @json($userCategories);
    const totalCount = places.length;
    const selected = new Set();
    let activeMarkerIdx = -1;
    let activeDay = 'all';
    let fullBounds = null;

    // ── Back ──
    document.getElementById('curBack').addEventListener('click', () => {
        if (history.length > 1 && document.referrer) history.back();
        else location.href = '/explore';
    });

    // ── Share ──
    const shareSheet = document.getElementById('curShareSheet');
    document.getElementById('curShareBtn').addEventListener('click', () => shareSheet.classList.add('is-open'));

    if (navigator.share) document.getElementById('curShareNative').style.display = '';
    document.getElementById('curShareNative').addEventListener('click', () => {
        navigator.share({ title: curTitle, text: curDesc, url: curUrl }).catch(() => {});
        shareSheet.classList.remove('is-open');
    });
    document.getElementById('curShareCopy').addEventListener('click', () => {
        navigator.clipboard.writeText(curUrl).then(() => showToast('링크가 복사됐어요'));
        shareSheet.classList.remove('is-open');
    });

    try { Kakao.init('{{ config("services.kakao.js_key") }}'); } catch(e) {}
    document.getElementById('curShareKakao').addEventListener('click', () => {
        try {
            Kakao.Share.sendDefault({
                objectType: 'feed',
                content: { title: curTitle, description: curDesc || '장소 ' + totalCount + '곳', imageUrl: curOgImage, link: { mobileWebUrl: curUrl, webUrl: curUrl } },
                buttons: [{ title: '장소 보기', link: { mobileWebUrl: curUrl, webUrl: curUrl } }],
            });
        } catch(e) { navigator.clipboard.writeText(curUrl).then(() => showToast('카카오톡 연결 실패 · 링크가 복사됐어요')); }
        shareSheet.classList.remove('is-open');
    });

    // ── Container ref ──
    const container = document.querySelector('.pp-app');
    const containerH = () => container.offsetHeight;

    // ── Map ──
    let map = null, markers = [];
    const pinSize = 28;
    function pinHtml(num, hl) {
        const bg = hl ? '#e67e22' : 'var(--pp-primary,#2b211e)';
        const sc = hl ? 'transform:scale(1.25);' : '';
        return '<div style="background:'+bg+';color:#fff;width:'+pinSize+'px;height:'+pinSize+'px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3);transition:transform .15s;'+sc+'">'+num+'</div>';
    }

    function sheetMidTop() { return Math.round(containerH() * 0.48); }
    function mapBottomPad() { return containerH() - sheetMidTop() + 20; }

    function calcOffsetCenter(lat, lng, zoom) {
        const ch = containerH();
        const offsetPx = Math.round(ch * 0.26);
        const mpp = 156543.03392 * Math.cos(lat * Math.PI / 180) / Math.pow(2, zoom);
        const latOff = offsetPx * mpp / 111320;
        return new naver.maps.LatLng(lat - latOff, lng);
    }

    function fitMapToAll() {
        if (!map) return;
        const vp = places.filter(p => p.lat && p.lng);
        if (!vp.length) return;
        if (vp.length === 1) {
            map.setZoom(14);
            map.setCenter(calcOffsetCenter(vp[0].lat, vp[0].lng, 14));
        } else if (vp.length >= 2) {
            map.fitBounds(fullBounds, { top: 40, right: 30, bottom: 40, left: 30 });
            var z = Math.min(map.getZoom() - 1, 14);
            map.setZoom(z);
            var midLat = (Math.min(...vp.map(p=>p.lat)) + Math.max(...vp.map(p=>p.lat))) / 2;
            var midLng = (Math.min(...vp.map(p=>p.lng)) + Math.max(...vp.map(p=>p.lng))) / 2;
            var sheetPx = containerH() - sheetMidTop();
            var offsetPx = Math.round(sheetPx / 2);
            var mpp = 156543.03392 * Math.cos(midLat * Math.PI / 180) / Math.pow(2, z);
            var latShift = offsetPx * mpp / 111320;
            map.setCenter(new naver.maps.LatLng(midLat - latShift, midLng));
        }
    }

    const fitBtn = document.getElementById('curFitBtn');
    if (places.length && typeof naver !== 'undefined') {
        fullBounds = new naver.maps.LatLngBounds();
        const validPlaces = places.filter(p => p.lat && p.lng);
        validPlaces.forEach(p => fullBounds.extend(new naver.maps.LatLng(p.lat, p.lng)));

        const initCenter = validPlaces.length === 1
            ? calcOffsetCenter(validPlaces[0].lat, validPlaces[0].lng, 14)
            : fullBounds.getCenter();

        map = new naver.maps.Map('curMap', {
            center: initCenter,
            zoom: validPlaces.length === 1 ? 14 : 12,
            zoomControl: false, scaleControl: false, logoControl: false, mapDataControl: false,
        });

        places.forEach((p, i) => {
            if (!p.lat || !p.lng) { markers.push(null); return; }
            const m = new naver.maps.Marker({
                position: new naver.maps.LatLng(p.lat, p.lng),
                map: map,
                icon: { content: pinHtml(i+1, false), anchor: new naver.maps.Point(pinSize/2, pinSize/2) },
                zIndex: 100,
            });
            naver.maps.Event.addListener(m, 'click', () => handlePinTap(i));
            markers.push(m);
        });
        naver.maps.Event.addListener(map, 'click', () => resetHighlight());

        let mapFitCount = 0;
        function onMapReady() {
            if (mapFitCount >= 2) return;
            mapFitCount++;
            setTimeout(fitMapToAll, mapFitCount === 1 ? 100 : 400);
        }
        naver.maps.Event.addListener(map, 'init_stylemap', onMapReady);
        naver.maps.Event.addListener(map, 'init', onMapReady);
        naver.maps.Event.addListener(map, 'idle', onMapReady);
        setTimeout(onMapReady, 300);
        setTimeout(onMapReady, 800);

        window.addEventListener('resize', () => { if (map) map.autoResize(); });
    }

    function highlightPin(idx) {
        if (activeMarkerIdx >= 0 && markers[activeMarkerIdx]) {
            markers[activeMarkerIdx].setIcon({ content: pinHtml(activeMarkerIdx+1, false), anchor: new naver.maps.Point(pinSize/2, pinSize/2) });
            markers[activeMarkerIdx].setZIndex(100);
        }
        if (idx >= 0 && markers[idx]) {
            markers[idx].setIcon({ content: pinHtml(idx+1, true), anchor: new naver.maps.Point(pinSize/2, pinSize/2) });
            markers[idx].setZIndex(200);
        }
        activeMarkerIdx = idx;
        fitBtn.style.display = idx >= 0 ? '' : 'none';
    }

    function resetHighlight() {
        highlightPin(-1);
        document.querySelectorAll('.pp-cur-card').forEach(c => c.classList.remove('is-focused'));
        fitMapToAll();
    }
    fitBtn.addEventListener('click', () => resetHighlight());

    function panToVisible(lat, lng) {
        const target = calcOffsetCenter(lat, lng, map.getZoom());
        map.panTo(target, { duration: 300 });
    }

    function handlePinTap(idx) {
        if (activeMarkerIdx === idx) { resetHighlight(); return; }
        const p = places[idx];
        if (!map || !p.lat || !p.lng) return;
        map.setZoom(16);
        setTimeout(() => panToVisible(p.lat, p.lng), 50);
        highlightPin(idx);
        document.querySelectorAll('.pp-cur-card').forEach(c => c.classList.remove('is-focused'));
        const card = document.querySelector('.pp-cur-card[data-idx="'+idx+'"]');
        if (card) {
            card.classList.add('is-focused');
            if (sheetState === 'peek') setSheetState('mid');
            setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 350);
        }
    }

    function handleCardTap(idx) {
        if (activeMarkerIdx === idx) { resetHighlight(); return; }
        const p = places[idx];
        if (!map || !p.lat || !p.lng) return;
        map.setZoom(16);
        setTimeout(() => panToVisible(p.lat, p.lng), 50);
        highlightPin(idx);
        document.querySelectorAll('.pp-cur-card').forEach(c => c.classList.remove('is-focused'));
        document.querySelector('.pp-cur-card[data-idx="'+idx+'"]')?.classList.add('is-focused');
    }

    // ── Bottom Sheet ──
    const sheet = document.getElementById('curSheet');
    const handle = document.getElementById('curSheetHandle');
    const sheetScroll = document.getElementById('curSheetScroll');
    const sheetHdr = document.getElementById('curSheetHdr');
    const sheetH = () => sheet.offsetHeight;

    const PEEK_PX = 110;
    let sheetState = 'mid';

    function getOffsets() {
        const h = sheetH();
        const ch = containerH();
        return {
            peek: h - PEEK_PX,
            mid: h - Math.round(ch * 0.52),
            full: 0,
        };
    }

    function setSheetState(state) {
        const offsets = getOffsets();
        sheetState = state;
        sheet.style.transform = 'translateY(' + Math.max(0, offsets[state]) + 'px)';
        sheet.classList.toggle('is-peek', state === 'peek');
        sheet.classList.toggle('is-mid', state === 'mid');
        sheet.classList.toggle('is-full', state === 'full');
        const handleH = handle.offsetHeight || 28;
        const ctaEl = document.getElementById('curCta');
        const ctaH = ctaEl ? ctaEl.offsetHeight : 60;
        if (state === 'full') {
            sheetScroll.style.maxHeight = '';
        } else if (state === 'mid') {
            const visibleH = Math.round(containerH() * 0.52) - handleH - ctaH;
            sheetScroll.style.maxHeight = Math.max(120, visibleH) + 'px';
        } else {
            sheetScroll.style.maxHeight = (PEEK_PX - handleH) + 'px';
        }
        if (activeMarkerIdx < 0 && state !== 'full') {
            setTimeout(fitMapToAll, 350);
        }
    }

    setSheetState('mid');

    // Touch gestures
    let dragStartY = 0, dragStartOffset = 0, isDragging = false, currentOffset = 0, dragEndTime = 0;

    function getCurrentOffset() {
        const t = getComputedStyle(sheet).transform;
        if (!t || t === 'none') return 0;
        const m = t.match(/matrix\(.+,\s*(.+)\)/);
        return m ? parseFloat(m[1]) : 0;
    }

    function onDragStart(e) {
        const t = e.touches ? e.touches[0] : e;
        dragStartY = t.clientY;
        dragStartOffset = getCurrentOffset();
        isDragging = true;
        sheet.classList.add('is-dragging');
    }

    function onDragMove(e) {
        if (!isDragging) return;
        const t = e.touches ? e.touches[0] : e;
        const dy = t.clientY - dragStartY;
        currentOffset = Math.max(0, Math.min(dragStartOffset + dy, sheetH() - PEEK_PX));
        sheet.style.transform = 'translateY(' + currentOffset + 'px)';
    }

    function onDragEnd() {
        if (!isDragging) return;
        isDragging = false;
        dragEndTime = Date.now();
        sheet.classList.remove('is-dragging');
        const offsets = getOffsets();
        const velocity = currentOffset - dragStartOffset;
        let target;
        if (velocity > 60) {
            target = sheetState === 'full' ? 'mid' : sheetState === 'mid' ? 'peek' : 'peek';
        } else if (velocity < -60) {
            target = sheetState === 'peek' ? 'mid' : sheetState === 'mid' ? 'full' : 'full';
        } else {
            const dists = { peek: Math.abs(currentOffset - offsets.peek), mid: Math.abs(currentOffset - offsets.mid), full: Math.abs(currentOffset - offsets.full) };
            target = Object.keys(dists).reduce((a, b) => dists[a] < dists[b] ? a : b);
        }
        setSheetState(target);
    }

    handle.addEventListener('touchstart', onDragStart, { passive: true });
    handle.addEventListener('touchmove', onDragMove, { passive: true });
    handle.addEventListener('touchend', onDragEnd);

    sheetHdr.addEventListener('touchstart', onDragStart, { passive: true });
    sheetHdr.addEventListener('touchmove', onDragMove, { passive: true });
    sheetHdr.addEventListener('touchend', onDragEnd);
    sheetHdr.addEventListener('mousedown', (e) => { onDragStart(e); });

    // Pull-down from scroll top → shrink to mid
    let scrollPullStart = 0;
    sheetScroll.addEventListener('touchstart', function(e) {
        if (sheetState !== 'full') return;
        if (sheetScroll.scrollTop <= 0) {
            scrollPullStart = e.touches[0].clientY;
        } else {
            scrollPullStart = 0;
        }
    }, { passive: true });

    sheetScroll.addEventListener('touchmove', function(e) {
        if (sheetState !== 'full' || !scrollPullStart) return;
        if (sheetScroll.scrollTop <= 0) {
            const dy = e.touches[0].clientY - scrollPullStart;
            if (dy > 50) {
                scrollPullStart = 0;
                setSheetState('mid');
            }
        } else {
            scrollPullStart = 0;
        }
    }, { passive: true });

    // Click on header → toggle peek/mid (suppress if touch drag just ended)
    sheetHdr.addEventListener('click', (e) => {
        if (isDragging || Date.now() - dragEndTime < 300) return;
        if (sheetState === 'peek') setSheetState('mid');
        else if (sheetState === 'mid') setSheetState('full');
    });

    // Mouse drag (desktop)
    handle.addEventListener('mousedown', (e) => { onDragStart(e); });
    document.addEventListener('mousemove', (e) => { if (isDragging) onDragMove(e); });
    document.addEventListener('mouseup', () => { if (isDragging) onDragEnd(); });

    // ── Day filter ──
    document.querySelectorAll('.pp-cur__day-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.pp-cur__day-btn').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            activeDay = btn.dataset.day;
            document.querySelectorAll('.pp-cur-card').forEach(card => {
                card.style.display = (activeDay === 'all' || card.dataset.day == activeDay) ? '' : 'none';
            });
            markers.forEach((m, i) => {
                if (!m) return;
                m.setVisible(activeDay === 'all' || places[i].day_number == activeDay);
            });
            resetHighlight();
        });
    });

    // ── Card tap → map sync ──
    document.querySelectorAll('.pp-cur-card__info').forEach(el => {
        el.addEventListener('click', () => {
            const idx = parseInt(el.closest('.pp-cur-card').dataset.idx);
            handleCardTap(idx);
        });
    });

    // ── Deep link ──
    const urlParams = new URLSearchParams(location.search);
    const placeParam = urlParams.get('place');
    if (placeParam) {
        const card = document.querySelector('.pp-cur-card[data-id="'+placeParam+'"]');
        if (card) {
            const idx = parseInt(card.dataset.idx);
            setTimeout(() => {
                setSheetState('full');
                setTimeout(() => { handleCardTap(idx); card.scrollIntoView({behavior: 'smooth', block: 'center'}); }, 400);
            }, 500);
        }
    }

    // ── ?action=save auto-trigger ──
    const allCards = document.querySelectorAll('.pp-cur-card');
    if (urlParams.get('action') === 'save') {
        setTimeout(() => {
            places.forEach(p => selected.add(p.id));
            allCards.forEach(c => c.classList.add('is-selected'));
            updateCtaText();
            updateSelectAllBtn();
            document.getElementById('curSaveBtn').click();
        }, 600);
    }

    // ── Selection ──
    const saveBtn = document.getElementById('curSaveBtn');
    const selectAllBtn = document.getElementById('curSelAll');

    document.querySelectorAll('.pp-cur-card__add').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const id = parseInt(btn.dataset.placeId);
            const card = btn.closest('.pp-cur-card');
            if (selected.has(id)) { selected.delete(id); card.classList.remove('is-selected'); }
            else { selected.add(id); card.classList.add('is-selected'); }
            updateCtaText();
            updateSelectAllBtn();
        });
    });

    selectAllBtn.addEventListener('click', () => {
        if (selected.size === totalCount) {
            selected.clear();
            allCards.forEach(c => c.classList.remove('is-selected'));
        } else {
            places.forEach(p => selected.add(p.id));
            allCards.forEach(c => c.classList.add('is-selected'));
        }
        updateCtaText();
        updateSelectAllBtn();
    });

    function updateSelectAllBtn() { selectAllBtn.textContent = selected.size === totalCount ? '선택해제' : '전체선택'; }
    function updateCtaText() { saveBtn.textContent = selected.size > 0 ? selected.size + '개 장소 담기' : '이 장소들 내 핀픽에 담기'; }
    function getSelectedIds() { return selected.size > 0 ? Array.from(selected) : places.map(p => p.id); }

    saveBtn.addEventListener('click', () => { if (isAuth) openCategorySheet(); else handleGuestSave(); });

    // ── Guest save ──
    const GUEST_KEY = 'pinpick_guest_places';
    const guestSheet = document.getElementById('curGuestSheet');
    const catSheet = document.getElementById('curCatSheet');

    document.querySelectorAll('[data-role="close"]').forEach(el => {
        el.addEventListener('click', () => {
            guestSheet.classList.remove('is-open');
            catSheet.classList.remove('is-open');
            shareSheet.classList.remove('is-open');
        });
    });

    function getGuestRemaining() { return 5 - JSON.parse(localStorage.getItem(GUEST_KEY) || '[]').length; }
    function updateGuestHint() {
        const hint = document.getElementById('curGuestHint');
        if (!hint || isAuth) return;
        hint.textContent = '비로그인은 5개까지 저장돼요 · 남은 저장 ' + getGuestRemaining() + '개';
        hint.style.display = '';
    }

    function handleGuestSave() {
        const remaining = getGuestRemaining();
        const toSave = places.filter(p => getSelectedIds().includes(p.id));
        if (remaining <= 0) {
            document.getElementById('curGuestInfo').textContent = '비로그인 저장 5개를 모두 사용했어요.\n로그인하면 전부 저장돼요';
            document.getElementById('curGuestPick').style.display = 'none';
            guestSheet.classList.add('is-open');
            return;
        }
        if (toSave.length > remaining) {
            document.getElementById('curGuestInfo').textContent = '비로그인은 ' + remaining + '개까지 더 저장할 수 있어요';
            document.getElementById('curGuestPick').textContent = '저장할 ' + remaining + '개 직접 고르기';
            document.getElementById('curGuestPick').style.display = '';
            guestSheet.classList.add('is-open');
            return;
        }
        doGuestSave(toSave);
    }

    function doGuestSave(toSave) {
        const list = JSON.parse(localStorage.getItem(GUEST_KEY) || '[]');
        toSave.forEach(p => {
            list.unshift({
                id: 'g' + Date.now() + Math.random().toString(36).slice(2,6),
                name: p.name, category_id: null, category_name: curTitle, category_icon: '📌',
                phone: p.phone || '', road_address: p.address || '', address: '',
                lat: p.lat, lng: p.lng, memo: curationType === 'course' && p.day_number ? 'Day ' + p.day_number : '',
                status: 'planned', visited_at: '', is_overseas: p.is_overseas, created_at: Date.now(),
            });
        });
        localStorage.setItem(GUEST_KEY, JSON.stringify(list));
        showToast(toSave.length + '개 장소가 저장됐어요!');
    }

    document.getElementById('curGuestLogin').addEventListener('click', () => {
        location.href = '/login?redirect=' + encodeURIComponent(location.pathname);
    });
    if (!isAuth) updateGuestHint();

    // ── Category sheet ──
    function openCategorySheet() {
        const catExisting = document.getElementById('curCatExisting');
        catExisting.innerHTML = '';
        if (userCats.length) {
            userCats.forEach(c => {
                const label = document.createElement('label');
                label.className = 'pp-share__cat-option';
                label.innerHTML = '<input type="radio" name="cur_cat_existing" value="'+c.id+'"><span class="pp-share__cat-name">'+(c.icon||'📌')+' '+escHtml(c.name)+'</span>';
                catExisting.appendChild(label);
            });
        }
        document.querySelectorAll('input[name="cur_cat"]').forEach(r => {
            r.addEventListener('change', () => {
                document.getElementById('curCatInputWrap').style.display = r.value === '__new__' ? '' : 'none';
                catExisting.style.display = r.value === '__new__' ? 'none' : '';
            });
        });
        catSheet.classList.add('is-open');
    }

    document.getElementById('curCatSaveBtn').addEventListener('click', async () => {
        const mode = document.querySelector('input[name="cur_cat"]:checked').value;
        let body = { place_ids: getSelectedIds() };
        if (mode === '__new__') {
            const name = document.getElementById('curCatInput').value.trim();
            if (!name) { alert('카테고리 이름을 입력하세요'); return; }
            body.new_category_name = name;
        } else {
            const sel = document.querySelector('input[name="cur_cat_existing"]:checked');
            if (!sel) { alert('카테고리를 선택하세요'); return; }
            body.category_id = parseInt(sel.value);
        }
        const btn = document.getElementById('curCatSaveBtn');
        btn.disabled = true; btn.textContent = '저장 중...';
        try {
            const res = await fetch('/c/' + curationId + '/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            const data = await res.json();
            if (data.success) {
                catSheet.classList.remove('is-open');
                let msg = data.saved + '개 장소가 저장됐어요!';
                if (data.skipped > 0) msg += ' (' + data.skipped + '개 중복 제외)';
                showToast(msg);
            } else { alert(data.error || '저장에 실패했어요'); }
        } catch(e) { alert('저장에 실패했어요'); }
        finally { btn.disabled = false; btn.textContent = '저장하기'; }
    });

    // ── Toast ──
    function showToast(msg) {
        let t = document.getElementById('ppCurToast');
        if (!t) { t = document.createElement('div'); t.id = 'ppCurToast'; t.className = 'pp-toast'; document.body.appendChild(t); }
        t.textContent = msg;
        t.classList.add('is-show');
        setTimeout(() => t.classList.remove('is-show'), 3000);
    }
    function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    // ── Lightbox ──
    const lb = document.getElementById('curLb');
    const lbImg = document.getElementById('curLbImg');
    const lbCounter = document.getElementById('curLbCounter');
    let lbPhotos = [], lbIdx = 0;

    window.openLightbox = function(el) {
        const card = el.closest('.pp-cur-card');
        lbPhotos = Array.from(card.querySelectorAll('.pp-cur-card__photo[data-full]')).map(p => p.dataset.full);
        lbIdx = Array.from(card.querySelectorAll('.pp-cur-card__photo[data-full]')).indexOf(el);
        if (lbIdx < 0) lbIdx = 0;
        showLbPhoto();
        lb.classList.add('is-open');
    };

    function showLbPhoto() {
        lbImg.src = lbPhotos[lbIdx];
        lbCounter.textContent = lbPhotos.length > 1 ? (lbIdx + 1) + ' / ' + lbPhotos.length : '';
        document.getElementById('curLbPrev').style.display = lbPhotos.length > 1 ? '' : 'none';
        document.getElementById('curLbNext').style.display = lbPhotos.length > 1 ? '' : 'none';
    }

    function closeLb() { lb.classList.remove('is-open'); lbImg.src = ''; }

    document.getElementById('curLbClose').addEventListener('click', closeLb);
    lb.addEventListener('click', function(e) { if (e.target === lb) closeLb(); });
    document.getElementById('curLbPrev').addEventListener('click', function(e) {
        e.stopPropagation();
        lbIdx = (lbIdx - 1 + lbPhotos.length) % lbPhotos.length;
        showLbPhoto();
    });
    document.getElementById('curLbNext').addEventListener('click', function(e) {
        e.stopPropagation();
        lbIdx = (lbIdx + 1) % lbPhotos.length;
        showLbPhoto();
    });
    document.addEventListener('keydown', function(e) {
        if (!lb.classList.contains('is-open')) return;
        if (e.key === 'Escape') closeLb();
        else if (e.key === 'ArrowLeft') { lbIdx = (lbIdx - 1 + lbPhotos.length) % lbPhotos.length; showLbPhoto(); }
        else if (e.key === 'ArrowRight') { lbIdx = (lbIdx + 1) % lbPhotos.length; showLbPhoto(); }
    });

    // Swipe on lightbox
    let lbTouchX = 0;
    lbImg.addEventListener('touchstart', function(e) { lbTouchX = e.touches[0].clientX; }, { passive: true });
    lbImg.addEventListener('touchend', function(e) {
        const dx = e.changedTouches[0].clientX - lbTouchX;
        if (Math.abs(dx) > 50 && lbPhotos.length > 1) {
            lbIdx = dx < 0 ? (lbIdx + 1) % lbPhotos.length : (lbIdx - 1 + lbPhotos.length) % lbPhotos.length;
            showLbPhoto();
        }
    });
})();
</script>
@endsection
