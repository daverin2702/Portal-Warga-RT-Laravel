<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Laporan keuangan per periode (bisa dicetak), terbuka untuk semua warga. */
class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $valid = fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}$/', $v);
        $from = $valid($request->query('dari')) ? $request->query('dari') : now()->format('Y-m');
        $to = $valid($request->query('sampai')) ? $request->query('sampai') : $from;
        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }
        $start = Carbon::createFromFormat('Y-m-d', $from.'-01')->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $to.'-01')->endOfMonth();

        $rows = CashTransaction::query()->approved()->whereBetween('date', [$start->toDateString(), $end->toDateString()])->orderBy('date')->orderBy('id')->get();
        $in = $rows->where('type', 'masuk')->values();
        $out = $rows->where('type', 'keluar')->values();
        $opening = CashTransaction::balance($start->toDateString());
        $label = fn ($ym) => Carbon::createFromFormat('Y-m-d', $ym.'-01')->locale('id')->translatedFormat('F Y');

        return view('finance.report', [
            'from' => $from, 'to' => $to, 'start' => $start, 'end' => $end,
            'periodLabel' => $from === $to ? $label($from) : $label($from).' s.d. '.$label($to),
            'in' => $in, 'out' => $out, 'totalIn' => (int) $in->sum('amount'), 'totalOut' => (int) $out->sum('amount'), 'opening' => $opening,
            'byCategory' => $rows->groupBy('category')->map(fn ($g) => ['masuk' => (int) $g->where('type', 'masuk')->sum('amount'), 'keluar' => (int) $g->where('type', 'keluar')->sum('amount')])->sortKeys(),
            'pending' => $request->user()->isBoard() ? CashTransaction::query()->whereIn('status', ['diajukan', 'diperiksa'])->whereBetween('date', [$start->toDateString(), $end->toDateString()])->count() : 0,
            'treasurer' => User::query()->where('position', 'bendahara')->value('name'),
        ]);
    }
}
