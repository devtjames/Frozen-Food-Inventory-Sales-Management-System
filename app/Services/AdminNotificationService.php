<?php

namespace App\Services;

use App\Enums\AdminNotificationType;
use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\AdminActivityNotification;
use Illuminate\Support\Facades\Notification;

class AdminNotificationService
{
    public function send(
        AdminNotificationType $type,
        string $title,
        string $description,
        ?string $relatedUrl = null,
        ?int $productId = null,
    ): void {
        $administrators = User::query()
            ->where('role', UserRole::Administrator)
            ->where('is_active', true)
            ->get();

        if ($administrators->isEmpty()) {
            return;
        }

        Notification::send($administrators, new AdminActivityNotification(
            $type,
            $title,
            $description,
            $relatedUrl,
            $productId,
        ));
    }
}
