<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Services\InvitationAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InvitationAnalyticsController extends Controller
{
    public function __invoke(Request $request, string $current_team, Invitation $invitation, InvitationAnalytics $analytics): Response
    {
        abort_unless($invitation->team_id === $request->user()->current_team_id, 404);
        Gate::authorize('viewAnalytics', $invitation);

        return Inertia::render('invitations/Analytics', [
            'invitation' => ['publicId' => $invitation->public_id, 'title' => $invitation->title],
            'summary' => $analytics->summary($invitation),
            'recipients' => Inertia::defer(fn () => $analytics->recipients($invitation)),
        ]);
    }
}
