<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pengeluaran kas: diajukan (Bendahara) --Wakil: periksa--> diperiksa --Ketua: setujui--> disetujui (masuk saldo & laporan).
 * Pemegang wewenang tahap berjalan juga boleh menolak.
 */
class CashWorkflow
{
    public const STEPS = [
        'diajukan' => ['periksa', 'periksa-kas', 'diperiksa', 'Sudah diperiksa'],
        'diperiksa' => ['setujui', 'setujui-kas', 'disetujui', 'Setujui pengeluaran'],
    ];

    public static function stepFor(string $status, User $user): ?array
    {
        $step = self::STEPS[$status] ?? null;

        return $step && $user->can($step[1]) ? $step : null;
    }

    public function advance(CashTransaction $tx, User $user, string $action, string $note = ''): string
    {
        $message = DB::transaction(function () use (&$tx, $user, $action, $note) {
            $tx = CashTransaction::query()->lockForUpdate()->findOrFail($tx->id);
            $step = self::STEPS[$tx->status] ?? null;
            if (! $step) {
                throw new RuntimeException('Pengeluaran ini sudah tidak dalam proses persetujuan.');
            }
            if (! $user->can($step[1])) {
                throw new RuntimeException('Anda tidak berwenang pada tahap ini.');
            }
            if ($action !== 'tolak' && $action !== $step[0]) {
                throw new RuntimeException('Tahap tidak sesuai atau sudah diproses orang lain. Muat ulang halaman.');
            }
            $tx->status = $action === 'tolak' ? 'ditolak' : $step[2];
            $log = $tx->log ?? [];
            $log[] = ['at' => now()->format('d/m/Y H:i'), 'by' => $user->name.' ('.$user->displayRole().')',
                'action' => ['periksa' => 'Diperiksa', 'setujui' => 'Disetujui', 'tolak' => 'Ditolak'][$action], 'note' => $note];
            $tx->log = $log;
            $tx->save();

            return match ($tx->status) {
                'disetujui' => 'Pengeluaran disetujui dan masuk ke saldo serta laporan keuangan.',
                'ditolak' => 'Pengeluaran ditolak.',
                default => 'Tersimpan. Menunggu persetujuan Ketua.',
            };
        });

        $info = $tx->label().' ('.rupiah($tx->amount).')'.($note !== '' ? ' - '.$note : '');
        match ($tx->status) {
            'diperiksa' => Notify::send(Notify::ability('setujui-kas'), 'persetujuan', 'Pengeluaran kas menunggu persetujuan Anda', $info, route('finance.cash.approvals')),
            'disetujui' => Notify::send(User::query()->find($tx->created_by), 'persetujuan', 'Pengeluaran kas disetujui', $info, route('finance.cash.index')),
            'ditolak' => Notify::send(User::query()->find($tx->created_by), 'persetujuan', 'Pengeluaran kas ditolak', $info, route('finance.cash.index')),
            default => null,
        };

        return $message;
    }
}
