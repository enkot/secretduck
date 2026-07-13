<script setup lang="ts">
import { MessageSquareText, Utensils, Users } from '@lucide/vue';
import type { HostRsvpResponse } from '@/types';

defineProps<{
    rsvp: HostRsvpResponse;
}>();

function formatSubmittedAt(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
</script>

<template>
    <div class="grid gap-4 rounded-xl bg-muted/40 p-4">
        <dl class="grid gap-4 text-sm sm:grid-cols-3">
            <div class="grid gap-1">
                <dt class="text-muted-foreground">Respondent</dt>
                <dd class="font-medium">{{ rsvp.respondentName }}</dd>
            </div>
            <div class="grid gap-1">
                <dt class="text-muted-foreground">Party size</dt>
                <dd class="flex items-center gap-2 font-medium">
                    <Users class="size-4 text-muted-foreground" />
                    {{ rsvp.guestCount }}
                </dd>
            </div>
            <div class="grid gap-1">
                <dt class="text-muted-foreground">Last submitted</dt>
                <dd class="font-medium">
                    {{ formatSubmittedAt(rsvp.submittedAt) }}
                </dd>
            </div>
        </dl>

        <div v-if="rsvp.dietaryNotes" class="flex items-start gap-3 text-sm">
            <Utensils class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
            <div class="min-w-0">
                <p class="text-muted-foreground">Dietary notes</p>
                <p class="mt-1 break-words whitespace-pre-wrap">
                    {{ rsvp.dietaryNotes }}
                </p>
            </div>
        </div>

        <div v-if="rsvp.message" class="flex items-start gap-3 text-sm">
            <MessageSquareText
                class="mt-0.5 size-4 shrink-0 text-muted-foreground"
            />
            <div class="min-w-0">
                <p class="text-muted-foreground">Message</p>
                <p class="mt-1 break-words whitespace-pre-wrap">
                    {{ rsvp.message }}
                </p>
            </div>
        </div>

        <p
            v-if="!rsvp.dietaryNotes && !rsvp.message"
            class="text-sm text-muted-foreground"
        >
            No dietary notes or message provided.
        </p>
    </div>
</template>
