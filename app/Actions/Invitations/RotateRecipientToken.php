<?php

namespace App\Actions\Invitations;

use App\Models\InvitationRecipient;
use App\ValueObjects\RecipientToken;
use Illuminate\Support\Facades\DB;

final class RotateRecipientToken
{
    public function regenerate(InvitationRecipient $recipient): RecipientToken
    {
        return $this->rotate($recipient, false);
    }

    public function reactivate(InvitationRecipient $recipient): RecipientToken
    {
        return $this->rotate($recipient, true);
    }

    public function revoke(InvitationRecipient $recipient): void
    {
        DB::transaction(function () use ($recipient): void {
            $locked = InvitationRecipient::query()->lockForUpdate()->findOrFail($recipient->id);
            $locked->update(['revoked_at' => now()]);
            $locked->guestSessions()->whereNull('revoked_at')->update(['revoked_at' => now()]);
        });
    }

    private function rotate(InvitationRecipient $recipient, bool $reactivate): RecipientToken
    {
        return DB::transaction(function () use ($recipient, $reactivate): RecipientToken {
            $locked = InvitationRecipient::query()->lockForUpdate()->findOrFail($recipient->id);
            $token = RecipientToken::generate();
            $locked->update([
                'token_hash' => $token->hash(),
                'token_ciphertext' => $token->value,
                'token_version' => $locked->token_version + 1,
                'revoked_at' => $reactivate ? null : $locked->revoked_at,
            ]);
            $locked->guestSessions()->whereNull('revoked_at')->update(['revoked_at' => now()]);

            return $token;
        });
    }
}
