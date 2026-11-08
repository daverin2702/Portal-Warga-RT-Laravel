<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ isset($title) ? $title.' | ' : '' }}{{ setting('site_name') }}</title>
@if ($favicon = upload_url(setting('favicon')) ?: upload_url(setting('logo')))
    <link rel="icon" href="{{ $favicon }}">
@endif
<link href="{{ rt_asset('fonts.css') }}" rel="stylesheet">
<link href="{{ rt_asset('bootstrap.css') }}" rel="stylesheet">
<link href="{{ rt_asset('icons.css') }}" rel="stylesheet">
<link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
@stack('head')
