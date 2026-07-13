<?php

namespace App\Enums;

enum InvitationEventType: string
{
    case LinkOpened = 'link_opened';
    case ChallengeStarted = 'challenge_started';
    case ChallengeFailed = 'challenge_failed';
    case ChallengeCompleted = 'challenge_completed';
    case InvitationRevealed = 'invitation_revealed';
    case RsvpSubmitted = 'rsvp_submitted';
    case RsvpUpdated = 'rsvp_updated';
    case CalendarOpened = 'calendar_opened';
    case MapOpened = 'map_opened';
    case WebsiteOpened = 'website_opened';
}
