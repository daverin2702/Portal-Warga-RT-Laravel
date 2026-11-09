<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Services\CameraProbe;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Throwable;

/** Halaman pantau CCTV untuk semua peran. */
class CctvController extends Controller
{
    public function index(Request $request): View
    {
        $cameras = Camera::query()->visibleTo($request->user())->get();

        return view('cctv.index', [
            'groups' => $cameras->groupBy(fn ($c) => $c->area ?: 'Lainnya'),
            'total' => $cameras->count(),
        ]);
    }

    /** Teruskan snapshot dari kamera lewat server, sehingga sandi kamera tidak sampai ke browser. */
    public function snapshot(Request $request, Camera $camera, CameraProbe $probe): Response
    {
        abort_unless($camera->type === 'snapshot' && $camera->via_server
            && Camera::query()->visibleTo($request->user())->whereKey($camera->id)->exists(), 404);

        try {
            $r = $probe->snapshot($camera);
        } catch (Throwable) {
            abort(504, 'Kamera tidak merespons.');
        }
        $type = (string) $r->header('Content-Type');
        abort_unless($r->successful() && str_starts_with(strtolower($type), 'image/'), 502, 'Kamera tidak mengirim gambar.');

        return response($r->body(), 200, [
            'Content-Type' => $type, 'Cache-Control' => 'no-store, max-age=0', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
