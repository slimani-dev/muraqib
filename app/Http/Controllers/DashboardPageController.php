<?php

namespace App\Http\Controllers;

use App\Enums\TeamPermission;
use App\Http\Requests\Dashboard\StoreDashboardPageRequest;
use App\Http\Requests\Dashboard\UpdateDashboardPageRequest;
use App\Models\DashboardPage;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * A team's dashboard pages. Each page stores its own layout (zones, sections, tabs, spans).
 */
class DashboardPageController extends Controller
{
    /** Slugs used by other dashboard routes. */
    public const RESERVED_SLUGS = ['widgets', 'weather', 'pages'];

    public function store(StoreDashboardPageRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $team->defaultDashboardPage();

        $page = $team->dashboardPages()->create([
            'name' => $request->validated('name'),
            'slug' => $this->uniqueSlug($team, $request->validated('name')),
            'position' => ($team->dashboardPages()->max('position') ?? 0) + 1,
            'layout' => DashboardPage::emptyLayout(),
            'updated_by' => $request->user()->id,
        ]);

        return to_route('dashboard.page', ['page' => $page->slug]);
    }

    public function update(UpdateDashboardPageRequest $request, string $currentTeam, DashboardPage $dashboardPage): RedirectResponse
    {
        $this->ensureTeamPage($request, $dashboardPage);

        $attributes = $request->safe()->only(['name']);

        if ($request->exists('layout')) {
            $attributes['layout'] = $request->validated('layout') ?? ($dashboardPage->is_default ? null : DashboardPage::emptyLayout());
        }

        $dashboardPage->update([...$attributes, 'updated_by' => $request->user()->id]);

        return back();
    }

    public function destroy(Request $request, string $currentTeam, DashboardPage $dashboardPage): RedirectResponse
    {
        $this->ensureTeamPage($request, $dashboardPage);
        abort_unless($request->user()->hasTeamPermission($dashboardPage->team, TeamPermission::UpdateDashboard), 403);
        abort_if($dashboardPage->is_default, 422, 'The default dashboard page can be renamed but not deleted.');

        $dashboardPage->delete();

        return to_route('dashboard');
    }

    private function ensureTeamPage(Request $request, DashboardPage $page): void
    {
        abort_unless($page->team_id === $request->user()->currentTeam?->id, 404);
    }

    private function uniqueSlug(Team $team, string $name): string
    {
        $base = Str::slug($name) ?: 'page';
        $slug = $base;
        $suffix = 2;

        while (in_array($slug, self::RESERVED_SLUGS, true) || $team->dashboardPages()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
