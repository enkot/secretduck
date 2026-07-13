<?php

namespace App\Actions\Invitations;

use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\ValueObjects\RecipientToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateRecipient
{
    /** @param array<string, mixed> $attributes
     * @return array{recipient: InvitationRecipient, token: RecipientToken}
     */
    public function handle(Invitation $invitation, array $attributes): array
    {
        return DB::transaction(function () use ($invitation, $attributes): array {
            $token = RecipientToken::generate();
            $recipient = $invitation->recipients()->create([
                ...$attributes,
                'public_id' => (string) Str::ulid(),
                'token_hash' => $token->hash(),
                'token_ciphertext' => $token->value,
            ]);

            return compact('recipient', 'token');
        });
    }
}
