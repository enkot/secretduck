<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Services\GuestAccessResolver;
use App\Services\GuestStateBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function __invoke(Request $request, Invitation $invitation, GuestAccessResolver $resolver, GuestStateBuilder $builder): Response
    {
        $session = $resolver->resolve($request, $invitation, false);

        return Inertia::render('guest/Open', [
            'publicId' => $invitation->public_id,
            'state' => $builder->build($invitation, $session),
        ])->withViewData(['metaRobots' => 'noindex, nofollow, noarchive']);
    }
}
