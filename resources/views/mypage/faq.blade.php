@extends('layouts.app')
@section('page_title', 'FAQ | 핀픽')
@section('app_class', 'pp-app--form')

@section('header')
<header class="pp-header">
    <a href="javascript:history.back()" class="pp-header__icon pp-header__back" aria-label="뒤로" onclick="if(!document.referrer||!document.referrer.includes(location.host)){location.href='{{ route('home') }}';return false;}">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </a>
    <div class="pp-header__title">FAQ</div>
    <div class="pp-header__spacer"></div>
</header>
@endsection

@section('content')
<ul class="pp-faq" id="ppFaq">
    @foreach($faqs as $f)
        <li class="pp-faq__item">
            <button type="button" class="pp-faq__q">
                <span class="pp-faq__q-mark">Q</span>
                <span class="pp-faq__q-text">{{ $f['q'] }}</span>
                <span class="pp-faq__q-toggle" aria-hidden="true">＋</span>
            </button>
            <div class="pp-faq__a" hidden>
                <span class="pp-faq__a-mark">A</span>
                <p class="pp-faq__a-text">{{ $f['a'] }}</p>
            </div>
        </li>
    @endforeach
</ul>

<footer class="pp-faq-footer">
    <div class="pp-faq-footer__contact">
        <span class="pp-faq-footer__label">문의</span>
        <a href="mailto:help.mapcube@gmail.com" class="pp-faq-footer__email">help.mapcube@gmail.com</a>
    </div>
    <div class="pp-faq-footer__links">
        <a href="{{ route('terms') }}">이용약관</a>
        <span class="pp-faq-footer__dot">·</span>
        <a href="{{ route('privacy') }}">개인정보 수집이용</a>
        <span class="pp-faq-footer__dot">·</span>
        <a href="{{ route('location-terms') }}">위치정보 이용약관</a>
    </div>
    <address class="pp-faq-footer__biz">
        <span>(주) 맵큐브</span><br>
        대표이사 : 이학영 | 사업자등록번호 : 230-81-13255<br>
        통신판매번호 : 2023-성남분당A-0360<br>
        주소 : 경기도 성남시 분당구 황새울로 354 8층<br>
        전화번호 : 1670-1376
    </address>
    <div class="pp-faq-footer__copy">&copy; {{ date('Y') }} 핀픽. All rights reserved.</div>
</footer>
@endsection

@push('scripts')
<script>
document.getElementById('ppFaq').addEventListener('click', (e) => {
    const btn = e.target.closest('.pp-faq__q');
    if (!btn) return;
    const li = btn.closest('.pp-faq__item');
    const ans = li.querySelector('.pp-faq__a');
    const open = li.classList.toggle('is-open');
    ans.hidden = !open;
    btn.querySelector('.pp-faq__q-toggle').textContent = open ? '－' : '＋';
});
</script>
@endpush
