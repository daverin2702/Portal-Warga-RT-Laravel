<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Penyimpanan unggahan publik (logo, foto) di disk "uploads" (public/uploads). */
class Uploads
{
    /** Simpan berkas baru dan hapus berkas lama. Mengembalikan jalur relatif. */
    public static function replace(?UploadedFile $file, ?string $old, string $folder): ?string
    {
        if (! $file) {
            return $old;
        }
        $path = $file->store($folder, 'uploads');
        if ($path && $old) {
            static::delete($old);
        }

        return $path ?: $old;
    }

    public static function delete(?string $path): void
    {
        if ($path && Storage::disk('uploads')->exists($path)) {
            Storage::disk('uploads')->delete($path);
        }
    }
}
