<?php

namespace App\Services\Dashboard;

/**
 * The widget ids a dashboard layout may reference. Must match the ids in
 * resources/js/components/dashboard/widgets/registry.ts (a test checks this).
 */
class WidgetCatalog
{
    /** @var list<string> */
    public const FIXED = [
        'portainer',
        'container-status',
        'calendar',
        'weather',
        'network',
        'repos',
        'my-repos',
        'open-source',
        'pull-requests',
        'repo-trends',
        'contributions',
        'github-inbox',
        'releases',
        'ci-status',
        'issues',
        'inbox',
        'pihole',
    ];

    /**
     * Widgets created per record: one per media service and one per Netdata server.
     *
     * @var list<string>
     */
    public const PATTERNS = [
        '/^media-\d+$/',
        '/^netdata-\d+$/',
    ];

    public static function isKnown(string $widget): bool
    {
        if (in_array($widget, self::FIXED, true)) {
            return true;
        }

        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $widget)) {
                return true;
            }
        }

        return false;
    }
}
