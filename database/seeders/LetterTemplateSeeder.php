<?php

namespace Database\Seeders;

use App\Models\LetterTemplate;
use Illuminate\Database\Seeder;

/** Tujuh templat surat bawaan. Aman dijalankan ulang: templat yang namanya sudah ada tidak ditimpa. */
class LetterTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = json_decode(file_get_contents(__DIR__.'/data/letter_templates.json'), true) ?: [];
        foreach ($templates as $t) {
            LetterTemplate::query()->firstOrCreate(['name' => $t['name']], ['body' => $t['body'], 'fields' => $t['fields']]);
        }
    }
}
