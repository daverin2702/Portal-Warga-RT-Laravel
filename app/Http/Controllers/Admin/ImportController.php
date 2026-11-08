<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HouseholdImporter;
use App\Services\SpreadsheetReader;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportController extends Controller
{
    public function create(): View
    {
        return view('admin.import', ['result' => null]);
    }

    public function store(Request $request, SpreadsheetReader $reader, HouseholdImporter $importer): View
    {
        // Cek ekstensi (bukan MIME): sebagian server mendeteksi .xlsx sebagai zip. Isi berkas divalidasi oleh SpreadsheetReader.
        $request->validate(['file' => ['required', 'file', 'max:2048', 'extensions:xlsx,csv']]);
        $file = $request->file('file');

        try {
            $rows = $reader->read($file->getRealPath(), $file->getClientOriginalExtension());
            @set_time_limit(120);
            $result = $importer->import($rows);
        } catch (RuntimeException $e) {
            $result = ['created' => [], 'skipped' => [], 'error' => $e->getMessage()];
        }

        return view('admin.import', ['result' => $result]);
    }

    public function template(): BinaryFileResponse
    {
        return response()->download(resource_path('templates/template_data_warga.xlsx'));
    }
}
