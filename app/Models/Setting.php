<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    private const CACHE_KEY = 'rt.settings';

    /** Semua pengaturan tersimpan (di-cache). Kosong jika tabel belum dimigrasi. */
    public static function stored(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
        } catch (Throwable) {
            return [];
        }
    }

    /** Nilai tersimpan yang tidak kosong, selain itu nilai bawaan config/rt.php. */
    public static function value(string $key, mixed $default = null): mixed
    {
        $stored = static::stored()[$key] ?? null;
        if ($stored !== null && $stored !== '') {
            return $stored;
        }

        return config('rt.defaults.'.$key, $default);
    }

    /** Ambil nilai mentah (tanpa nilai bawaan), untuk mengisi formulir. */
    public static function raw(string $key): string
    {
        return (string) (static::stored()[$key] ?? '');
    }

    public static function put(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value === null ? '' : (string) $value]);
        }
        Cache::forget(self::CACHE_KEY);
    }
}
