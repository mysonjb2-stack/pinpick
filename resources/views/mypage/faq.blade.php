@extends('layouts.app')
@section('title', 'FAQ')
@section('app_class', 'pp-app--form')

@section('header')
<header class="pp-header">
    <a href="{{ route('mypage') }}" class="pp-header__icon pp-header__back" aria-label="뒤로">
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
