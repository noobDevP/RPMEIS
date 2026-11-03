<?php

namespace App\Services;

use App\Models\Announcement;

class AnnouncementService
{
    public function current(): ?Announcement
    {
        return Announcement::current();
    }

    public function updateMessage(string $message): Announcement
    {
        return Announcement::query()->updateOrCreate(
            ['id' => 1],
            ['message' => $message, 'created_at' => now()],
        );
    }
}
