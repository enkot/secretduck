<?php

namespace App\Http\Controllers\Guest;

use App\Actions\Guests\GiveSudokuHint;
use App\Actions\Guests\StartChallenge;
use App\Actions\Guests\SubmitChallenge;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\SubmitChallengeRequest;
use App\Http\Requests\Guest\SudokuHintRequest;
use App\Models\Invitation;
use App\Services\GuestAccessResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    public function start(Request $request, Invitation $invitation, GuestAccessResolver $resolver, StartChallenge $action): JsonResponse
    {
        $recipient = $action->handle($resolver->requireActive($request, $invitation));

        return $this->response(['startedAt' => $recipient->challenge_started_at?->toIso8601String()]);
    }

    public function submit(SubmitChallengeRequest $request, Invitation $invitation, GuestAccessResolver $resolver, SubmitChallenge $action): JsonResponse
    {
        $result = $action->handle($resolver->requireActive($request, $invitation), $request->validated());

        return $this->response($result->toArray());
    }

    public function hint(SudokuHintRequest $request, Invitation $invitation, GuestAccessResolver $resolver, GiveSudokuHint $action): JsonResponse
    {
        return $this->response($action->handle($resolver->requireActive($request, $invitation), $request->validated()));
    }

    /** @param array<string, mixed> $data */
    private function response(array $data): JsonResponse
    {
        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }
}
