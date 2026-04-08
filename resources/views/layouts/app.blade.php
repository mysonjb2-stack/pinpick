<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#ff5a5f">
    <title>@yield('title', '핀픽') · 핀픽</title>
    <meta name="description" content="내가 저장한 장소를 빠르게 꺼내 쓰는 나만의 지도">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @stack('head')
</head>
<body>
<div class="pp-app @yield('app_class')">
    @hasSection('header')
        @yield('header')
    @endif

    @if(session('success'))
        <div class="pp-flash">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="pp-flash pp-flash--error">{{ session('error') }}</div>
    @endif

    @yield('content')

    @include('partials.nav')
</div>
@stack('scripts')
</body>
</html>
