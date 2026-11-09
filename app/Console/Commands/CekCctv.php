<?php

namespace App\Console\Commands;

use App\Models\Camera;
use App\Models\User;
use App\Services\CameraProbe;
use App\Support\Notify;
use Illuminate\Console\Command;

/** Cek koneksi semua kamera aktif; beri tahu admin & Seksi Keamanan saat kamera putus atau pulih. */
class CekCctv extends Command
{
    protected $signature = 'rt:cek-cctv';

    protected $description = 'Cek koneksi kamera CCTV aktif dan kirim notifikasi bila statusnya berubah';

    public function handle(CameraProbe $probe): int
    {
        $down = [];
        $up = [];
        foreach (Camera::query()->where('active', true)->get() as $camera) {
            $before = $camera->last_status;
            [$ok, $msg] = $probe->check($camera);
            $this->line(($ok ? '<info>OK</info>    ' : '<error>GAGAL</error> ').$camera->name.' - '.$msg);
            if (! $ok && $before !== 'offline') {
                $down[] = $camera->name;
            } elseif ($ok && $before === 'offline') {
                $up[] = $camera->name;
            }
        }

        $to = User::query()->where('role', 'admin')
            ->orWhere(fn ($q) => $q->where('role', 'pengurus')->whereIn('position', ['seksi_keamanan', 'tim_keamanan']))->get();
        if ($down) {
            Notify::send($to, 'darurat', 'CCTV terputus: '.implode(', ', $down), 'Periksa daya, jaringan, atau gateway kamera.', route('admin.cameras.index'), true);
        }
        if ($up) {
            Notify::send($to, 'info', 'CCTV kembali terhubung: '.implode(', ', $up), '', route('cctv.index'), true);
        }

        return self::SUCCESS;
    }
}
