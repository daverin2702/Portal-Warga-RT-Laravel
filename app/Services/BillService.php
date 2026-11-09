<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillType;
use App\Models\CashTransaction;
use App\Models\Household;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BillService
{
    /** Terbitkan semua jenis tagihan wajib untuk semua keluarga pada satu periode. Tagihan yang sudah ada dilewati. */
    public function issueMandatory(string $period): array
    {
        $types = BillType::query()->where('mandatory', true)->where('active', true)->get();
        $households = Household::query()->pluck('id');
        $created = 0;
        $skipped = 0;
        $notify = [];
        DB::transaction(function () use ($types, $households, $period, &$created, &$skipped, &$notify) {
            foreach ($types as $type) {
                foreach ($households as $hid) {
                    $bill = Bill::query()->firstOrCreate(
                        ['household_id' => $hid, 'name' => $type->name, 'period' => $period],
                        ['bill_type_id' => $type->id, 'allocation' => $type->allocation, 'amount' => $type->amount, 'status' => 'belum'],
                    );
                    if ($bill->wasRecentlyCreated) {
                        $created++;
                        $notify[$hid][] = $type->name;
                    } else {
                        $skipped++;
                    }
                }
            }
        });
        foreach ($notify as $hid => $names) {
            Notify::send(Notify::household($hid), 'tagihan', 'Tagihan baru periode '.$period, implode(', ', $names).'. Bayar dan unggah bukti di dasbor.', route('dashboard'));
        }

        return ['types' => $types->count(), 'created' => $created, 'skipped' => $skipped];
    }

    /** Satu jenis tagihan untuk semua keluarga atau satu keluarga. */
    public function issue(BillType $type, string $period, int $amount, ?int $householdId = null): array
    {
        $ids = $householdId ? collect([$householdId]) : Household::query()->pluck('id');
        $created = 0;
        DB::transaction(function () use ($ids, $type, $period, $amount, &$created) {
            foreach ($ids as $hid) {
                $bill = Bill::query()->firstOrCreate(
                    ['household_id' => $hid, 'name' => $type->name, 'period' => $period],
                    ['bill_type_id' => $type->id, 'allocation' => $type->allocation, 'amount' => $amount, 'status' => 'belum'],
                );
                if ($bill->wasRecentlyCreated) {
                    $created++;
                    Notify::send(Notify::household($hid), 'tagihan', 'Tagihan baru: '.$type->name, 'Periode '.$period.', '.rupiah($amount).'.', route('dashboard'));
                }
            }
        });

        return ['created' => $created, 'skipped' => $ids->count() - $created];
    }

    /** Warga mengunggah bukti bayar. Berkas disimpan privat (tidak bisa dibuka lewat alamat langsung). */
    public function submitProof(Bill $bill, UploadedFile $file, string $method): void
    {
        if ($bill->status !== 'belum') {
            throw new RuntimeException('Tagihan ini sudah dibayar atau sedang diverifikasi.');
        }
        $path = $file->store('bukti-bayar', 'local');
        if (! $path) {
            throw new RuntimeException('Bukti bayar gagal disimpan. Coba lagi.');
        }
        $bill->update(['status' => 'menunggu', 'payment_method' => $method, 'proof_path' => $path, 'proof_uploaded_at' => now(), 'rejection_note' => null]);
        Notify::send(Notify::ability('verifikasi-bayar'), 'tagihan', 'Bukti bayar masuk', ($bill->household?->head_name ?? 'Warga').': '.$bill->name.' '.$bill->period.', '.rupiah($bill->amount), route('finance.bills.index', ['status' => 'menunggu']));
    }

    /** Bendahara menerima pembayaran: tagihan lunas, struk terbit, kas bertambah. */
    public function accept(Bill $bill, User $verifier, ?string $method = null): string
    {
        $number = DB::transaction(function () use (&$bill, $verifier, $method) {
            $bill = Bill::query()->with('household')->lockForUpdate()->findOrFail($bill->id);
            if ($bill->status === 'lunas') {
                throw new RuntimeException('Tagihan ini sudah lunas.');
            }
            $number = 'STR-'.now()->format('ymd').'-'.str_pad((string) $bill->id, 5, '0', STR_PAD_LEFT);
            $bill->update([
                'status' => 'lunas', 'payment_method' => $method ?? $bill->payment_method ?? 'Tunai', 'receipt_number' => $number,
                'paid_at' => now(), 'verified_by' => $verifier->id, 'rejection_note' => null,
            ]);
            CashTransaction::query()->create([
                'date' => now()->toDateString(), 'type' => 'masuk', 'category' => $bill->name, 'allocation' => $bill->allocation,
                'description' => $bill->name.' '.$bill->period.' - '.($bill->household?->head_name ?? 'warga'),
                'amount' => $bill->amount, 'status' => 'disetujui', 'bill_id' => $bill->id, 'created_by' => $verifier->id,
            ]);

            return $number;
        });
        Notify::send(Notify::household($bill->household_id), 'tagihan', 'Pembayaran diterima', $bill->name.' '.$bill->period.' lunas. Struk '.$number.' sudah tersedia.', route('finance.bills.receipt', $bill));

        return $number;
    }

    public function reject(Bill $bill, string $note): void
    {
        if ($bill->status !== 'menunggu') {
            throw new RuntimeException('Tagihan ini tidak sedang menunggu verifikasi.');
        }
        $this->deleteProof($bill);
        $bill->update(['status' => 'belum', 'proof_path' => null, 'proof_uploaded_at' => null, 'rejection_note' => $note ?: 'Bukti tidak sesuai']);
        Notify::send(Notify::household($bill->household_id), 'tagihan', 'Bukti bayar ditolak', $bill->name.' '.$bill->period.': '.$bill->rejection_note.'. Silakan unggah ulang.', route('dashboard'));
    }

    public function deleteProof(Bill $bill): void
    {
        if ($bill->proof_path && Storage::disk('local')->exists($bill->proof_path)) {
            Storage::disk('local')->delete($bill->proof_path);
        }
    }
}
