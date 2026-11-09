<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->query('kategori');

        return view('community.announcements', [
            'announcements' => Announcement::query()->with('author')
                ->when($category && array_key_exists($category, Announcement::CATEGORIES), fn ($q) => $q->where('category', $category))
                ->latest()->paginate(15)->withQueryString(),
            'category' => $category,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'category' => ['required', Rule::in(array_keys(Announcement::CATEGORIES))],
        ]);
        $a = Announcement::query()->create($data + ['created_by' => $request->user()->id]);
        Notify::send(Notify::everyone(), 'pengumuman', 'Pengumuman: '.$a->title, Str::limit($a->body, 140), route('announcements.index'));

        return back()->with('success', 'Pengumuman diterbitkan dan dikirim ke semua warga.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('success', 'Pengumuman dihapus.');
    }
}
