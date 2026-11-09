<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'area', 'location', 'description', 'brand', 'type', 'source', 'stream_key', 'refresh_seconds', 'via_server', 'visibility', 'sort_order', 'active'])]
#[Hidden(['source'])]
class Camera extends Model
{
    public const TYPES = [
        'rtsp' => 'RTSP (lewat gateway go2rtc / MediaMTX)',
        'hls' => 'HLS (.m3u8) dari NVR, cloud, atau gateway',
        'mjpeg' => 'MJPEG (aliran gambar langsung)',
        'snapshot' => 'Snapshot JPEG berkala',
        'embed' => 'Embed / tautan berbagi dari aplikasi merek atau layanan pihak ketiga',
        'youtube' => 'YouTube Live',
    ];

    public const VISIBILITY = ['semua' => 'Semua akun (warga & pengurus)', 'pengurus' => 'Hanya pengurus'];

    public const BRANDS = ['Hikvision', 'Dahua', 'EZVIZ', 'Imou', 'TP-Link Tapo', 'Reolink', 'Uniview', 'Axis', 'Xiaomi', 'V380', 'Lainnya'];

    protected function casts(): array
    {
        return [
            'source' => 'encrypted',
            'via_server' => 'boolean',
            'active' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }

    /** Kamera aktif yang boleh dilihat pengguna ini. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where('active', true)
            ->when(! $user->isBoard(), fn ($q) => $q->where('visibility', 'semua'))
            ->orderBy('area')->orderBy('sort_order')->orderBy('name');
    }

    /** URL sumber dengan sandi disamarkan, untuk ditampilkan ke admin. */
    public function maskedSource(): string
    {
        return (string) preg_replace('~(://[^:/@\s]+:)[^@/\s]+@~', '$1****@', (string) $this->source);
    }

    /**
     * Data pemutar untuk browser. URL sumber yang berisi sandi (RTSP, snapshot lewat server)
     * tidak pernah dikirim ke browser.
     *
     * @return array{kind: string, src?: string, refresh?: int, error?: string}
     */
    public function player(): array
    {
        $src = (string) $this->source;

        switch ($this->type) {
            case 'rtsp':
                $gw = rtrim((string) setting('cctv_gateway_url'), '/');
                if ($gw === '' || ! $this->stream_key) {
                    return ['kind' => 'error', 'error' => 'Gateway RTSP belum diatur admin.'];
                }
                $key = rawurlencode($this->stream_key);

                return setting('cctv_gateway') === 'mediamtx'
                    ? ['kind' => 'iframe', 'src' => $gw.'/'.$key.'/?controls=true&muted=true&autoplay=true']
                    : ['kind' => 'iframe', 'src' => $gw.'/stream.html?src='.$key.'&mode=webrtc,mse,hls,mjpeg'];
            case 'hls':
                return ['kind' => 'hls', 'src' => $src];
            case 'mjpeg':
                return ['kind' => 'img', 'src' => $src];
            case 'snapshot':
                return ['kind' => 'snapshot', 'refresh' => max(1, (int) $this->refresh_seconds),
                    'src' => $this->via_server ? route('cctv.snapshot', $this) : $src];
            case 'youtube':
                return ($embed = self::youtubeEmbed($src)) ? ['kind' => 'iframe', 'src' => $embed] : ['kind' => 'error', 'error' => 'Alamat YouTube tidak dikenali.'];
            default:
                return ['kind' => 'iframe', 'src' => $src];
        }
    }

    /** Ubah URL video/live YouTube atau ID kanal (UC...) menjadi URL embed. */
    public static function youtubeEmbed(string $value): ?string
    {
        $value = trim($value);
        $opt = 'autoplay=1&mute=1&playsinline=1&rel=0';
        if (preg_match('~^UC[\w-]{22}$~', $value) || preg_match('~youtube\.com/channel/(UC[\w-]{22})~', $value, $m)) {
            return 'https://www.youtube.com/embed/live_stream?channel='.($m[1] ?? $value).'&'.$opt;
        }
        if (preg_match('~(?:youtu\.be/|[?&]v=|/embed/|/live/|/shorts/)([\w-]{11})~', $value, $m) || preg_match('~^([\w-]{11})$~', $value, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1].'?'.$opt;
        }

        return null;
    }

    /** Ambil atribut src bila admin menempelkan kode <iframe ...> utuh. */
    public static function extractIframeSrc(string $value): string
    {
        $value = trim($value);
        if (stripos($value, '<iframe') !== false && preg_match('~\ssrc\s*=\s*["\']([^"\']+)["\']~i', $value, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES);
        }

        return $value;
    }
}
