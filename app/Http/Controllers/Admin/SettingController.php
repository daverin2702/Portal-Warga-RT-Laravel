<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    private const TEXT = [
        'site_name', 'brand_sub', 'rt_number', 'rw_number', 'village', 'district', 'city', 'secretariat_address', 'motto',
        'hero_badge', 'hero_title', 'hero_lead', 'hero_button_primary', 'hero_button_secondary',
        'board_title', 'board_subtitle', 'chair_name', 'chair_term', 'chair_message', 'vice_name', 'vice_term', 'vice_message',
        'footer_left', 'footer_right',
        'letterhead_line1', 'letterhead_line2', 'letterhead_line3', 'letterhead_line4', 'public_url',
    ];

    private const IMAGES = ['logo' => 'logo', 'logo_right' => 'logo', 'hero_image' => 'beranda', 'chair_photo' => 'pengurus', 'vice_photo' => 'pengurus'];

    public function edit(): View
    {
        $features = json_decode(Setting::raw('features'), true);

        return view('admin.settings', [
            'features' => is_array($features) && $features ? $features : config('rt.default_features'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [
            'site_name' => ['required', 'string', 'max:60'],
            'rt_number' => ['required', 'string', 'max:5'],
            'rw_number' => ['required', 'string', 'max:5'],
            'profile_source' => ['required', Rule::in(['account', 'manual'])],
            'public_url' => ['nullable', 'url:http,https', 'max:200'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png', 'max:512'],
            'features' => ['nullable', 'array', 'max:9'],
            'features.*.icon' => ['nullable', Rule::in(config('rt.feature_icons'))],
            'features.*.title' => ['nullable', 'string', 'max:60'],
            'features.*.text' => ['nullable', 'string', 'max:200'],
        ];
        foreach (self::TEXT as $key) {
            $rules[$key] ??= ['nullable', 'string', 'max:300'];
        }
        foreach (array_keys(self::IMAGES) as $key) {
            $rules[$key] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
        }
        $data = $request->validate($rules);

        $save = [];
        foreach (self::TEXT as $key) {
            $save[$key] = trim((string) ($data[$key] ?? ''));
        }
        $save['profile_source'] = $data['profile_source'];
        $save['show_stats'] = $request->boolean('show_stats') ? '1' : '0';

        foreach (self::IMAGES + ['favicon' => 'logo'] as $key => $folder) {
            $old = Setting::raw($key);
            if ($request->boolean('remove_'.$key)) {
                Uploads::delete($old);
                $old = '';
            }
            $save[$key] = Uploads::replace($request->file($key), $old ?: null, $folder) ?? '';
        }

        $save['features'] = json_encode(collect($data['features'] ?? [])
            ->filter(fn ($f) => trim($f['title'] ?? '') !== '')
            ->map(fn ($f) => ['icon' => $f['icon'] ?? 'bi-star', 'title' => trim($f['title']), 'text' => trim($f['text'] ?? '')])
            ->values()->all(), JSON_UNESCAPED_UNICODE);

        Setting::put($save);

        return redirect()->route('admin.settings.edit', ['tab' => $request->input('tab')])->with('success', 'Pengaturan situs disimpan.');
    }
}
