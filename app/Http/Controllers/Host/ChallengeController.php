<?php

namespace App\Http\Controllers\Host;

use App\Actions\Invitations\ConfigureChallenge;
use App\Enums\ChallengeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Host\ConfigureChallengeRequest;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ChallengeController extends Controller
{
    public function update(ConfigureChallengeRequest $request, string $current_team, Invitation $invitation, ConfigureChallenge $action): RedirectResponse
    {
        abort_unless($invitation->team_id === $request->user()->current_team_id, 404);
        Gate::authorize('update', $invitation);
        $validated = $request->validated();
        $action->handle(
            $invitation,
            ChallengeType::from($validated['type']),
            $validated['configuration'],
            (int) ($validated['max_attempts'] ?? config('questinvite.attempt_limit')),
        );

        return back()->with('success', 'Challenge configured.');
    }
}
