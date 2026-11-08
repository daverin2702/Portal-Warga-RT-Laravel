<?php

namespace App\Http\Controllers;

use App\Http\Requests\HouseholdRequest;
use App\Models\Household;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HouseholdController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $block = $request->query('blok');

        $households = Household::query()
            ->withCount('users')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('head_name', 'like', "%$q%")->orWhere('family_card_number', 'like', "%$q%")
                ->orWhere('phone', 'like', "%$q%")->orWhere('house_number', $q)))
            ->when($block, fn ($query) => $query->where('block', $block))
            ->orderByHouse()
            ->paginate(20)->withQueryString();

        return view('households.index', [
            'households' => $households,
            'blocks' => Household::query()->whereNotNull('block')->where('block', '<>', '')->distinct()->orderBy('block')->pluck('block'),
            'q' => $q,
            'block' => $block,
        ]);
    }

    public function create(): View
    {
        return view('households.form', ['household' => new Household(['members_count' => 1, 'residency_status' => 'owner'])]);
    }

    public function store(HouseholdRequest $request): RedirectResponse
    {
        Household::query()->create($request->validated());

        return redirect()->route('households.index')->with('success', 'Data keluarga ditambahkan.');
    }

    public function edit(Household $household): View
    {
        return view('households.form', ['household' => $household]);
    }

    public function update(HouseholdRequest $request, Household $household): RedirectResponse
    {
        $household->update($request->validated());

        return redirect()->route('households.index')->with('success', 'Data keluarga diperbarui.');
    }

    public function destroy(Household $household): RedirectResponse
    {
        $household->delete();

        return back()->with('success', 'Data keluarga dihapus. Akun yang terhubung tetap ada tanpa data keluarga.');
    }
}
