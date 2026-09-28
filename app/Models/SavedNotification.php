<?php

namespace App\Models;

use Database\Factories\SavedNotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A GitHub notification saved from the inbox widget. GitHub's API has no "save",
 * so saved notifications are kept here, with a copy of the notification so they
 * stay visible after being marked as done.
 *
 * @property array<string, mixed> $notification
 */
class SavedNotification extends Model
{
    /** @use HasFactory<SavedNotificationFactory> */
    use HasFactory;

    protected $fillable = [
        'git_account_id',
        'thread_id',
        'notification',
    ];

    protected function casts(): array
    {
        return [
            'notification' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(GitAccount::class, 'git_account_id');
    }
}
