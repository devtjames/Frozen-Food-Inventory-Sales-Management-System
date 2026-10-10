<?php

namespace App\Notifications;

use App\Enums\AdminNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly AdminNotificationType $notificationType,
        private readonly string $title,
        private readonly string $description,
        private readonly ?string $relatedUrl = null,
        private readonly ?int $productId = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->notificationType->value,
            'title' => $this->title,
            'description' => $this->description,
            'related_url' => $this->relatedUrl,
            'product_id' => $this->productId,
        ];
    }
}
