<?php

namespace App\Services\Git;

use App\Models\GitAccount;
use Throwable;

/**
 * Stores a Git account's contribution calendar (GitHub only for now) for the Contributions widget.
 */
class ContributionsSyncer
{
    public function sync(GitAccount $account): bool
    {
        try {
            $contributions = $account->client()->contributions();
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        if ($contributions === null) {
            return false;
        }

        $account->update(['contributions' => $contributions, 'contributions_synced_at' => now()]);

        return true;
    }
}
