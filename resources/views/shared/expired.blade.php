@extends('layouts.app')
@section('page_title', '만료된 공유 | 핀픽')
@section('noindex', true)

@section('content')
<div class="pp-expired">
    <div class="pp-expired__icon">🔗</div>
    <h2 class="pp-expired__title">만료된 공유입니다</h2>
    <p class="pp-expired__desc">이 링크는 더 이상 사용할 수 없어요.<br>공유자가 링크를 취소했거나 존재하지 않는 주소예요.</p>
    <a href="/" class="pp-btn" style="max-width:240px;margin:24px auto 0;display:block;text-align:center;text-decoration:none;line-height:50px">핀픽 홈으로</a>
</div>
@endsection
