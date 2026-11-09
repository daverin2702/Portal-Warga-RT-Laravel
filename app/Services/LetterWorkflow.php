<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\LetterRequest;
use App\Models\User;
use App\Support\Board;
use App\Support\Notify;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Alur persetujuan surat.
 * Pengajuan warga : diajukan --(Sekretaris: teruskan)--> diteruskan --(Wakil: periksa)--> diperiksa --(Ketua: setujui)--> disetujui (surat terbit)
 * Draf manual     : draf --(Wakil: periksa)--> diperiksa --(Ketua: setujui)--> terbit
 * Pada setiap tahap, pemegang wewenang tahap itu juga boleh menolak.
 */
class LetterWorkflow
{
    public const REQUEST_STEPS = [
        'diajukan' => ['teruskan', 'teruskan-surat', 'diteruskan', 'Teruskan ke Wakil'],
        'diteruskan' => ['periksa', 'periksa-surat', 'diperiksa', 'Sudah diperiksa'],
        'diperiksa' => ['setujui', 'tandatangani-surat', 'disetujui', 'Setujui & tandatangani'],
    ];

    public const DRAFT_STEPS = [
        'draf' => ['periksa', 'periksa-surat', 'diperiksa', 'Sudah diperiksa'],
        'diperiksa' => ['setujui', 'tandatangani-surat', 'terbit', 'Setujui & tandatangani'],
    ];

    public const ACTION_LABELS = ['teruskan' => 'Diteruskan', 'periksa' => 'Diperiksa', 'setujui' => 'Disetujui & ditandatangani', 'tolak' => 'Ditolak'];

    public function __construct(private LetterRenderer $renderer) {}

    /** Langkah yang tersedia bagi pengguna untuk status tertentu, atau null. */
    public static function stepFor(array $steps, string $status, User $user): ?array
    {
        $step = $steps[$status] ?? null;

        return $step && $user->can($step[1]) ? $step : null;
    }

    public function advanceRequest(LetterRequest $req, User $user, string $action, string $note = '', bool $qr = true): string
    {
        $message = DB::transaction(function () use (&$req, $user, $action, $note, $qr) {
            $req = LetterRequest::query()->lockForUpdate()->findOrFail($req->id);
            $step = $this->authorizeStep(self::REQUEST_STEPS, $req->status, $user, $action);
            $req->status = $action === 'tolak' ? 'ditolak' : $step[2];
            $req->note = $note !== '' ? $note : $req->note;
            $req->log = $this->appendLog($req->log, $user, $action, $note);
            $message = $action === 'tolak' ? 'Pengajuan ditolak.' : 'Tersimpan.';

            if ($req->status === 'disetujui') {
                $template = $req->template;
                if (! $template) {
                    throw new RuntimeException('Templat untuk pengajuan ini sudah dihapus. Buat suratnya lewat menu Buat Surat.');
                }
                $number = $this->renderer->nextNumber();
                $data = $this->renderer->data($req->household, ($req->field_values ?? []) + ['keperluan' => $req->purpose, 'nomor_surat' => $number]);
                $letter = Letter::query()->create([
                    'letter_template_id' => $template->id, 'household_id' => $req->household_id, 'created_by' => $user->id,
                    'number' => $number, 'title' => $template->name, 'purpose' => $req->purpose,
                    'body' => $this->renderer->sanitize($this->renderer->fill($template->body, $data)),
                    'with_letterhead' => true, 'qr_signature' => $qr, 'status' => 'terbit', 'token' => Str::random(32),
                    'signer_name' => Board::chairName(), 'issued_at' => now(),
                    'log' => $this->appendLog([], $user, 'setujui', 'Terbit dari pengajuan warga'),
                ]);
                $req->letter_id = $letter->id;
                $message = 'Disetujui. Surat nomor '.$number.' sudah terbit.';
            }
            $req->save();

            return $message;
        });

        $info = $req->template_name.($note !== '' ? ' - '.$note : '');
        match ($req->status) {
            'diteruskan' => Notify::send(Notify::ability('periksa-surat'), 'persetujuan', 'Pengajuan surat perlu diperiksa', $info, route('letters.approvals.index')),
            'diperiksa' => Notify::send(Notify::ability('tandatangani-surat'), 'persetujuan', 'Surat menunggu tanda tangan Anda', $info, route('letters.approvals.index')),
            'disetujui' => Notify::send(Notify::household($req->household_id), 'persetujuan', 'Surat Anda sudah terbit', $info, route('letters.requests.index')),
            'ditolak' => Notify::send(Notify::household($req->household_id), 'persetujuan', 'Pengajuan surat ditolak', $info, route('letters.requests.index')),
            default => null,
        };

        return $message;
    }

    public function advanceDraft(Letter $letter, User $user, string $action, string $note = '', bool $qr = true): string
    {
        $message = DB::transaction(function () use (&$letter, $user, $action, $note, $qr) {
            $letter = Letter::query()->lockForUpdate()->findOrFail($letter->id);
            $step = $this->authorizeStep(self::DRAFT_STEPS, $letter->status, $user, $action);
            $letter->status = $action === 'tolak' ? 'ditolak' : $step[2];
            $letter->log = $this->appendLog($letter->log, $user, $action, $note);
            if ($letter->status === 'terbit') {
                $letter->qr_signature = $qr;
                $letter->signer_name = Board::chairName();
                $letter->issued_at = now();
            }
            $letter->save();

            return match ($letter->status) {
                'terbit' => $qr ? 'Disetujui. QR tanda tangan kini berlaku.' : 'Disetujui. Surat siap dicetak untuk ditandatangani basah.',
                'ditolak' => 'Draf ditolak.',
                default => 'Tersimpan.',
            };
        });

        $info = $letter->title.' - '.$letter->number.($note !== '' ? ' - '.$note : '');
        match ($letter->status) {
            'diperiksa' => Notify::send(Notify::ability('tandatangani-surat'), 'persetujuan', 'Draf surat menunggu tanda tangan Anda', $info, route('letters.approvals.index')),
            'terbit' => Notify::send(User::query()->find($letter->created_by), 'persetujuan', 'Draf surat sudah ditandatangani', $info, route('letters.archive')),
            'ditolak' => Notify::send(User::query()->find($letter->created_by), 'persetujuan', 'Draf surat ditolak', $info, route('letters.archive')),
            default => null,
        };

        return $message;
    }

    private function authorizeStep(array $steps, string $status, User $user, string $action): array
    {
        $step = $steps[$status] ?? null;
        if (! $step) {
            throw new RuntimeException('Surat ini sudah tidak dalam proses persetujuan.');
        }
        if (! $user->can($step[1])) {
            throw new RuntimeException('Anda tidak berwenang pada tahap ini.');
        }
        if ($action !== 'tolak' && $action !== $step[0]) {
            throw new RuntimeException('Tahap tidak sesuai atau sudah diproses orang lain. Muat ulang halaman.');
        }

        return $step;
    }

    private function appendLog(?array $log, User $user, string $action, string $note): array
    {
        $log ??= [];
        $log[] = ['at' => now()->format('d/m/Y H:i'), 'by' => $user->name.' ('.$user->displayRole().')', 'action' => self::ACTION_LABELS[$action] ?? $action, 'note' => $note];

        return $log;
    }
}
