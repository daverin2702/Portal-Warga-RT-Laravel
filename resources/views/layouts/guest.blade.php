<!doctype html>
<html lang="id" data-theme="{{ active_theme() }}">
<head>@include('partials.head')</head>
<body>
@yield('content')
<script src="{{ rt_asset('bootstrap.js') }}"></script>
@stack('scripts')
</body>
</html>
