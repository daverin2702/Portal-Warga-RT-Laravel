<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    private const PAGES = [
        'warga' => ['title' => 'Masuk sebagai Warga', 'roles' => ['warga']],
        'pengurus' => ['title' => 'Masuk sebagai Pengurus RT', 'roles' => ['pengurus']],
        'admin' => ['title' => 'Masuk sebagai Administrator', 'roles' => ['admin']],
    ];

    public function show(string $peran = 'warga'): View
    {
        return view('auth.login', ['peran' => $peran, 'title' => self::PAGES[$peran]['title'], 'pages' => self::PAGES]);
    }

    public function store(Request $request, string $peran = 'warga'): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['username' => __('auth.failed')]);
        }

        $user = $request->user();
        if (! in_array($user->role, self::PAGES[$peran]['roles'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages([
                'username' => 'Akun ini bukan akun '.strtolower(config('rt.roles.'.self::PAGES[$peran]['roles'][0])).'. Gunakan halaman masuk yang sesuai.',
            ]);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        if ($user->must_change_password) {
            return redirect()->route('profile.edit')->with('warning', 'Demi keamanan, ganti kata sandi bawaan Anda terlebih dahulu.');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
