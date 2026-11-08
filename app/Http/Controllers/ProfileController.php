<?php

namespace App\Http\Controllers;

use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user(), 'household' => $request->user()->household]);
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        $user = $request->user();
        $user->name = $data['name'];
        $user->photo = Uploads::replace($request->file('photo'), $user->photo, 'foto');
        $user->save();

        return back()->with('success', 'Profil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $request->user()->forceFill(['password' => $request->input('password'), 'must_change_password' => false])->save();

        return redirect()->route('dashboard')->with('success', 'Kata sandi berhasil diganti.');
    }

    /** Warga memperbarui data keluarganya sendiri. Nama KK, alamat, blok, dan nomor rumah dikunci (dipakai di surat resmi). */
    public function updateHousehold(Request $request): RedirectResponse
    {
        $household = $request->user()->household;
        abort_unless($household, 404);

        $data = $request->validate([
            'family_card_number' => ['nullable', 'digits_between:8,20', Rule::unique('households')->ignore($household->id)],
            'head_nik' => ['nullable', 'digits_between:8,20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'birth_place' => ['nullable', 'string', 'max:60'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['Laki-laki', 'Perempuan'])],
            'religion' => ['nullable', Rule::in(config('rt.religions'))],
            'occupation' => ['nullable', 'string', 'max:60'],
            'members_count' => ['required', 'integer', 'min:1', 'max:50'],
            'residency_status' => ['required', Rule::in(array_keys(\App\Models\Household::RESIDENCY))],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
        ]);
        // NIK/KK yang sudah terisi hanya bisa diubah Sekretaris.
        foreach (['family_card_number', 'head_nik'] as $locked) {
            if ($household->$locked) {
                unset($data[$locked]);
            }
        }
        $household->update($data);

        return back()->with('success', 'Data keluarga diperbarui.');
    }
}
