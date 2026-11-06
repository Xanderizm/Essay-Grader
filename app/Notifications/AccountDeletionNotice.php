<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class AccountDeletionNotice extends Notification implements ShouldQueue
{
    use Queueable;

    // $purgeAt null = akun sudah dihapus sekarang
    public function __construct(public ?Carbon $purgeAt = null)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting('Halo, ' . $notifiable->name . '!');

        if ($this->purgeAt === null) {
            return $mail->subject('Akun Penilai Esai Anda sudah dihapus')
                ->line('Akun Anda beserta seluruh penilaian, jawaban, dan nilainya sudah dihapus permanen.')
                ->line('Terima kasih sudah memakai Penilai Esai.');
        }

        return $mail->subject('Akun Anda dijadwalkan untuk dihapus')
            ->line('Kami menerima permintaan penghapusan akun Anda. Akun dan seluruh datanya akan dihapus permanen pada ' . $this->purgeAt->timezone('Asia/Jakarta')->translatedFormat('d F Y') . '.')
            ->line('Berubah pikiran? Masuk kembali sebelum tanggal itu dan penghapusan otomatis dibatalkan.');
    }
}