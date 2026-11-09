<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notifikasi umum portal (lonceng di kanan atas).
 * Jenis: persetujuan | tagihan | pengumuman | aduan | darurat | info
 * Saat ini memakai kanal "database"; kanal lain (mail, WhatsApp) bisa ditambahkan di via().
 */
class PortalNotice extends Notification
{
    public function __construct(
        public string $kind,
        public string $title,
        public string $body = '',
        public ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'title' => mb_substr($this->title, 0, 150),
            'body' => mb_substr($this->body, 0, 300),
            'url' => $this->url,
        ];
    }
}
