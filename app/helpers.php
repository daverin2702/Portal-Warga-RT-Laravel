<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting')) {
    /** Nilai pengaturan situs (tabel settings), jatuh ke nilai bawaan config/rt.php. */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::value($key, $default);
    }
}

if (! function_exists('rt_name')) {
    function rt_name(): string
    {
        return 'RT '.setting('rt_number').' / RW '.setting('rw_number');
    }
}

if (! function_exists('rt_location')) {
    function rt_location(): string
    {
        return trim(setting('village').', '.setting('city'), ', ');
    }
}

if (! function_exists('rt_brand_sub')) {
    function rt_brand_sub(): string
    {
        return setting('brand_sub') ?: rt_name().' · '.setting('village');
    }
}

if (! function_exists('rt_asset')) {
    /** Alamat aset tampilan: salinan lokal di public/ jika ada (mode offline), selain itu CDN. */
    function rt_asset(string $key): string
    {
        [$local, $cdn] = config("rt.assets.$key", [null, '']);
        if ($local && is_file(public_path($local)) && filesize(public_path($local)) > 0) {
            return asset($local).'?v='.filemtime(public_path($local));
        }

        return $cdn;
    }
}

if (! function_exists('rt_asset_local')) {
    function rt_asset_local(string $key): bool
    {
        $local = config("rt.assets.$key.0");

        return $local && is_file(public_path($local));
    }
}

if (! function_exists('upload_url')) {
    /** URL berkas unggahan di disk "uploads" (public/uploads), atau null jika tidak ada. */
    function upload_url(?string $path): ?string
    {
        if (! $path || ! Storage::disk('uploads')->exists($path)) {
            return null;
        }

        return asset('uploads/'.$path);
    }
}

if (! function_exists('initials')) {
    function initials(?string $name): string
    {
        $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);

        return mb_strtoupper(mb_substr($parts[0] ?? '?', 0, 1).mb_substr($parts[1] ?? '', 0, 1));
    }
}

if (! function_exists('position_label')) {
    function position_label(?string $key): string
    {
        return config('rt.positions.'.($key ?: 'warga'), 'Warga');
    }
}

if (! function_exists('rupiah')) {
    function rupiah(int|float|null $n): string
    {
        return 'Rp '.number_format((float) $n, 0, ',', '.');
    }
}

if (! function_exists('active_theme')) {
    /** Tema aktif; admin bisa mempratinjau tema lain dengan ?pratinjau_tema=kunci. */
    function active_theme(): string
    {
        $theme = setting('theme');
        $preview = request()->query('pratinjau_tema');
        if ($preview && auth()->user()?->isAdmin()) {
            $theme = $preview;
        }

        return array_key_exists($theme, config('rt.themes')) ? $theme : 'teal';
    }
}
