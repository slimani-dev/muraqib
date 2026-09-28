<?php

namespace App\Services\Git;

use App\Enums\GitProvider;
use App\Models\GitAccount;
use App\Models\SavedNotification;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * The GitHub notification inbox for the dashboard. Notifications are fetched from the
 * server (GitHub asks not to poll more than once a minute, so they're cached 60 seconds)
 * and the widget's actions go through here too, so tokens never reach the browser.
 */
class GitHubInbox
{
    public const CACHE_SECONDS = 60;

    /**
     * @return list<array{id: int, name: string, username: ?string, error: bool, notifications: list<array<string, mixed>>, saved: list<array<string, mixed>>}>
     */
    public function accounts(): array
    {
        return GitAccount::query()
            ->where('provider', GitProvider::GitHub)
            ->whereNotNull('token')
            ->orderBy('name')
            ->get()
            ->map(function (GitAccount $account): array {
                $notifications = $this->notifications($account);
                $saved = SavedNotification::query()->where('git_account_id', $account->id)->latest()->get();
                $savedIds = $saved->pluck('thread_id')->all();

                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'username' => $account->contributions['username'] ?? null,
                    'error' => $notifications === null,
                    'notifications' => collect($notifications ?? [])
                        ->map(fn (array $notification): array => [...$notification, 'saved' => in_array($notification['id'], $savedIds, true)])
                        ->all(),
                    'saved' => $saved->map(fn (SavedNotification $item): array => [...$item->notification, 'saved' => true])->all(),
                ];
            })
            ->all();
    }

    /**
     * Cached for a minute. Null when GitHub can't be reached or the token can't read notifications.
     *
     * @return list<array<string, mixed>>|null
     */
    public function notifications(GitAccount $account, bool $fresh = false): ?array
    {
        $key = $this->cacheKey($account);

        if ($fresh) {
            Cache::forget($key);
        }

        try {
            return Cache::remember($key, self::CACHE_SECONDS, fn (): array => $this->client($account)->notifications());
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Apply a widget action to a notification thread, then drop the cached inbox.
     *
     * @param  'read'|'done'|'unsubscribe'|'save'|'unsave'  $action
     */
    public function act(GitAccount $account, string $threadId, string $action): void
    {
        match ($action) {
            'read' => $this->client($account)->markNotificationRead($threadId),
            'done' => $this->client($account)->markNotificationDone($threadId),
            'unsubscribe' => $this->client($account)->unsubscribeFromNotification($threadId),
            'save' => $this->save($account, $threadId),
            'unsave' => SavedNotification::query()->where('git_account_id', $account->id)->where('thread_id', $threadId)->delete(),
        };

        Cache::forget($this->cacheKey($account));
    }

    private function save(GitAccount $account, string $threadId): void
    {
        $notification = collect($this->notifications($account) ?? [])->firstWhere('id', $threadId);

        if ($notification === null) {
            return;
        }

        SavedNotification::query()->updateOrCreate(
            ['git_account_id' => $account->id, 'thread_id' => $threadId],
            ['notification' => $notification],
        );
    }

    private function client(GitAccount $account): GitHubClient
    {
        /** @var GitHubClient */
        return $account->client();
    }

    private function cacheKey(GitAccount $account): string
    {
        return "github_inbox_{$account->id}";
    }
}
