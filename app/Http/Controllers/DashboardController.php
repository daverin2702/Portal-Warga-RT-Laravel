<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if (! $user->isBoard()) {
            return view('dashboard.resident', [
                'user' => $user,
                'household' => $user->household,
                'board' => User::query()->where('role', 'pengurus')->whereIn('position', config('rt.core_positions'))->orderByPosition()->get(),
            ]);
        }

        $perBlock = Household::query()
            ->selectRaw("COALESCE(NULLIF(block, ''), 'Tanpa blok') as label, COUNT(*) as total")
            ->groupBy('label')->orderBy('label')->pluck('total', 'label');
        $residency = Household::query()->selectRaw('residency_status, COUNT(*) as total')->groupBy('residency_status')->pluck('total', 'residency_status');

        return view('dashboard.board', [
            'user' => $user,
            'stats' => [
                'households' => Household::query()->count(),
                'residents' => (int) Household::query()->sum('members_count'),
                'accounts' => User::query()->count(),
                'unlinked' => Household::query()->doesntHave('users')->count(),
            ],
            'perBlock' => $perBlock,
            'residency' => collect(Household::RESIDENCY)->mapWithKeys(fn ($label, $key) => [$label => (int) ($residency[$key] ?? 0)]),
            'latest' => Household::query()->latest()->limit(5)->get(),
        ]);
    }
}
