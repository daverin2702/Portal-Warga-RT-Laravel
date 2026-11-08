@extends('layouts.guest', ['title' => $title])

@section('content')
<div class="login-wrap"><div class="panel p-4" style="width:100%;max-width:410px;border-radius:20px">
    <a href="{{ route('home') }}" class="plate mb-3 text-decoration-none">{{ rt_name() }}</a>
    <h1 class="h4 fw-bold mb-1">{{ $title }}</h1>
    <p class="text-secondary mb-3">Masukkan akun Anda untuk melanjutkan.</p>
    @include('partials.flash')
    <form method="post" action="{{ route('login.store', $peran) }}">
        @csrf
        <label class="form-label" for="username">Username</label>
        <input id="username" name="username" class="form-control mb-3" value="{{ old('username') }}" required autofocus autocomplete="username">
        <label class="form-label" for="password">Kata sandi</label>
        <input id="password" name="password" type="password" class="form-control mb-3" required autocomplete="current-password">
        <label class="form-check mb-3"><input type="checkbox" class="form-check-input" name="remember" value="1"> Ingat saya</label>
        <button class="btn btn-primary w-100 py-2">Masuk</button>
    </form>
    <div class="small mt-3 d-flex justify-content-between">
        <a href="{{ route('home') }}">Kembali ke beranda</a>
        <span>@foreach ($pages as $key => $page)@if ($key !== $peran)<a class="ms-2" href="{{ route('login', $key) }}">{{ ucfirst($key) }}</a>@endif @endforeach</span>
    </div>
</div></div>
@endsection
