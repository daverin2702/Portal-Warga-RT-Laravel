<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'body', 'fields'])]
class LetterTemplate extends Model
{
    /** Variabel bawaan yang tidak boleh dipakai sebagai kunci kolom tambahan. */
    public const RESERVED = [
        'nama', 'nik', 'no_kk', 'tempat_lahir', 'tgl_lahir', 'jenis_kelamin', 'agama', 'pekerjaan', 'alamat', 'blok', 'no_rumah',
        'status_hunian', 'telp', 'rt', 'rw', 'kelurahan', 'kecamatan', 'kota', 'nama_ketua', 'tanggal_surat', 'nomor_surat', 'keperluan', 'qr_ttd',
    ];

    public const FIELD_TYPES = ['text' => 'Teks', 'textarea' => 'Teks panjang', 'date' => 'Tanggal', 'number' => 'Angka'];

    protected function casts(): array
    {
        return ['fields' => 'array'];
    }

    /** Kolom tambahan yang valid: [['key','label','type','required'], ...] */
    public function fieldList(): array
    {
        return array_values(array_filter($this->fields ?? [], fn ($f) => ! empty($f['key']) && ! empty($f['label'])));
    }
}
