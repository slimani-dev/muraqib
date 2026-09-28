<?php

namespace App\Services\Git;

use App\Models\GitAccount;
use App\Models\Repository;
use App\Models\RepositoryStat;
use Illuminate\Support\Carbon;

/**
 * What the repository widgets need, read from the database only.
 */
class RepositoryDashboard
{
    /**
     * @return list<array<string, mixed>>
     */
    public function repositories(): array
    {
        $weekAgo = today()->subDays(7)->toDateString();

        return Repository::query()
            ->with(['account', 'stats' => fn ($query) => $query->where('date', '>=', $weekAgo)->orderBy('date')])
            ->orderByDesc('is_managed')
            ->orderBy('name')
            ->get()
            ->map(function (Repository $repository): array {
                $oldest = $repository->stats->first();

                return [
                    'id' => $repository->id,
                    'title' => $repository->title(),
                    'full_name' => $repository->fullName(),
                    'url' => $repository->html_url ?: $repository->account->webUrl().'/'.$repository->fullName(),
                    'description' => $repository->description,
                    'logo_url' => $repository->logo_url,
                    'provider' => $repository->account->provider->value,
                    'provider_label' => $repository->account->provider->getLabel(),
                    'provider_icon' => $repository->account->provider->iconifyIcon(),
                    'is_managed' => $repository->is_managed,
                    'language' => $repository->language,
                    'stars' => $repository->stars,
                    'forks' => $repository->forks,
                    'open_issues' => $repository->open_issues,
                    'open_pull_requests' => $repository->open_pull_requests,
                    'pushed_at' => $repository->pushed_at?->toIso8601String(),
                    'latest_release' => $repository->latest_release,
                    'ci' => $repository->ci,
                    'pull_requests' => $repository->pull_requests ?? [],
                    'issues' => $repository->issues ?? [],
                    'downloads_total' => $repository->totalDownloads(),
                    'downloads_monthly' => $repository->monthlyDownloads(),
                    'stars_week_change' => $oldest && $repository->stars !== null && $oldest->stars !== null
                        ? $repository->stars - $oldest->stars
                        : null,
                    'synced_at' => $repository->synced_at?->toIso8601String(),
                    'sync_error' => $repository->sync_error !== null,
                ];
            })
            ->all();
    }

    /**
     * Contribution calendars of the accounts that have one (GitHub for now).
     *
     * @return list<array<string, mixed>>
     */
    public function contributions(): array
    {
        return GitAccount::query()
            ->whereNotNull('contributions')
            ->orderBy('name')
            ->get()
            ->map(fn (GitAccount $account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'provider_icon' => $account->provider->iconifyIcon(),
                'profile_url' => $account->webUrl().'/'.$account->contributions['username'],
                'synced_at' => $account->contributions_synced_at?->toIso8601String(),
                ...$account->contributions,
            ])
            ->all();
    }

    /**
     * Daily totals across every repository for the last 30 days, for the stars & downloads sparklines.
     *
     * @return array{dates: list<string>, stars: list<int>, downloads: list<int>}
     */
    public function trends(int $days = 30): array
    {
        $totals = RepositoryStat::query()
            ->where('date', '>=', today()->subDays($days - 1)->toDateString())
            ->selectRaw('date, SUM(COALESCE(stars, 0)) as stars, SUM(COALESCE(downloads, 0)) as downloads')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'dates' => $totals->map(fn (RepositoryStat $row): string => Carbon::parse($row->date)->toDateString())->all(),
            'stars' => $totals->map(fn (RepositoryStat $row): int => (int) $row->stars)->all(),
            'downloads' => $totals->map(fn (RepositoryStat $row): int => (int) $row->downloads)->all(),
        ];
    }
}
