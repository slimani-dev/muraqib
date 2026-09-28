<?php

use App\Actions\Teams\CreateTeam;
use App\Enums\GitProvider;
use App\Filament\Resources\GitAccounts\Pages\ListGitAccounts;
use App\Filament\Resources\Repositories\Pages\ListRepositories;
use App\Jobs\SyncRepository;
use App\Models\GitAccount;
use App\Models\Repository;
use App\Models\User;
use App\Services\Git\ContributionsSyncer;
use App\Services\Git\RepositorySyncer;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

test('github repositories sync stars, prs, issues, release and ci', function () {
    Queue::fake();
    $repository = Repository::factory()
        ->for(GitAccount::factory()->state(['token' => 'gh-token']), 'account')
        ->create(['owner' => 'laravel', 'name' => 'framework', 'packagist_package' => 'laravel/framework']);

    Http::fake([
        'api.github.com/repos/laravel/framework' => Http::response([
            'description' => 'The Laravel Framework.', 'html_url' => 'https://github.com/laravel/framework', 'default_branch' => '12.x',
            'language' => 'PHP', 'stargazers_count' => 34000, 'forks_count' => 11000, 'open_issues_count' => 12, 'pushed_at' => '2026-09-27T10:00:00Z',
        ]),
        'api.github.com/repos/laravel/framework/pulls*' => Http::response([
            ['number' => 7, 'title' => 'Add a thing', 'html_url' => 'https://github.com/laravel/framework/pull/7', 'user' => ['login' => 'taylor'], 'created_at' => '2026-09-26T10:00:00Z', 'draft' => false, 'requested_reviewers' => [['login' => 'me']]],
            ['number' => 8, 'title' => 'WIP', 'html_url' => null, 'user' => ['login' => 'nuno'], 'created_at' => '2026-09-27T10:00:00Z', 'draft' => true, 'requested_reviewers' => []],
        ]),
        'api.github.com/repos/laravel/framework/issues*' => Http::response([
            ['number' => 9, 'title' => 'Bug', 'html_url' => 'https://github.com/laravel/framework/issues/9', 'user' => ['login' => 'someone'], 'created_at' => '2026-09-25T10:00:00Z', 'comments' => 3],
            ['number' => 7, 'title' => 'Add a thing', 'pull_request' => ['url' => '...']],
        ]),
        'api.github.com/repos/laravel/framework/releases/latest' => Http::response(['tag_name' => 'v12.1.0', 'name' => 'v12.1.0', 'html_url' => 'https://github.com/laravel/framework/releases/tag/v12.1.0', 'published_at' => '2026-09-20T10:00:00Z']),
        'api.github.com/repos/laravel/framework/actions/runs*' => Http::response(['workflow_runs' => [['status' => 'completed', 'conclusion' => 'failure', 'html_url' => 'https://github.com/run/1', 'updated_at' => '2026-09-27T11:00:00Z']]]),
        'packagist.org/*' => Http::response(['downloads' => ['total' => 500000000, 'monthly' => 9000000, 'daily' => 300000]]),
    ]);

    app(RepositorySyncer::class)->sync($repository);

    expect($repository->fresh())
        ->stars->toBe(34000)
        ->open_pull_requests->toBe(2)
        ->open_issues->toBe(10)
        ->latest_release->tag->toBe('v12.1.0')
        ->ci->status->toBe('failure')
        ->pull_requests->toHaveCount(2)
        ->issues->toHaveCount(1)
        ->downloads->packagist->total->toBe(500000000)
        ->sync_error->toBeNull();

    expect($repository->stats()->first())->stars->toBe(34000)->downloads->toBe(500000000);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'api.github.com/repos/laravel/framework')
        && $request->hasHeader('Authorization', 'Bearer gh-token'));
});

test('a repo without releases or ci still syncs', function () {
    Queue::fake();
    $repository = Repository::factory()->create(['owner' => 'me', 'name' => 'tiny']);

    Http::fake([
        'api.github.com/repos/me/tiny' => Http::response(['stargazers_count' => 3, 'open_issues_count' => 0]),
        'api.github.com/repos/me/tiny/pulls*' => Http::response([]),
        'api.github.com/repos/me/tiny/issues*' => Http::response([]),
        'api.github.com/repos/me/tiny/releases/latest' => Http::response(['message' => 'Not Found'], 404),
        'api.github.com/repos/me/tiny/actions/runs*' => Http::response(['message' => 'Not Found'], 404),
    ]);

    app(RepositorySyncer::class)->sync($repository);

    expect($repository->fresh())->stars->toBe(3)->latest_release->toBeNull()->ci->toBeNull()->sync_error->toBeNull();
});

test('a failed sync keeps the last data and records the error', function () {
    Queue::fake();
    $repository = Repository::factory()->synced()->create(['stars' => 42]);
    Http::fake(['api.github.com/*' => Http::response(['message' => 'Bad credentials'], 401)]);

    app(RepositorySyncer::class)->sync($repository);

    expect($repository->fresh())->stars->toBe(42)->sync_error->toContain('401');
});

test('gitlab, gitea-compatible and bitbucket hosts map to the same shape', function (GitProvider $provider, ?string $baseUrl, array $responses, int $stars, int $pullRequests) {
    Queue::fake();
    $repository = Repository::factory()
        ->for(GitAccount::factory()->provider($provider, $baseUrl), 'account')
        ->create(['owner' => 'group', 'name' => 'project']);

    Http::fake(array_map(fn (array $body) => Http::response($body), $responses) + ['*' => Http::response([], 404)]);

    app(RepositorySyncer::class)->sync($repository);

    expect($repository->fresh())
        ->sync_error->toBeNull()
        ->stars->toBe($stars)
        ->open_pull_requests->toBe($pullRequests)
        ->html_url->not->toBeNull();
})->with([
    'gitlab' => [GitProvider::GitLab, null, [
        'gitlab.com/api/v4/projects/group%2Fproject' => ['star_count' => 5, 'web_url' => 'https://gitlab.com/group/project', 'default_branch' => 'main'],
        'gitlab.com/api/v4/projects/group%2Fproject/merge_requests*' => [['iid' => 1, 'title' => 'MR', 'web_url' => 'x', 'author' => ['username' => 'a'], 'created_at' => '2026-09-01T00:00:00Z', 'draft' => false, 'reviewers' => []]],
    ], 5, 1],
    'codeberg' => [GitProvider::Codeberg, null, [
        'codeberg.org/api/v1/repos/group/project' => ['stars_count' => 9, 'open_pr_counter' => 2, 'html_url' => 'https://codeberg.org/group/project'],
        'codeberg.org/api/v1/repos/group/project/pulls*' => [],
    ], 9, 2],
    'self-hosted forgejo' => [GitProvider::Forgejo, 'https://git.example.com', [
        'git.example.com/api/v1/repos/group/project' => ['stars_count' => 1, 'open_pr_counter' => 0, 'html_url' => 'https://git.example.com/group/project'],
        'git.example.com/api/v1/repos/group/project/pulls*' => [],
    ], 1, 0],
]);

test('bitbucket has no stars but syncs prs', function () {
    Queue::fake();
    $repository = Repository::factory()
        ->for(GitAccount::factory()->provider(GitProvider::Bitbucket)->state(['username' => 'me', 'token' => 'app-pass']), 'account')
        ->create(['owner' => 'team', 'name' => 'app']);

    Http::fake([
        'api.bitbucket.org/2.0/repositories/team/app' => Http::response(['description' => '', 'links' => ['html' => ['href' => 'https://bitbucket.org/team/app']], 'mainbranch' => ['name' => 'main']]),
        'api.bitbucket.org/2.0/repositories/team/app/pullrequests*' => Http::response(['size' => 1, 'values' => [['id' => 3, 'title' => 'PR', 'links' => ['html' => ['href' => 'x']], 'author' => ['display_name' => 'Me'], 'created_on' => '2026-09-01T00:00:00Z']]]),
        '*' => Http::response([], 404),
    ]);

    app(RepositorySyncer::class)->sync($repository);

    expect($repository->fresh())->stars->toBeNull()->open_pull_requests->toBe(1)->sync_error->toBeNull();
    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Basic '.base64_encode('me:app-pass')));
});

test('adding a repository queues its first sync', function () {
    Queue::fake();

    Repository::factory()->create();

    Queue::assertPushedOn('low', SyncRepository::class);
});

test('git account tokens are encrypted and never sent back to the form', function () {
    $account = GitAccount::factory()->create(['token' => 'secret-token']);

    expect(DB::table('git_accounts')->value('token'))->not->toContain('secret-token');

    Livewire::actingAs(User::factory()->create())
        ->test(ListGitAccounts::class)
        ->mountTableAction('edit', $account)
        ->assertTableActionDataSet(['token' => null])
        ->setTableActionData(['name' => 'Renamed', 'token' => ''])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    expect($account->fresh())->name->toBe('Renamed')->token->toBe('secret-token');
});

test('self-hosted gitea needs an instance url', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(ListGitAccounts::class)
        ->callAction('create', data: ['provider' => 'gitea', 'name' => 'Home Gitea'])
        ->assertHasFormErrors(['base_url']);
});

test('repositories are added from the admin panel', function () {
    Queue::fake();
    $account = GitAccount::factory()->create();

    Livewire::actingAs(User::factory()->create())
        ->test(ListRepositories::class)
        ->callAction('create', data: ['git_account_id' => $account->id, 'owner' => 'laravel', 'name' => 'framework', 'is_managed' => true])
        ->assertHasNoFormErrors();

    expect(Repository::query()->first())->fullName()->toBe('laravel/framework')->is_managed->toBeTrue();
});

test('repositories:sync queues every repository', function () {
    Repository::factory()->count(2)->create();
    Queue::fake();

    $this->artisan('repositories:sync')->assertSuccessful();

    Queue::assertPushed(SyncRepository::class, 2);
});

test('the dashboard gets repositories with their weekly star change and no tokens', function () {
    Queue::fake();
    $user = User::factory()->create();
    $team = app(CreateTeam::class)->handle($user, 'Repo Team', isPersonal: true);
    $user->update(['current_team_id' => $team->id]);

    $repository = Repository::factory()
        ->for(GitAccount::factory()->state(['token' => 'super-secret-token']), 'account')
        ->synced()
        ->managed()
        ->create(['stars' => 120, 'display_name' => 'My Package']);
    $repository->stats()->create(['date' => today()->subDays(7), 'stars' => 100]);
    $repository->stats()->create(['date' => today(), 'stars' => 120]);

    $response = $this->actingAs($user)->get(route('dashboard', ['current_team' => $team->slug]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('repositories.0.title', 'My Package')
        ->where('repositories.0.stars_week_change', 20)
        ->where('repositories.0.logo_url', null)
        ->where('repositories.0.provider_icon', 'simple-icons:github')
        ->where('repository_trends.stars', [100, 120]));

    expect($response->getContent())->not->toContain('super-secret-token');
});

test('the github contribution calendar is synced onto the account', function () {
    Queue::fake();
    $account = GitAccount::factory()->create(['token' => 'gh-token']);

    Http::fake(['api.github.com/graphql' => Http::response(['data' => ['viewer' => [
        'login' => 'slimani-dev',
        'contributionsCollection' => [
            'totalCommitContributions' => 239,
            'totalPullRequestContributions' => 7,
            'totalPullRequestReviewContributions' => 0,
            'totalIssueContributions' => 1,
            'restrictedContributionsCount' => 77,
            'contributionCalendar' => [
                'totalContributions' => 342,
                'weeks' => [['contributionDays' => [
                    ['date' => '2026-09-27', 'contributionCount' => 0, 'contributionLevel' => 'NONE'],
                    ['date' => '2026-09-28', 'contributionCount' => 9, 'contributionLevel' => 'FOURTH_QUARTILE'],
                ]]],
            ],
        ],
    ]]])]);

    expect(app(ContributionsSyncer::class)->sync($account))->toBeTrue()
        ->and($account->fresh()->contributions)
        ->total->toBe(342)
        ->commits->toBe(239)
        ->private->toBe(77)
        ->and($account->fresh()->contributions['weeks'][0][1])->toBe(['date' => '2026-09-28', 'count' => 9, 'level' => 4]);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST' && $request->hasHeader('Authorization', 'Bearer gh-token'));
});

test('accounts without a token or on other hosts have no contribution calendar', function (GitProvider $provider, ?string $token) {
    Http::fake();
    $account = GitAccount::factory()->provider($provider)->make(['token' => $token]);

    expect($account->client()->contributions())->toBeNull();
    Http::assertNothingSent();
})->with([
    'github without a token' => [GitProvider::GitHub, null],
    'gitlab' => [GitProvider::GitLab, 'token'],
]);
