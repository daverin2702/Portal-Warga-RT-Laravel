<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Support\Board;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Carbon;

/** Mengisi variabel {{...}}, membersihkan HTML, dan membuat nomor surat. */
class LetterRenderer
{
    private const ROMAN = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    /** Nilai variabel bawaan untuk satu keluarga (boleh null untuk surat tanpa data warga). */
    public function data(?Household $h, array $extra = []): array
    {
        return array_merge([
            'nama' => $h?->head_name ?? '',
            'nik' => $h?->head_nik ?? '',
            'no_kk' => $h?->family_card_number ?? '',
            'tempat_lahir' => $h?->birth_place ?? '',
            'tgl_lahir' => $h?->birth_date ? self::date($h->birth_date) : '',
            'jenis_kelamin' => $h?->gender ?? '',
            'agama' => $h?->religion ?? '',
            'pekerjaan' => $h?->occupation ?? '',
            'alamat' => $h?->fullAddress() ?? '',
            'blok' => $h?->block ?? '',
            'no_rumah' => $h?->house_number ?? '',
            'status_hunian' => $h?->residencyLabel() ?? '',
            'telp' => $h?->phone ?? '',
            'rt' => setting('rt_number'),
            'rw' => setting('rw_number'),
            'kelurahan' => setting('village'),
            'kecamatan' => setting('district'),
            'kota' => setting('city'),
            'nama_ketua' => Board::chairName(),
            'tanggal_surat' => self::date(now()),
        ], $extra);
    }

    /** Ganti {{kunci}} dengan nilai (di-escape). Variabel tanpa nilai dan {{qr_ttd}} dibiarkan apa adanya. */
    public function fill(string $html, array $data): string
    {
        return preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function ($m) use ($data) {
            $key = strtolower($m[1]);
            $value = $data[$key] ?? '';

            return ($key !== 'qr_ttd' && $value !== '' && $value !== null) ? e((string) $value) : $m[0];
        }, $html);
    }

    /** Nilai kolom tambahan dari formulir, tanggal diubah ke format Indonesia. */
    public function fieldValues(LetterTemplate $template, array $input): array
    {
        $out = [];
        foreach ($template->fieldList() as $f) {
            $value = trim((string) ($input[$f['key']] ?? ''));
            if (($f['type'] ?? '') === 'date' && $value !== '') {
                $value = self::date($value);
            }
            $out[$f['key']] = $value;
        }

        return $out;
    }

    /** Hapus skrip, iframe, atribut on*, dan tautan javascript:/data: dari HTML editor. */
    public function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        $doc = new DOMDocument;
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="akar">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $xp = new DOMXPath($doc);
        foreach (iterator_to_array($xp->query('//script|//iframe|//object|//embed|//link|//meta|//form|//base|//style|//input|//button|//textarea|//select')) as $node) {
            $node->parentNode->removeChild($node);
        }
        foreach ($xp->query('//*') as $node) {
            foreach (iterator_to_array($node->attributes) as $attr) {
                if (stripos($attr->name, 'on') === 0 || preg_match('/^\s*(javascript|data|vbscript):/i', $attr->value)) {
                    $node->removeAttribute($attr->name);
                }
            }
        }
        $root = $doc->getElementById('akar');
        $out = '';
        if ($root) {
            foreach ($root->childNodes as $child) {
                $out .= $doc->saveHTML($child);
            }
        }

        return $out;
    }

    /** 001/RT.05/RW.02/XI/2026 — urut per tahun. */
    public function nextNumber(): string
    {
        $n = Letter::query()->whereYear('created_at', now()->year)->count() + 1;

        return sprintf('%03d/RT.%s/RW.%s/%s/%s', $n, setting('rt_number'), setting('rw_number'), self::ROMAN[now()->month], now()->year);
    }

    public static function date(mixed $date): string
    {
        return Carbon::parse($date)->locale('id')->translatedFormat('j F Y');
    }
}
