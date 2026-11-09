<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\Setting;
use App\Services\CameraProbe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Admin: penempatan, penamaan, dan koneksi kamera CCTV. */
class CameraController extends Controller
{
    public function index(): View
    {
        return view('admin.cameras.index', [
            'cameras' => Camera::query()->orderBy('area')->orderBy('sort_order')->orderBy('name')->get(),
            'rtspCount' => Camera::query()->where('type', 'rtsp')->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.cameras.form', ['camera' => new Camera(['type' => 'rtsp', 'refresh_seconds' => 5, 'via_server' => true, 'visibility' => 'semua', 'active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $camera = Camera::query()->create($this->validated($request, new Camera));

        return redirect()->route('admin.cameras.index')->with('success', 'Kamera "'.$camera->name.'" ditambahkan. Tekan "Tes" untuk mengecek koneksinya.');
    }

    public function edit(Camera $camera): View
    {
        return view('admin.cameras.form', ['camera' => $camera]);
    }

    public function update(Request $request, Camera $camera): RedirectResponse
    {
        $camera->update($this->validated($request, $camera));

        return redirect()->route('admin.cameras.index')->with('success', 'Kamera "'.$camera->name.'" diperbarui.');
    }

    public function destroy(Camera $camera): RedirectResponse
    {
        $camera->delete();

        return back()->with('success', 'Kamera dihapus.');
    }

    public function test(Camera $camera, CameraProbe $probe): RedirectResponse
    {
        [$ok, $msg] = $probe->check($camera);

        return back()->with($ok ? 'success' : 'error', $camera->name.': '.$msg);
    }

    public function testAll(CameraProbe $probe): RedirectResponse
    {
        $ok = 0;
        $all = Camera::query()->where('active', true)->get();
        foreach ($all as $c) {
            $ok += $probe->check($c)[0] ? 1 : 0;
        }

        return back()->with('success', "Tes selesai: {$ok} dari {$all->count()} kamera aktif terhubung.");
    }

    public function gateway(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cctv_gateway' => ['required', Rule::in(['go2rtc', 'mediamtx'])],
            'cctv_gateway_url' => ['nullable', 'url:http,https', 'max:200'],
        ]);
        Setting::put(['cctv_gateway' => $data['cctv_gateway'], 'cctv_gateway_url' => rtrim((string) ($data['cctv_gateway_url'] ?? ''), '/')]);

        return back()->with('success', 'Pengaturan gateway RTSP disimpan.');
    }

    /** Unduh berkas konfigurasi gateway berisi semua kamera RTSP aktif. */
    public function config(string $format): StreamedResponse
    {
        $cams = Camera::query()->where('type', 'rtsp')->where('active', true)->orderBy('sort_order')->get();
        $q = fn (string $v) => "'".str_replace("'", "''", $v)."'";
        $head = "# Dibuat oleh Portal Warga (".now()->format('d/m/Y H:i').").\n# BERISI SANDI KAMERA: simpan hanya di komputer gateway.\n";

        if ($format === 'mediamtx') {
            $body = $head."# Tambahkan bagian berikut ke mediamtx.yml\npaths:\n";
            foreach ($cams as $c) {
                $body .= "  {$c->stream_key}:   # {$c->name}\n    source: ".$q((string) $c->source)."\n    sourceOnDemand: yes\n";
            }
            $name = 'mediamtx-portal-warga.yml';
        } else {
            $body = $head."api:\n  listen: \":1984\"\n  origin: \"*\"\nrtsp:\n  listen: \":8554\"\nwebrtc:\n  listen: \":8555\"\nstreams:\n";
            foreach ($cams as $c) {
                $body .= "  {$c->stream_key}: ".$q((string) $c->source)."   # {$c->name}\n";
            }
            $name = 'go2rtc.yaml';
        }

        return response()->streamDownload(fn () => print ($body), $name, ['Content-Type' => 'text/yaml; charset=UTF-8']);
    }

    private function validated(Request $request, Camera $camera): array
    {
        $types = array_keys(Camera::TYPES);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'area' => ['nullable', 'string', 'max:60'],
            'location' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:250'],
            'brand' => ['nullable', 'string', 'max:40'],
            'type' => ['required', Rule::in($types)],
            'source' => [$camera->exists ? 'nullable' : 'required', 'string', 'max:2000'],
            'stream_key' => ['nullable', 'alpha_dash:ascii', 'max:60', Rule::unique('cameras', 'stream_key')->ignore($camera->id)],
            'refresh_seconds' => ['nullable', 'integer', 'min:1', 'max:300'],
            'visibility' => ['required', Rule::in(array_keys(Camera::VISIBILITY))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $source = trim((string) ($data['source'] ?? ''));
        if ($source === '' && $camera->exists && $camera->type === $data['type']) {
            unset($data['source']);   // dikosongkan saat mengubah: sumber lama dipertahankan
        } else {
            if ($source === '') {
                throw ValidationException::withMessages(['source' => 'Isi sumber kamera karena jenis koneksinya berubah.']);
            }
            $data['source'] = $this->checkSource($data['type'], $source);
        }

        if ($data['type'] === 'rtsp') {
            $data['stream_key'] = Str::slug($data['stream_key'] ?: $data['name'], '_') ?: 'kamera_'.Str::lower(Str::random(5));
            if (Camera::query()->where('stream_key', $data['stream_key'])->whereKeyNot($camera->id ?? 0)->exists()) {
                $data['stream_key'] .= '_'.Str::lower(Str::random(3));
            }
        } else {
            $data['stream_key'] = null;
        }

        $data['refresh_seconds'] = $data['refresh_seconds'] ?? 5;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['via_server'] = $request->boolean('via_server');
        $data['active'] = $request->boolean('active');

        return $data;
    }

    private function checkSource(string $type, string $value): string
    {
        $fail = fn (string $m) => throw ValidationException::withMessages(['source' => $m]);
        if (in_array($type, ['embed', 'youtube'], true)) {
            $value = Camera::extractIframeSrc($value);
        }

        match ($type) {
            'rtsp' => preg_match('~^rtsps?://[^\s]+$~i', $value) || $fail('Alamat RTSP harus diawali rtsp:// atau rtsps://.'),
            'youtube' => Camera::youtubeEmbed($value) || $fail('Tempel tautan video/siaran YouTube, ID video, atau ID kanal (UC...).'),
            default => preg_match('~^https?://[^\s<>"]+$~i', $value) || $fail('Alamat harus diawali http:// atau https://.'),
        };

        return $value;
    }
}
