<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Support\Board;
use Illuminate\View\View;
use Throwable;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        try {
            $stats = [
                'households' => Household::query()->count(),
                'residents' => (int) Household::query()->sum('members_count'),
                'blocks' => Household::query()->whereNotNull('block')->where('block', '<>', '')->distinct()->count('block'),
            ];
        } catch (Throwable) {
            $stats = ['households' => 0, 'residents' => 0, 'blocks' => 0];
        }

        $features = json_decode((string) setting('features'), true);

        return view('home', [
            'stats' => $stats,
            'chair' => Board::profile('chair'),
            'vice' => Board::profile('vice'),
            'features' => is_array($features) && $features ? $features : config('rt.default_features'),
        ]);
    }
}
