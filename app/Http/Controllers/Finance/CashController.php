<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Services\CashWorkflow;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class CashController extends Controller
{
    public function __construct(private CashWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $year = (int) ($request->query('tahun') ?: now()->year);
        $month = (int) $request->query('bulan');

        $approved = CashTransaction::query()->approved()->whereYear('date', $year);
        $monthly = (clone $approved)->selectRaw("MONTH(date) as m, SUM(CASE WHEN type='masuk' THEN amount ELSE 0 END) as masuk, SUM(CASE WHEN type='keluar' THEN amount ELSE 0 END) as keluar")
            ->groupByRaw('MONTH(date)')->get()->keyBy('m');

        return view('finance.cash', [
            'year' => $year,
            'month' => $month,
            'years' => CashTransaction::query()->selectRaw('DISTINCT YEAR(date) as y')->pluck('y')->push(now()->year)->unique()->sortDesc()->values(),
            'balance' => CashTransaction::balance(),
            'totals' => ['masuk' => (int) (clone $approved)->where('type', 'masuk')->sum('amount'), 'keluar' => (int) (clone $approved)->where('type', 'keluar')->sum('amount')],
            'monthly' => $monthly,
            'allocations' => CashTransaction::query()->approved()
                ->selectRaw("allocation, SUM(CASE WHEN type='masuk' THEN amount ELSE 0 END) as masuk, SUM(CASE WHEN type='keluar' THEN amount ELSE 0 END) as keluar")
                ->groupBy('allocation')->orderBy('allocation')->get(),
            'transactions' => CashTransaction::query()->whereYear('date', $year)
                ->when($month, fn ($q) => $q->whereMonth('date', $month))
                ->when(! $user->isBoard(), fn ($q) => $q->approved())
                ->orderByDesc('date')->orderByDesc('id')->paginate(40)->withQueryString(),
        ]);
    }

    /** Bendahara: pemasukan langsung berlaku; pengeluaran diajukan untuk diperiksa Wakil dan disetujui Ketua. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'type' => ['required', Rule::in(['masuk', 'keluar'])],
            'category' => ['required', 'string', 'max:60'],
            'allocation' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:200'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
        ]);
        $user = $request->user();
        $out = $data['type'] === 'keluar';
        $tx = CashTransaction::query()->create($data + [
            'status' => $out ? 'diajukan' : 'disetujui', 'created_by' => $user->id,
            'log' => [['at' => now()->format('d/m/Y H:i'), 'by' => $user->name.' ('.$user->displayRole().')', 'action' => $out ? 'Diposting' : 'Dicatat', 'note' => '']],
        ]);

        if ($out) {
            Notify::send(Notify::ability('periksa-kas'), 'persetujuan', 'Pengeluaran kas perlu diperiksa', $tx->label().' ('.rupiah($tx->amount).')', route('finance.cash.approvals'));
        }

        return back()->with('success', $out ? 'Pengeluaran diposting. Menunggu pemeriksaan Wakil Ketua dan persetujuan Ketua.' : 'Pemasukan dicatat.');
    }

    public function destroy(Request $request, CashTransaction $cash): RedirectResponse
    {
        if ($cash->bill_id || ($cash->status === 'disetujui' && ! $request->user()->isAdmin())) {
            return back()->with('error', 'Catatan yang sudah disetujui atau berasal dari pembayaran tagihan tidak bisa dihapus.');
        }
        $cash->delete();

        return back()->with('success', 'Catatan kas dihapus.');
    }

    public function approvals(): View
    {
        return view('finance.cash-approvals', [
            'items' => CashTransaction::query()->with('creator')->whereIn('status', array_keys(CashWorkflow::STEPS))->oldest()->get(),
        ]);
    }

    public function approve(Request $request, CashTransaction $cash): RedirectResponse
    {
        $data = $request->validate(['action' => ['required', Rule::in(['periksa', 'setujui', 'tolak'])], 'note' => ['nullable', 'string', 'max:200']]);
        try {
            return back()->with('success', $this->workflow->advance($cash, $request->user(), $data['action'], trim((string) ($data['note'] ?? ''))));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
