<?php

namespace App\Services;

use App\Models\Camera;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Uji konektivitas kamera dari sisi server dan ambil snapshot untuk diteruskan ke browser. */
class CameraProbe
{
    /** @return array{0: bool, 1: string} [berhasil, pesan] */
    public function check(Camera $camera): array
    {
        try {
            [$ok, $msg] = match ($camera->type) {
                'rtsp' => $this->rtsp($camera),
                'snapshot' => $this->http((string) $camera->source, 'image/'),
                'mjpeg' => $this->http((string) $camera->source, 'multipart/'),
                'hls' => $this->http((string) $camera->source, null),
                'youtube' => [Camera::youtubeEmbed((string) $camera->source) !== null, 'Alamat YouTube dikenali. Status siaran dicek oleh YouTube saat diputar.'],
                default => $this->http((string) $camera->source, null),
            };
        } catch (Throwable $e) {
            [$ok, $msg] = [false, $e->getMessage()];
        }

        $camera->forceFill(['last_status' => $ok ? 'online' : 'offline', 'last_message' => mb_strimwidth($msg, 0, 200), 'last_checked_at' => now()])->save();

        return [$ok, $msg];
    }

    /** Ambil satu gambar snapshot (dukung Basic & Digest auth dari user:sandi di URL). */
    public function snapshot(Camera $camera): Response
    {
        return $this->get((string) $camera->source);
    }

    private function rtsp(Camera $camera): array
    {
        $p = parse_url((string) $camera->source);
        if (empty($p['host'])) {
            return [false, 'URL RTSP tidak valid.'];
        }
        $port = $p['port'] ?? (($p['scheme'] ?? '') === 'rtsps' ? 322 : 554);
        $sock = @fsockopen($p['host'], $port, $errno, $errstr, 4);
        if (! $sock) {
            return [false, "Port RTSP {$p['host']}:{$port} tidak bisa dihubungi ({$errstr})."];
        }
        fclose($sock);
        $msg = "Port RTSP {$p['host']}:{$port} terbuka.";

        $gw = rtrim((string) setting('cctv_gateway_url'), '/');
        if ($gw === '') {
            return [true, $msg.' Gateway belum diatur, kamera belum bisa ditonton.'];
        }
        try {
            $r = Http::timeout(4)->get(setting('cctv_gateway') === 'mediamtx' ? $gw.'/'.rawurlencode((string) $camera->stream_key).'/' : $gw.'/api/streams');
            $msg .= $r->status() < 500 ? ' Gateway menjawab (HTTP '.$r->status().').' : ' Gateway galat (HTTP '.$r->status().').';
        } catch (Throwable) {
            $msg .= ' Gateway tidak terjangkau dari server (bisa jadi hanya terjangkau dari jaringan warga).';
        }

        return [true, $msg];
    }

    private function http(string $url, ?string $expect): array
    {
        $r = $this->get($url, $expect === 'multipart/');
        $type = strtolower((string) $r->header('Content-Type'));
        if (! $r->successful()) {
            return [false, 'Server kamera menjawab HTTP '.$r->status().($r->status() === 401 ? ' (username/sandi salah).' : '.')];
        }
        if ($expect && ! str_starts_with($type, $expect)) {
            return [false, 'Terhubung, tetapi jenis isinya "'.($type ?: 'tidak diketahui').'", bukan '.rtrim($expect, '/').'.'];
        }

        return [true, 'Terhubung (HTTP '.$r->status().($type ? ', '.$type : '').').'];
    }

    private function get(string $url, bool $stream = false): Response
    {
        $p = parse_url($url);
        $user = isset($p['user']) ? rawurldecode($p['user']) : null;
        $pass = isset($p['pass']) ? rawurldecode($p['pass']) : '';
        $clean = $user !== null ? preg_replace('~://[^@/]+@~', '://', $url, 1) : $url;
        // Kamera lokal umumnya memakai sertifikat swa-tanda tangan, jadi verifikasi TLS dimatikan.
        $req = fn () => Http::timeout(8)->withOptions(['stream' => $stream, 'verify' => false]);

        $r = $user !== null ? $req()->withBasicAuth($user, $pass)->get($clean) : $req()->get($clean);
        if ($r->status() === 401 && $user !== null) {
            $r = $req()->withDigestAuth($user, $pass)->get($clean);   // Hikvision, Dahua, dll. memakai Digest
        }

        return $r;
    }
}
