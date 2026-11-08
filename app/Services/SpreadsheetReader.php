<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Pembaca .xlsx (sheet pertama) dan .csv tanpa pustaka tambahan.
 * Mengembalikan daftar baris; setiap baris berupa array nilai teks.
 */
class SpreadsheetReader
{
    public function read(string $path, string $extension): array
    {
        return match (strtolower($extension)) {
            'xlsx' => $this->xlsx($path),
            'csv', 'txt' => $this->csv($path),
            default => throw new RuntimeException('Format berkas tidak didukung. Gunakan .xlsx atau .csv.'),
        };
    }

    private function csv(string $path): array
    {
        $h = fopen($path, 'r');
        if (! $h) {
            throw new RuntimeException('Berkas CSV tidak bisa dibuka.');
        }
        $first = (string) fgets($h);
        rewind($h);
        $sep = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $rows = [];
        while (($r = fgetcsv($h, 0, $sep, '"', '\\')) !== false) {
            if ($rows === [] && isset($r[0])) {
                $r[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $r[0]);
            }
            $rows[] = array_map(fn ($v) => (string) $v, $r);
        }
        fclose($h);

        return $rows;
    }

    private function xlsx(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP "zip" belum aktif. Aktifkan di php.ini atau simpan berkas sebagai .csv.');
        }
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Berkas Excel tidak bisa dibuka.');
        }
        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false && ($sx = @simplexml_load_string($xml))) {
            foreach ($sx->si as $si) {
                $shared[] = isset($si->t) ? (string) $si->t : $this->joinRuns($si);
            }
        }
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        $sx = $sheet ? @simplexml_load_string($sheet) : null;
        if (! $sx) {
            throw new RuntimeException('Sheet pertama pada berkas Excel tidak ditemukan.');
        }
        $rows = [];
        foreach ($sx->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                preg_match('/^([A-Z]+)/', (string) $c['r'], $m);
                $idx = 0;
                foreach (str_split($m[1] ?? 'A') as $ch) {
                    $idx = $idx * 26 + (ord($ch) - 64);
                }
                $type = (string) $c['t'];
                $value = (string) $c->v;
                if ($type === 's') {
                    $value = $shared[(int) $value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = isset($c->is->t) ? (string) $c->is->t : $this->joinRuns($c->is);
                }
                $cells[$idx - 1] = $value;
            }
            if ($cells) {
                $max = max(array_keys($cells));
                $rows[] = array_map(fn ($i) => $cells[$i] ?? '', range(0, $max));
            }
        }

        return $rows;
    }

    private function joinRuns(SimpleXMLElement $node): string
    {
        $text = '';
        foreach ($node->r as $r) {
            $text .= (string) $r->t;
        }

        return $text;
    }
}
