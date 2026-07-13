export type InvitationStatus =
    'draft' | 'published' | 'paused' | 'archived' | 'expired';
export type ChallengeType = 'scratch' | 'trivia' | 'sudoku';
export type RsvpResponse = 'attending' | 'not_attending' | 'maybe';

export interface HostRsvpResponse {
    respondentName: string;
    response: RsvpResponse;
    responseLabel: string;
    guestCount: number;
    dietaryNotes: string | null;
    message: string | null;
    submittedAt: string;
}

export interface InvitationCard {
    publicId: string;
    title: string | null;
    startsAt: string | null;
    status: InvitationStatus;
    recipientCount: number;
    completedCount: number;
    rsvpCount?: number;
}

export interface RecipientSummary {
    publicId: string;
    name: string;
    email: string | null;
    greeting: string | null;
    maxGuests: number;
    expiresAt: string | null;
    openedAt: string | null;
    completedAt: string | null;
    revokedAt: string | null;
    rsvp: HostRsvpResponse | null;
}

export interface HostInvitation {
    publicId: string;
    status: InvitationStatus;
    title: string | null;
    hostNames: string | null;
    startsAt: string | null;
    timezone: string | null;
    venueName: string | null;
    address: string | null;
    description: string | null;
    dressCode: string | null;
    rsvpDeadlineAt: string | null;
    mapUrl: string | null;
    externalUrl: string | null;
    theme: string;
    accentColor: string | null;
    hasCover: boolean;
    revealHeading: string | null;
    teaserText: string | null;
    successMessage: string | null;
    defaultMaxGuests: number;
    accessExpiresAt: string | null;
    challenge: {
        type: ChallengeType;
        configuration: Record<string, unknown>;
        maxAttempts: number;
    } | null;
    recipients: RecipientSummary[];
}

export interface GuestState {
    availability:
        | 'authorization_required'
        | 'unpublished'
        | 'paused'
        | 'archived'
        | 'expired'
        | 'available';
    recipient?: { name: string; greeting: string | null };
    theme?: {
        key: string;
        accentColor: string | null;
        coverImageUrl: string | null;
    };
    teaserText?: string | null;
    challenge?: {
        type: ChallengeType;
        publicConfiguration: Record<string, unknown>;
        attemptsRemaining: number;
        lockedUntil: string | null;
    };
    unlocked?: boolean;
    rsvpSubmitted?: boolean;
}

export interface RevealPayload {
    heading: string;
    title: string;
    hostNames: string;
    startsAt: string;
    timezone: string;
    venueName: string | null;
    address: string | null;
    description: string | null;
    dressCode: string | null;
    rsvp: {
        currentResponse: {
            respondentName: string;
            response: string;
            guestCount: number;
            dietaryNotes: string | null;
            message: string | null;
        } | null;
        maxGuests: number;
        deadline: string | null;
        canUpdate: boolean;
    };
    actions: {
        calendarUrl: string;
        mapUrl: string | null;
        websiteUrl: string | null;
    };
}
