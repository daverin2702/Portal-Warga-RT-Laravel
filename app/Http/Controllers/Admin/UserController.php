<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\User;
use App\Services\PositionService;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private PositionService $positions) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $users = User::query()->with('household')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('username', 'like', "%$q%")))
            ->when($request->query('jabatan'), fn ($query, $p) => $query->where('position', $p))
            ->orderByPosition()->paginate(25)->withQueryString();

        return view('admin.users.index', ['users' => $users, 'q' => $q]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['role' => 'warga', 'position' => 'warga']), 'households' => $this->households()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $user = User::query()->create([
            'name' => $data['name'], 'username' => $data['username'], 'email' => $data['email'] ?? null,
            'password' => $data['password'], 'role' => $data['role'], 'position' => 'warga',
            'household_id' => $data['household_id'] ?? null, 'must_change_password' => true,
            'photo' => Uploads::replace($request->file('photo'), null, 'foto'),
        ]);
        $this->positions->assign($user, $data['position']);

        return redirect()->route('admin.users.index')->with('success', 'Akun dibuat. Pengguna akan diminta mengganti kata sandi saat pertama masuk.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user, 'households' => $this->households()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        $user->fill([
            'name' => $data['name'], 'email' => $data['email'] ?? null,
            'household_id' => $data['household_id'] ?? null,
            // Admin tidak bisa menurunkan hak aksesnya sendiri.
            'role' => $user->is($request->user()) ? $user->role : $data['role'],
            'photo' => Uploads::replace($request->file('photo'), $user->photo, 'foto'),
        ]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
            $user->must_change_password = true;
        }
        $user->save();
        $this->positions->assign($user, $data['position']);

        return redirect()->route('admin.users.index')->with('success', 'Akun diperbarui.');
    }

    /** Ganti jabatan cepat dari daftar akun. */
    public function position(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['position' => ['required', Rule::in(array_keys(config('rt.positions')))]]);
        $this->positions->assign($user, $data['position']);

        return back()->with('success', $user->name.' sekarang: '.position_label($data['position']).'.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Akun yang sedang dipakai tidak bisa dihapus.');
        }
        Uploads::delete($user->photo);
        $user->delete();

        return back()->with('success', 'Akun dihapus.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => $user ? ['prohibited'] : ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9_.]+$/', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'password' => $user ? ['nullable', Password::min(8)] : ['required', Password::min(8)],
            'role' => ['required', Rule::in(array_keys(config('rt.roles')))],
            'position' => ['required', Rule::in(array_keys(config('rt.positions')))],
            'household_id' => ['nullable', 'exists:households,id'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    }

    private function households()
    {
        return Household::query()->orderByHouse()->get(['id', 'head_name', 'block', 'house_number']);
    }
}
