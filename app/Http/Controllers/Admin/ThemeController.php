<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function index(): View
    {
        return view('admin.themes', ['themes' => config('rt.themes'), 'active' => setting('theme')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['theme' => ['required', Rule::in(array_keys(config('rt.themes')))]]);
        Setting::put(['theme' => $data['theme']]);

        return back()->with('success', 'Tema "'.config('rt.themes.'.$data['theme'].'.name').'" diterapkan ke seluruh website.');
    }
}
