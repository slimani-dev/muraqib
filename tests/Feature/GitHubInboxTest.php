<?php

use App\Enums\GitProvider;
use App\Models\GitAccount;
use App\Models\SavedNotification;
use App\Models\User;
use App\Services\Git\GitHubInbox;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->account = GitAccount::factory()->create(['token' => 'gh-token']);
    $this->user = User::factory()->create();
});

function fakeGitHubNotifications(): void
{
    Http::fake([
        'api.github.com/notifications?*' => Http::response([[
            'id' => '111',
            'unread' => true,
            'reason' => 'review_requested',
            'updated_at' => '2026-09-27T10:00:00Z',
            'subject' => ['title' => 'Add a thing', 'type' => 'PullRequest', 'url' => 'https://api.github.com/repos/me/app/pulls/5'],
            'repository' => ['full_name' => 'me/app', 'html_url' => 'https://github.com/me/app', 'owner' => ['avatar_url' => 'https://avatars.example.com/me']],
        ]]),
        'api.github.com/notifications/threads/*' => Http::response(null, 205),
    ]);
}

test('the inbox lists notifications with website links', function () {
    fakeGitHubNotifications();
    $inbox = app(GitHubInbox::class)->accounts();

    expect($inbox[0]['notifications'][0])
        ->id->toBe('111')
        ->number->toBe('5')
        ->url->toBe('https://github.com/me/app/pull/5')
        ->saved->toBeFalse();
});

test('notification actions call the matching github endpoints', function (string $action, string $method, string $path) {
    fakeGitHubNotifications();
    $this->actingAs($this->user)
        ->post(route('api.github.notifications.update', $this->account), ['action' => $action, 'threads' => ['111']])
        ->assertRedirect();

    Http::assertSent(fn (Request $request): bool => $request->method() === $method
        && str_ends_with($request->url(), $path)
        && $request->hasHeader('Authorization', 'Bearer gh-token'));
})->with([
    'mark as read' => ['read', 'PATCH', '/notifications/threads/111'],
    'mark as done' => ['done', 'DELETE', '/notifications/threads/111'],
    'unsubscribe' => ['unsubscribe', 'PUT', '/notifications/threads/111/subscription'],
]);

test('saving keeps a copy of the notification, and unsaving removes it', function () {
    fakeGitHubNotifications();
    $this->actingAs($this->user)
        ->post(route('api.github.notifications.update', $this->account), ['action' => 'save', 'threads' => ['111']]);

    expect(SavedNotification::query()->first())->thread_id->toBe('111')->notification->title->toBe('Add a thing')
        ->and(app(GitHubInbox::class)->accounts()[0]['notifications'][0]['saved'])->toBeTrue();

    $this->actingAs($this->user)
        ->post(route('api.github.notifications.update', $this->account), ['action' => 'unsave', 'threads' => ['111']]);

    expect(SavedNotification::query()->count())->toBe(0);
});

test('actions are validated and only work on github accounts', function () {
    fakeGitHubNotifications();
    $this->actingAs($this->user)
        ->post(route('api.github.notifications.update', $this->account), ['action' => 'delete-repo', 'threads' => ['111']])
        ->assertInvalid('action');

    $gitlab = GitAccount::factory()->provider(GitProvider::GitLab)->create();

    $this->actingAs($this->user)
        ->post(route('api.github.notifications.update', $gitlab), ['action' => 'done', 'threads' => ['111']])
        ->assertNotFound();
});

test('a token that cannot read notifications shows an error instead of failing', function () {
    Http::fake(['api.github.com/*' => Http::response(['message' => 'Resource not accessible'], 403)]);

    expect(app(GitHubInbox::class)->accounts()[0])->error->toBeTrue()->notifications->toBe([]);
});
