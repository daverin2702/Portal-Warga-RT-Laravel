<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function __invoke(): View
    {
        return view('organization', [
            'holders' => User::query()->where('position', '<>', 'warga')->orderBy('name')->get()->groupBy('position'),
            'term' => setting('chair_term'),
        ]);
    }
}
