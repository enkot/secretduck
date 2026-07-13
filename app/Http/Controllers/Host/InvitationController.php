<?php

namespace App\Http\Controllers\Host;

use App\Actions\Invitations\CreateInvitation;
use App\Actions\Invitations\UpdateInvitation;
use App\Challenges\SudokuTemplateRegistry;
use App\Data\HostRsvpData;
use App\Enums\ChallengeType;
use App\Enums\TeamPermission;
use App\Enums\Theme;
use App\Http\Controllers\Controller;
use App\Http\Requests\Host\StoreInvitationRequest;
use App\Http\Requests\Host\UpdateInvitationRequest;
use App\Models\Invitation;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Services\InvitationAnalytics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function index(Request $request, InvitationAnalytics $analytics): Response
    {
        $team = $this->team($request);
        $canManageInvitations = $request->user()->hasTeamPermission($team, TeamPermission::ManageEventInvitations);
        $invitations = $canManageInvitations
            ? $team->invitations()
                ->withCount(['recipients', 'recipients as completed_count' => fn ($query) => $query->whereNotNull('challenge_completed_at')])
                ->latest()
                ->get()
                ->map(fn (Invitation $invitation): array => $this->summary($invitation, $analytics))
            : collect();

        return Inertia::render('invitations/Index', [
            'pendingInvitations' => $this->pendingTeamInvitations($request),
            'invitations' => $invitations,
            'canManageInvitations' => $canManageInvitations,
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', [Invitation::class, $this->team($request)]);

        return Inertia::render('invitations/Create');
    }

    public function store(StoreInvitationRequest $request, CreateInvitation $createInvitation): RedirectResponse
    {
        $team = $this->team($request);
        Gate::authorize('create', [Invitation::class, $team]);
        $invitation = $createInvitation->handle($team, $request->validated());

        return to_route('invitations.edit', [$team, $invitation])->with('success', 'Invitation draft created.');
    }

    public function show(Request $request, string $current_team, Invitation $invitation, InvitationAnalytics $analytics): Response
    {
        $this->authorizeInvitation($request, $invitation, 'view');

        return Inertia::render('invitations/Show', [
            'invitation' => $this->hostData($invitation->load(['challenge', 'recipients.rsvp'])),
            'analytics' => $analytics->summary($invitation),
        ]);
    }

    public function edit(Request $request, string $current_team, Invitation $invitation, SudokuTemplateRegistry $templates): Response
    {
        $this->authorizeInvitation($request, $invitation, 'update');

        return Inertia::render('invitations/Edit', [
            'invitation' => $this->hostData($invitation->load(['challenge', 'recipients.rsvp'])),
            'challengeTypes' => collect(ChallengeType::cases())->map(fn (ChallengeType $type) => ['value' => $type->value, 'label' => $type->label()]),
            'themes' => collect(Theme::cases())->map(fn (Theme $theme) => ['value' => $theme->value, 'label' => $theme->label()]),
            'sudokuTemplates' => collect($templates->all())->map(fn (array $template): array => [
                'key' => $template['key'], 'label' => $template['label'], 'difficulty' => $template['difficulty'],
            ]),
        ]);
    }

    public function update(UpdateInvitationRequest $request, string $current_team, Invitation $invitation, UpdateInvitation $updateInvitation): RedirectResponse
    {
        $this->authorizeInvitation($request, $invitation, 'update');
        $updateInvitation->handle($invitation, $request->validated());

        return back()->with('success', 'Invitation details saved.');
    }

    public function preview(Request $request, string $current_team, Invitation $invitation): Response
    {
        $this->authorizeInvitation($request, $invitation, 'view');

        return Inertia::render('invitations/Preview', ['invitation' => $this->hostData($invitation->load('challenge'))]);
    }

    /** @return array<string, mixed> */
    private function summary(Invitation $invitation, InvitationAnalytics $analytics): array
    {
        return [
            'publicId' => $invitation->public_id,
            'title' => $invitation->title,
            'startsAt' => $invitation->starts_at?->toIso8601String(),
            'status' => $invitation->isExpired() ? 'expired' : $invitation->status->value,
            'recipientCount' => $invitation->recipients_count,
            'completedCount' => $invitation->completed_count,
            'rsvpCount' => $analytics->summary($invitation)['total'] - $analytics->summary($invitation)['noResponse'],
        ];
    }

    /**
     * @return Collection<int, array{code: string, inviterName: string, team: array{name: string, slug: string}}>
     */
    private function pendingTeamInvitations(Request $request): Collection
    {
        return TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [Str::lower($request->user()->email)])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation): array => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);
    }

    /** @return array<string, mixed> */
    private function hostData(Invitation $invitation): array
    {
        return [
            'publicId' => $invitation->public_id,
            'status' => $invitation->isExpired() ? 'expired' : $invitation->status->value,
            'title' => $invitation->title,
            'hostNames' => $invitation->host_names,
            'startsAt' => $invitation->starts_at?->format('Y-m-d\TH:i'),
            'timezone' => $invitation->timezone,
            'venueName' => $invitation->venue_name,
            'address' => $invitation->address,
            'description' => $invitation->description,
            'dressCode' => $invitation->dress_code,
            'rsvpDeadlineAt' => $invitation->rsvp_deadline_at?->format('Y-m-d\TH:i'),
            'mapUrl' => $invitation->map_url,
            'externalUrl' => $invitation->external_url,
            'theme' => $invitation->theme->value,
            'accentColor' => $invitation->accent_color,
            'hasCover' => $invitation->cover_image_path !== null,
            'revealHeading' => $invitation->reveal_heading,
            'teaserText' => $invitation->teaser_text,
            'successMessage' => $invitation->success_message,
            'defaultMaxGuests' => $invitation->default_max_guests,
            'accessExpiresAt' => $invitation->access_expires_at?->format('Y-m-d\TH:i'),
            'challenge' => $invitation->challenge === null ? null : [
                'type' => $invitation->challenge->type->value,
                'configuration' => [...$invitation->challenge->public_configuration, ...$invitation->challenge->private_configuration],
                'maxAttempts' => $invitation->challenge->max_attempts,
            ],
            'recipients' => $invitation->relationLoaded('recipients') ? $invitation->recipients->map(fn ($recipient): array => [
                'publicId' => $recipient->public_id,
                'name' => $recipient->name,
                'email' => $recipient->email,
                'greeting' => $recipient->greeting,
                'maxGuests' => $recipient->max_guests,
                'expiresAt' => $recipient->expires_at?->format('Y-m-d\TH:i'),
                'openedAt' => $recipient->opened_at?->toIso8601String(),
                'completedAt' => $recipient->challenge_completed_at?->toIso8601String(),
                'revokedAt' => $recipient->revoked_at?->toIso8601String(),
                'rsvp' => $recipient->rsvp === null ? null : HostRsvpData::from($recipient->rsvp),
            ]) : [],
        ];
    }

    private function team(Request $request): Team
    {
        return $request->user()->currentTeam()->firstOrFail();
    }

    private function authorizeInvitation(Request $request, Invitation $invitation, string $ability): void
    {
        abort_unless($invitation->team_id === $this->team($request)->id, 404);
        Gate::authorize($ability, $invitation);
    }
}
