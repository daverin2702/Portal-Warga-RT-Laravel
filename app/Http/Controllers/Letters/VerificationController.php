<?php

namespace App\Http\Controllers\Letters;

use App\Http\Controllers\Controller;
use App\Models\Letter;
use App\Services\LetterRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Verifikasi keaslian surat untuk publik. Data pemohon disamarkan sebagian. */
class VerificationController extends Controller
{
    public function show(string $token): View
    {
        $letter = Letter::query()->with('household')->where('token', $token)->first();

        return view('letters.verify', ['letter' => $letter, 'data' => $letter?->isIssued() ? self::publicData($letter) : null]);
    }

    /** Dipakai pop-up "Cek Surat" di beranda: kode QR, atau nomor surat + tanggal pembuatan. */
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kode' => ['nullable', 'string', 'max:500'],
            'nomor' => ['nullable', 'required_without:kode', 'string', 'max:80'],
            'tanggal' => ['nullable', 'required_without:kode', 'date_format:Y-m-d'],
        ]);

        if (! empty($data['kode'])) {
            if (! preg_match('~(?:/verifikasi/|^)([A-Za-z0-9]{24,32})(?:[/?#]|$)~', trim($data['kode']), $m)) {
                return response()->json(['valid' => false, 'pesan' => 'Kode QR ini bukan kode surat dari '.rt_name().'.']);
            }
            $letter = Letter::query()->with('household')->where('token', $m[1])->first();
        } else {
            $number = preg_replace('/\s+/', '', $data['nomor']);
            $letter = Letter::query()->with('household')->whereRaw("REPLACE(number, ' ', '') = ?", [$number])
                ->where(fn ($q) => $q->whereDate('issued_at', $data['tanggal'])->orWhereDate('created_at', $data['tanggal']))->first();
        }

        if (! $letter) {
            return response()->json(['valid' => false, 'pesan' => 'Surat tidak ditemukan. Periksa kembali nomor dan tanggal, atau hubungi pengurus RT.']);
        }
        if (! $letter->isIssued()) {
            return response()->json(['valid' => false, 'pesan' => 'Surat ini tercatat tetapi belum disetujui Ketua RT, sehingga belum berlaku.']);
        }

        return response()->json(['valid' => true, 'data' => self::publicData($letter)]);
    }

    public static function publicData(Letter $letter): array
    {
        return [
            'nomor' => $letter->number,
            'jenis' => $letter->title,
            'tanggal' => LetterRenderer::date($letter->issued_at ?? $letter->created_at),
            'pemohon' => $letter->household ? mask_text($letter->household->head_name) : '-',
            'tujuan' => $letter->purpose ? mask_text($letter->purpose, 3) : '-',
            'penandatangan' => 'Ketua '.rt_name().($letter->signer_name ? ', '.mask_text($letter->signer_name) : ''),
            'ttd' => $letter->qr_signature ? 'Tanda tangan digital (QR)' : 'Tanda tangan basah',
        ];
    }
}
