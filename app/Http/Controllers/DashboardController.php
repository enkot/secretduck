<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function __invoke(string $current_team): RedirectResponse
    {
        return to_route('invitations.index', ['current_team' => $current_team]);
    }
}
