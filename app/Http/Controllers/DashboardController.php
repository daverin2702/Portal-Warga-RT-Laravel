<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\Letter;
use App\Models\LetterRequest;
use App\Services\LetterWorkflow;
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
                'letterRequests' => LetterRequest::query()->with('letter')->where('household_id', $user->household_id ?? 0)->latest()->limit(5)->get(),
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
            'tasks' => $this->tasks($user),
        ]);
    }

    /** Jumlah surat yang menunggu tindakan pengguna ini, sesuai wewenangnya di tiap tahap. */
    private function tasks($user): array
    {
        $tasks = [];
        foreach (LetterWorkflow::REQUEST_STEPS as $status => [, $ability, , $label]) {
            if ($user->can($ability) && ($n = LetterRequest::query()->where('status', $status)->count())) {
                $tasks[] = ['Pengajuan surat: '.strtolower($label), $n];
            }
        }
        foreach (LetterWorkflow::DRAFT_STEPS as $status => [, $ability, , $label]) {
            if ($user->can($ability) && ($n = Letter::query()->where('status', $status)->count())) {
                $tasks[] = ['Draf surat: '.strtolower($label), $n];
            }
        }

        return $tasks;
    }
}
