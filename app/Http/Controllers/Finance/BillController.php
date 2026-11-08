<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillType;
use App\Models\Household;
use App\Services\BillService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BillController extends Controller
{
    public function __construct(private BillService $bills) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->query('status');
        $period = $request->query('periode');

        $bills = Bill::query()->with('household')
            ->when(! $user->isBoard(), fn ($q) => $q->where('household_id', $user->household_id ?? 0))
            ->when($status && array_key_exists($status, Bill::STATUS), fn ($q) => $q->where('status', $status))
            ->when($period && preg_match('/^\d{4}-\d{2}$/', $period), fn ($q) => $q->where('period', $period))
            ->orderByRaw("CASE status WHEN 'menunggu' THEN 0 WHEN 'belum' THEN 1 ELSE 2 END")->latest('id')
            ->paginate(30)->withQueryString();

        return view('finance.bills', [
            'bills' => $bills,
            'types' => BillType::query()->where('active', true)->orderByDesc('mandatory')->orderBy('name')->get(),
            'households' => $user->can('kelola-tagihan') ? Household::query()->orderByHouse()->get(['id', 'head_name', 'block', 'house_number']) : collect(),
            'summary' => $user->isBoard() ? Bill::query()->selectRaw('status, COUNT(*) as n, COALESCE(SUM(amount),0) as total')->groupBy('status')->get()->keyBy('status') : collect(),
            'status' => $status,
            'period' => $period,
        ]);
    }

    public function issueMandatory(Request $request): RedirectResponse
    {
        $data = $request->validate(['period' => ['required', 'date_format:Y-m']]);
        $r = $this->bills->issueMandatory($data['period']);
        if (! $r['types']) {
            return back()->with('warning', 'Belum ada jenis tagihan wajib yang aktif. Atur di menu Jenis Tagihan.');
        }

        return back()->with('success', "Tagihan wajib {$data['period']} diterbitkan: {$r['created']} tagihan baru".($r['skipped'] ? ", {$r['skipped']} dilewati karena sudah ada" : '').'.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bill_type_id' => ['required', Rule::exists('bill_types', 'id')->where('active', true)],
            'period' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'integer', 'min:1000', 'max:100000000'],
            'household_id' => ['nullable', 'exists:households,id'],
        ]);
        $r = $this->bills->issue(BillType::query()->findOrFail($data['bill_type_id']), $data['period'], (int) $data['amount'], $data['household_id'] ?? null);

        return back()->with('success', "{$r['created']} tagihan dibuat".($r['skipped'] ? " ({$r['skipped']} sudah ada, dilewati)" : '').'.');
    }

    /** Warga mengunggah bukti bayar untuk tagihan keluarganya sendiri. */
    public function uploadProof(Request $request, Bill $bill): RedirectResponse
    {
        abort_unless($bill->household_id === $request->user()->household_id, 403);
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(config('rt.payment_methods'))],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:3072'],
        ]);
        try {
            $this->bills->submitProof($bill, $request->file('proof'), $data['payment_method']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Bukti bayar terkirim. Menunggu verifikasi Bendahara.');
    }

    public function proof(Request $request, Bill $bill): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->isBoard() || $bill->household_id === $user->household_id, 404);
        abort_unless($bill->proof_path && Storage::disk('local')->exists($bill->proof_path), 404);

        return Storage::disk('local')->response($bill->proof_path, null, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function accept(Request $request, Bill $bill): RedirectResponse
    {
        if ($bill->status !== 'menunggu') {
            return back()->with('error', 'Tagihan ini tidak sedang menunggu verifikasi (mungkin sudah diproses).');
        }

        return $this->run(fn () => 'Pembayaran diterima. Struk '.$this->bills->accept($bill, $request->user()).' terbit dan kas bertambah.');
    }

    public function cash(Request $request, Bill $bill): RedirectResponse
    {
        if ($bill->status !== 'belum') {
            return back()->with('error', 'Hanya tagihan yang belum dibayar yang bisa ditandai lunas tunai.');
        }

        return $this->run(fn () => 'Ditandai lunas tunai. Struk '.$this->bills->accept($bill, $request->user(), 'Tunai').' terbit.');
    }

    public function reject(Request $request, Bill $bill): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:200']]);

        return $this->run(function () use ($bill, $data) {
            $this->bills->reject($bill, trim((string) ($data['note'] ?? '')));

            return 'Bukti ditolak dan dikembalikan ke warga.';
        });
    }

    public function destroy(Bill $bill): RedirectResponse
    {
        if ($bill->status !== 'belum') {
            return back()->with('error', 'Hanya tagihan yang belum dibayar yang bisa dihapus.');
        }
        $bill->delete();

        return back()->with('success', 'Tagihan dihapus.');
    }

    public function receipt(Request $request, Bill $bill): View
    {
        $user = $request->user();
        abort_unless($bill->status === 'lunas' && ($user->isBoard() || $bill->household_id === $user->household_id), 404);

        return view('finance.receipt', ['bill' => $bill->load(['household', 'verifier'])]);
    }

    private function run(callable $fn): RedirectResponse
    {
        try {
            return back()->with('success', $fn());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
