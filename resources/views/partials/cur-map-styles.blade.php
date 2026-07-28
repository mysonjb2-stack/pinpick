<style>
/* ── Container: 480px app shell ── */
.pp-app--curpage {
  overflow: hidden; height: 100vh; height: 100dvh;
  position: relative; padding-bottom: 0;
}
.pp-app--curpage .pp-nav { display: none; }

/* ── Map: fills container ── */
.pp-cur-map { position: absolute; inset: 0; z-index: 1; }
.pp-cur-map__el { width: 100%; height: 100%; opacity: 0; transition: opacity .2s ease; }
.pp-cur-map__el.is-ready { opacity: 1; }
.pp-cur-map__skel {
  position: absolute; inset: 0; z-index: 0;
  background: var(--pp-bg-sub, #f5f0eb);
  display: flex; align-items: center; justify-content: center;
}
.pp-cur-map__skel::after {
  content: ''; width: 28px; height: 28px; border-radius: 50%;
  border: 3px solid var(--pp-line, #e0d8d0); border-top-color: var(--pp-primary, #2b211e);
  animation: curMapSpin .8s linear infinite;
}
@keyframes curMapSpin { to { transform: rotate(360deg); } }

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
.pp-cur-sheet__title-row {
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
}
.pp-cur-sheet__title {
  font-size: 20px; font-weight: 800; color: var(--pp-text);
  margin: 0; letter-spacing: -0.02em; line-height: 1.3; flex: 1; min-width: 0;
}
.pp-cur-sheet__share-btn {
  flex-shrink: 0; border: none; width: 36px; height: 36px;
  display: flex; align-items: center; justify-content: center;
  background: var(--pp-bg-card, #f5f5f5); color: var(--pp-text-sub);
  cursor: pointer; border-radius: 50%;
}
.pp-cur-sheet__share-btn:active { background: var(--pp-chip-bg); }
.pp-cur-sheet__summary {
  font-size: 13px; color: var(--pp-text-sub); margin: 4px 0 0;
}
.pp-cur-sheet__author {
  display: flex; align-items: center; gap: 6px; margin-top: 8px;
}
.pp-cur-author__avatar {
  width: 22px; height: 22px; border-radius: 50%; object-fit: cover;
  flex-shrink: 0; background: var(--pp-chip-bg);
}
.pp-cur-author__avatar--initial {
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700; color: #fff;
  background: var(--pp-text-sub, #8c7e72);
}
.pp-cur-author__name { font-size: 13px; font-weight: 600; color: var(--pp-text); }
.pp-cur-author__dot { font-size: 11px; color: var(--pp-text-sub); }
.pp-cur-author__count { font-size: 12px; color: var(--pp-text-sub); }

/* Report */
.pp-cur-report { text-align: center; padding: 12px 0 0; }
.pp-cur-report__btn {
  display: inline-flex; align-items: center; gap: 4px;
  border: none; background: none; font-size: 12px; color: var(--pp-text-sub);
  cursor: pointer; padding: 6px 12px;
}
.pp-cur-report-sheet {
  display: none; position: fixed; inset: 0; z-index: 1200;
  align-items: flex-end; justify-content: center;
}
.pp-cur-report-sheet.is-open { display: flex; }
.pp-cur-report-sheet__backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.45); }
.pp-cur-report-sheet__panel {
  position: relative; width: 100%; max-width: 480px; background: var(--pp-bg);
  border-radius: 16px 16px 0 0; padding: 20px 18px calc(20px + env(safe-area-inset-bottom));
}
.pp-cur-report-sheet__head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.pp-cur-report-sheet__head h3 { font-size: 16px; font-weight: 700; margin: 0; color: var(--pp-text); }
.pp-cur-report-sheet__close { border: none; background: none; font-size: 22px; color: var(--pp-text-sub); cursor: pointer; }
.pp-cur-report-opt {
  display: block; padding: 10px 0; border-bottom: 1px solid var(--pp-line);
  font-size: 14px; color: var(--pp-text); cursor: pointer;
}
.pp-cur-report-opt input { accent-color: var(--pp-primary); margin-right: 8px; }
.pp-cur-report-detail {
  width: 100%; margin-top: 10px; padding: 10px; border: 1px solid var(--pp-line);
  border-radius: 10px; font-size: 13px; resize: none; min-height: 60px;
  color: var(--pp-text); background: var(--pp-bg); box-sizing: border-box;
}
.pp-cur-report-submit { width: 100%; margin-top: 12px; }

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
  cursor: pointer; -webkit-tap-highlight-color: transparent;
}
.pp-cur-card.is-selected { border-color: var(--pp-primary); background: rgba(43,33,30,.04); }
.pp-cur-card.is-focused { border-color: var(--pp-primary, #e67e22); background: #fef9f4; }

.pp-cur-card__top {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 8px;
}
.pp-cur-card__info { flex: 1; min-width: 0; }
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
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.pp-cur-card__sub .pp-cur-card__cat { margin-right: 4px; }
.pp-cur-card__cat {
  font-size: 11px; font-weight: 600; color: var(--pp-text-sub);
  background: var(--pp-chip-bg); border-radius: 4px; padding: 2px 6px;
  display: inline; vertical-align: baseline;
}

/* Add (label) button */
.pp-cur-card__add {
  flex-shrink: 0; height: 30px; border-radius: 999px;
  padding: 0 12px; gap: 4px;
  border: 1.5px solid var(--pp-line); background: #fff;
  display: inline-flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all .15s; color: var(--pp-text-sub);
  font-size: 12px; font-weight: 600; white-space: nowrap;
}
.pp-cur-card__add:active { transform: scale(.95); }
.pp-cur-card__add-label--off { display: inline; }
.pp-cur-card__add-label--on { display: none; }
.pp-cur-card__check { display: none; }
.pp-cur-card.is-selected .pp-cur-card__add {
  background: var(--pp-primary); border-color: var(--pp-primary); color: #fff;
}
.pp-cur-card.is-selected .pp-cur-card__plus { display: none; }
.pp-cur-card.is-selected .pp-cur-card__check { display: block; }
.pp-cur-card.is-selected .pp-cur-card__add-label--off { display: none; }
.pp-cur-card.is-selected .pp-cur-card__add-label--on { display: inline; }

/* Bottom action line */
.pp-cur-card__actions {
  display: flex; align-items: center; justify-content: space-between;
  margin-top: 8px;
}
.pp-cur-card__map-chip {
  display: inline-flex; align-items: center; gap: 3px;
  height: 28px; padding: 0 10px; border-radius: 999px;
  border: 1px solid var(--pp-line); background: var(--pp-bg, #fff);
  font-size: 11.5px; font-weight: 500; color: var(--pp-text-sub);
  text-decoration: none; cursor: pointer; margin-left: auto;
  transition: background .12s;
}
.pp-cur-card__map-chip:active { background: var(--pp-chip-bg); }

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
  background: #dbeafe; color: #1d4ed8; font-size: 11px; font-weight: 600;
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

/* ── App banner (share page) ── */
.pp-cur-app-banner {
  position: absolute; top: 0; left: 0; right: 0; z-index: 350;
  display: flex; align-items: center; justify-content: space-between;
  padding: 8px 12px; background: rgba(255,255,255,.95);
  backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
  border-bottom: 1px solid var(--pp-line);
}
.pp-cur-app-banner__left {
  display: flex; align-items: center; gap: 8px;
}
.pp-cur-app-banner__text {
  font-size: 13px; font-weight: 600; color: var(--pp-text);
}
.pp-cur-app-banner__right {
  display: flex; align-items: center; gap: 6px;
}
.pp-cur-app-banner__open {
  padding: 6px 14px; border-radius: 999px; border: none;
  background: var(--pp-primary); color: #fff;
  font-size: 12px; font-weight: 700; cursor: pointer;
}
.pp-cur-app-banner__close {
  border: none; background: none; font-size: 16px;
  color: var(--pp-text-sub); cursor: pointer; padding: 4px;
}

/* ── Reselect bar ── */
.pp-share__reselect-bar {
  position: fixed; top: 0; left: 50%; transform: translateX(-50%);
  width: 100%; max-width: 480px; z-index: 400;
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 18px; background: var(--pp-primary);
  color: #fff; font-size: 14px; font-weight: 600;
}
.pp-share__reselect-cancel {
  border: none; background: rgba(255,255,255,.2); color: #fff;
  padding: 5px 14px; border-radius: 999px; font-size: 13px; font-weight: 600; cursor: pointer;
}
</style>
