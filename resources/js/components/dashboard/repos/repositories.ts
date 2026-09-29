import { formatDistanceToNowStrict } from 'date-fns';

/** One entry of the dashboard's `repositories` prop (App\Services\Git\RepositoryDashboard). */
export type RepositorySummary = {
    id: number;
    title: string;
    full_name: string;
    url: string;
    description: string | null;
    logo_url: string | null;
    provider: string;
    provider_label: string;
    provider_icon: string;
    is_managed: boolean;
    language: string | null;
    stars: number | null;
    forks: number | null;
    open_issues: number | null;
    open_pull_requests: number | null;
    pushed_at: string | null;
    latest_release: {
        tag: string;
        name: string | null;
        url: string | null;
        published_at: string | null;
    } | null;
    ci: {
        status: 'success' | 'failure' | 'running' | 'cancelled' | 'unknown';
        url: string | null;
        finished_at: string | null;
    } | null;
    pull_requests: {
        number: number | string;
        title: string;
        url: string | null;
        author: string | null;
        created_at: string | null;
        draft: boolean;
        reviewers: number;
    }[];
    issues: {
        number: number | string;
        title: string;
        url: string | null;
        author: string | null;
        created_at: string | null;
        comments: number;
    }[];
    downloads_total: number | null;
    downloads_monthly: number | null;
    stars_week_change: number | null;
    synced_at: string | null;
    sync_error: boolean;
};

export type RepositoryTrends = {
    dates: string[];
    stars: number[];
    downloads: number[];
};

export const ADMIN_REPOSITORIES_URL = '/admin/repositories';

/** 1234 → "1.2k", 3400000 → "3.4M". */
export const formatCount = (value: number | null | undefined): string => {
    if (value === null || value === undefined) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, {
        notation: 'compact',
        maximumFractionDigits: 1,
    }).format(value);
};

const unitShort: Record<string, string> = {
    second: 's',
    minute: 'm',
    hour: 'h',
    day: 'd',
    month: 'mo',
    year: 'y',
};

/** "3h", "2d", "4mo" from an ISO date. */
export const age = (date: string | null | undefined): string => {
    if (!date) {
        return '';
    }

    const [amount, unit] = formatDistanceToNowStrict(new Date(date)).split(' ');

    return `${amount}${unitShort[unit.replace(/s$/, '')] ?? unit}`;
};

export const ciAppearance: Record<
    string,
    { label: string; dot: string; text: string }
> = {
    success: { label: 'Passing', dot: 'bg-chart-3', text: 'text-chart-3' },
    failure: {
        label: 'Failing',
        dot: 'bg-destructive',
        text: 'text-destructive',
    },
    running: {
        label: 'Running',
        dot: 'bg-chart-1 animate-pulse',
        text: 'text-chart-1',
    },
    cancelled: {
        label: 'Cancelled',
        dot: 'bg-muted-foreground',
        text: 'text-muted-foreground',
    },
    unknown: {
        label: 'Unknown',
        dot: 'bg-muted-foreground',
        text: 'text-muted-foreground',
    },
};
