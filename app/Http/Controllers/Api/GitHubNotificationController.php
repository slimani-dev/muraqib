<?php

namespace App\Http\Controllers\Api;

use App\Enums\GitProvider;
use App\Http\Controllers\Controller;
use App\Models\GitAccount;
use App\Services\Git\GitHubInbox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GitHubNotificationController extends Controller
{
    /**
     * Mark as read / done, unsubscribe, save or unsave one or more notification threads.
     */
    public function update(Request $request, GitAccount $gitAccount, GitHubInbox $inbox): RedirectResponse
    {
        abort_unless($gitAccount->provider === GitProvider::GitHub, 404);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['read', 'done', 'unsubscribe', 'save', 'unsave'])],
            'threads' => ['required', 'array', 'min:1', 'max:50'],
            'threads.*' => ['required', 'string', 'regex:/^\d+$/'],
        ]);

        foreach ($validated['threads'] as $threadId) {
            $inbox->act($gitAccount, $threadId, $validated['action']);
        }

        return back();
    }
}
