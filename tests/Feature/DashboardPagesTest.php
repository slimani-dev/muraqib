<?php

use App\Actions\Teams\CreateTeam;
use App\Enums\TeamRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\DashboardPage;
use App\Models\User;
use App\Services\Dashboard\WidgetCatalog;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->team = app(CreateTeam::class)->handle($this->owner, 'Home Lab', isPersonal: true);
    $this->owner->update(['current_team_id' => $this->team->id]);
});

function layout(array $middle = [], array $left = [], array $right = []): array
{
    return ['version' => 1, 'zones' => ['left' => $left, 'middle' => $middle, 'right' => $right]];
}

test('the dashboard creates and shows the default page with its built-in layout', function () {
    $this->actingAs($this->owner)
        ->get(route('dashboard', ['current_team' => $this->team->slug]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboardPage.name', 'Dashboard')
            ->where('dashboardPage.is_default', true)
            ->where('dashboardPage.layout', null)
            ->where('canEditDashboard', true)
            ->has('dashboardPages', 1));
});

test('owners add pages, which get their own url and an empty layout', function () {
    $this->actingAs($this->owner)
        ->post(route('dashboard.pages.store', ['current_team' => $this->team->slug]), ['name' => 'Media Room'])
        ->assertRedirect(route('dashboard.page', ['current_team' => $this->team->slug, 'page' => 'media-room']));

    $this->actingAs($this->owner)
        ->get(route('dashboard.page', ['current_team' => $this->team->slug, 'page' => 'media-room']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboardPage.name', 'Media Room')
            ->where('dashboardPage.layout', layout())
            ->has('dashboardPages', 2));
});

test('page slugs avoid other dashboard routes', function () {
    $this->actingAs($this->owner)->post(route('dashboard.pages.store', ['current_team' => $this->team->slug]), ['name' => 'Widgets']);

    expect($this->team->dashboardPages()->where('name', 'Widgets')->value('slug'))->toBe('widgets-2');
});

test('a page stores its layout with sections and tabs', function () {
    $page = $this->team->defaultDashboardPage();
    $layout = layout(middle: [
        ['id' => 'a', 'kind' => 'widget', 'widget' => 'portainer', 'columns' => 3],
        ['id' => 'b', 'kind' => 'section', 'title' => 'Code', 'columns' => 2, 'items' => [
            ['id' => 'c', 'kind' => 'widget', 'widget' => 'repos', 'columns' => 2],
        ]],
        ['id' => 'd', 'kind' => 'tabs', 'columns' => 1, 'default_tab' => 't1', 'tabs' => [
            ['id' => 't1', 'title' => 'Overview', 'items' => [['id' => 'e', 'kind' => 'widget', 'widget' => 'media-12']]],
            ['id' => 't2', 'title' => 'Details', 'items' => [
                ['id' => 'f', 'kind' => 'section', 'title' => 'Infra', 'items' => [['id' => 'g', 'kind' => 'widget', 'widget' => 'netdata-3']]],
            ]],
        ]],
    ], right: [['id' => 'h', 'kind' => 'widget', 'widget' => 'weather']]);

    $this->actingAs($this->owner)
        ->patch(route('dashboard.pages.update', ['current_team' => $this->team->slug, 'dashboardPage' => $page]), ['layout' => $layout, 'name' => 'Home'])
        ->assertSessionHasNoErrors();

    expect($page->fresh())->layout->toBe($layout)->name->toBe('Home')->is_default->toBeTrue();
});

test('layouts cannot nest deeper than tabs holding sections', function (array $item, string $message) {
    $page = $this->team->defaultDashboardPage();

    $this->actingAs($this->owner)
        ->patch(route('dashboard.pages.update', ['current_team' => $this->team->slug, 'dashboardPage' => $page]), ['layout' => layout(middle: [$item])])
        ->assertSessionHasErrors(['layout' => $message]);
})->with([
    'section in a section' => [
        ['id' => 'a', 'kind' => 'section', 'title' => 'Outer', 'items' => [['id' => 'b', 'kind' => 'section', 'title' => 'Inner', 'items' => []]]],
        'Sections can only be placed in a zone or a tab, not inside another section.',
    ],
    'tabs in a tab' => [
        ['id' => 'a', 'kind' => 'tabs', 'default_tab' => 't', 'tabs' => [['id' => 't', 'title' => 'One', 'items' => [
            ['id' => 'b', 'kind' => 'tabs', 'default_tab' => 'u', 'tabs' => [['id' => 'u', 'title' => 'Two', 'items' => []]]],
        ]]]],
        'Tabbed sections can only be placed directly in a zone.',
    ],
    'unknown widget' => [['id' => 'a', 'kind' => 'widget', 'widget' => 'bitcoin-miner'], 'zones.middle.0 is not a known widget.'],
    'same widget twice' => [
        ['id' => 'a', 'kind' => 'section', 'title' => 'S', 'items' => [['id' => 'b', 'kind' => 'widget', 'widget' => 'weather'], ['id' => 'c', 'kind' => 'widget', 'widget' => 'weather']]],
        'The weather widget is placed twice.',
    ],
    'default tab missing' => [
        ['id' => 'a', 'kind' => 'tabs', 'default_tab' => 'nope', 'tabs' => [['id' => 't', 'title' => 'One', 'items' => []]]],
        'zones.middle.0 must name one of its tabs as the default.',
    ],
    'bad column span' => [['id' => 'a', 'kind' => 'widget', 'widget' => 'weather', 'columns' => 4], 'zones.middle.0 columns must be 1, 2 or 3.'],
]);

test('resetting the default page brings back the built-in layout', function () {
    $page = $this->team->defaultDashboardPage();
    $page->update(['layout' => layout()]);

    $this->actingAs($this->owner)
        ->patch(route('dashboard.pages.update', ['current_team' => $this->team->slug, 'dashboardPage' => $page]), ['layout' => null]);

    expect($page->fresh()->layout)->toBeNull();
});

test('the default page cannot be deleted but other pages can', function () {
    $default = $this->team->defaultDashboardPage();
    $extra = DashboardPage::factory()->create(['team_id' => $this->team->id]);

    $this->actingAs($this->owner)
        ->delete(route('dashboard.pages.destroy', ['current_team' => $this->team->slug, 'dashboardPage' => $default]))
        ->assertStatus(422);

    $this->actingAs($this->owner)
        ->delete(route('dashboard.pages.destroy', ['current_team' => $this->team->slug, 'dashboardPage' => $extra]))
        ->assertRedirect();

    expect(DashboardPage::query()->pluck('id')->all())->toBe([$default->id]);
});

test('members see the shared layout but cannot change it', function () {
    $member = User::factory()->create();
    $this->team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->update(['current_team_id' => $this->team->id]);
    $page = $this->team->defaultDashboardPage();

    $this->actingAs($member)
        ->get(route('dashboard', ['current_team' => $this->team->slug]))
        ->assertInertia(fn (Assert $inertia) => $inertia->where('canEditDashboard', false));

    $this->actingAs($member)
        ->patch(route('dashboard.pages.update', ['current_team' => $this->team->slug, 'dashboardPage' => $page]), ['name' => 'Mine'])
        ->assertForbidden();

    $this->actingAs($member)
        ->post(route('dashboard.pages.store', ['current_team' => $this->team->slug]), ['name' => 'Mine'])
        ->assertForbidden();
});

test('pages of another team are not reachable', function () {
    $other = User::factory()->create();
    $otherTeam = app(CreateTeam::class)->handle($other, 'Other', isPersonal: true);
    $otherPage = $otherTeam->defaultDashboardPage();

    $this->actingAs($this->owner)
        ->patch(route('dashboard.pages.update', ['current_team' => $this->team->slug, 'dashboardPage' => $otherPage]), ['name' => 'Hijacked'])
        ->assertNotFound();
});

test('the php widget catalog matches the dashboard widget registry', function () {
    $registry = file_get_contents(resource_path('js/components/dashboard/widgets/registry.ts'));
    $fixed = str($registry)->after('export const widgets: WidgetDefinition[] = [')->before('];');

    preg_match_all("/\\{ id: '([a-z-]+)'/", (string) $fixed, $matches);

    expect($matches[1])->toEqualCanonicalizing(WidgetCatalog::FIXED)
        ->and($registry)->toContain('id: `media-${service.id}`', 'id: `netdata-${server.id}`');
});

test('every new team starts with its default dashboard page', function () {
    $pages = $this->team->dashboardPages()->get();

    expect($pages)->toHaveCount(1)
        ->and($pages->first())->is_default->toBeTrue()->slug->toBe('dashboard');
});

test('the default page cannot be deleted or demoted, even directly', function () {
    $default = $this->team->defaultDashboardPage();

    expect(fn () => $default->delete())->toThrow(LogicException::class)
        ->and(fn () => $default->update(['is_default' => false]))->toThrow(LogicException::class)
        ->and($default->fresh())->not->toBeNull()->is_default->toBeTrue();
});

test('saving answers with only the page props, so the dashboard does not reload every widget', function () {
    $page = $this->team->defaultDashboardPage();
    $headers = [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
        'X-Inertia-Partial-Component' => 'Dashboard',
        'X-Inertia-Partial-Data' => 'dashboardPage,dashboardPages',
        'Referer' => route('dashboard', ['current_team' => $this->team->slug]),
    ];

    $response = $this->actingAs($this->owner)
        ->followingRedirects()
        ->withHeaders($headers)
        ->patch(route('dashboard.pages.update', ['current_team' => $this->team->slug, 'dashboardPage' => $page]), ['name' => 'Home', 'layout' => layout()]);

    expect(array_keys($response->json('props')))->not->toContain('containers', 'repositories', 'media_services')
        ->and($response->json('props.dashboardPage.name'))->toBe('Home');
});
