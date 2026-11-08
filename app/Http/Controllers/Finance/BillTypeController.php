<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BillType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillTypeController extends Controller
{
    public function index(Request $request): View
    {
        $edit = $request->query('ubah') ? BillType::query()->find($request->query('ubah')) : null;

        return view('finance.bill-types', [
            'types' => BillType::query()->withCount('bills')->orderByDesc('active')->orderByDesc('mandatory')->orderBy('name')->get(),
            'edit' => $edit ?? new BillType(['allocation' => 'Kas umum', 'mandatory' => false]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        BillType::query()->create($this->validated($request));

        return redirect()->route('finance.types.index')->with('success', 'Jenis tagihan ditambahkan.');
    }

    public function update(Request $request, BillType $type): RedirectResponse
    {
        $type->update($this->validated($request, $type));

        return redirect()->route('finance.types.index')->with('success', 'Jenis tagihan diperbarui. Tagihan yang sudah terbit tidak berubah.');
    }

    public function toggle(BillType $type): RedirectResponse
    {
        $type->update(['active' => ! $type->active]);

        return back()->with('success', $type->name.($type->active ? ' diaktifkan.' : ' dinonaktifkan.'));
    }

    private function validated(Request $request, ?BillType $type = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('bill_types')->ignore($type?->id)],
            'description' => ['nullable', 'string', 'max:150'],
            'amount' => ['required', 'integer', 'min:0', 'max:100000000'],
            'allocation' => ['required', 'string', 'max:80'],
        ]);
        $data['mandatory'] = $request->boolean('mandatory');

        return $data;
    }
}
