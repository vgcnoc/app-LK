<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingStatusNotification extends Notification
{
    use Queueable;

    public $customerName;
    public $status;
    public $keterangan;

    /**
     * Create a new notification instance.
     */
    public function __construct($customerName, $status, $keterangan = null)
    {
        $this->customerName = $customerName;
        $this->status = $status;
        $this->keterangan = $keterangan;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $message = "Status Booking '{$this->customerName}' menjadi {$this->status}.";
        if ($this->keterangan) {
            $message .= " Alasan: {$this->keterangan}";
        }

        return [
            'title' => 'Update Status Booking',
            'message' => $message,
            'status' => $this->status,
            'customer_name' => $this->customerName,
            'keterangan' => $this->keterangan
        ];
    }
}
