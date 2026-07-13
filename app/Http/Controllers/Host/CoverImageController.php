<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Http\Requests\Host\CoverImageRequest;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class CoverImageController extends Controller
{
    public function store(CoverImageRequest $request, string $current_team, Invitation $invitation): RedirectResponse
    {
        $this->authorizeScoped($request, $invitation);
        $disk = Storage::disk(config('questinvite.cover_disk'));
        $path = $request->file('cover')->store("invitation-covers/{$invitation->public_id}", config('questinvite.cover_disk'));
        $oldPath = $invitation->cover_image_path;
        $invitation->update(['cover_image_path' => $path]);

        if ($oldPath !== null) {
            $disk->delete($oldPath);
        }

        return back()->with('success', 'Cover image saved.');
    }

    public function destroy(Request $request, string $current_team, Invitation $invitation): RedirectResponse
    {
        $this->authorizeScoped($request, $invitation);
        if ($invitation->cover_image_path !== null) {
            Storage::disk(config('questinvite.cover_disk'))->delete($invitation->cover_image_path);
            $invitation->update(['cover_image_path' => null]);
        }

        return back()->with('success', 'Cover image removed.');
    }

    private function authorizeScoped(Request $request, Invitation $invitation): void
    {
        abort_unless($invitation->team_id === $request->user()->current_team_id, 404);
        Gate::authorize('update', $invitation);
    }
}
