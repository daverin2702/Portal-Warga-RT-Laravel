<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pengguna dengan kata sandi bawaan diarahkan ke Profil Saya sampai sandinya diganti. */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password && ! $request->routeIs('profile.*', 'logout')) {
            return redirect()->route('profile.edit')
                ->with('warning', 'Demi keamanan, ganti kata sandi bawaan Anda terlebih dahulu.');
        }

        return $next($request);
    }
}
