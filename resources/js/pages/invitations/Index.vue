<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CalendarDays, Plus, Sparkles, Users } from '@lucide/vue';
import { computed } from 'vue';
import PendingInvitationsModal from '@/components/PendingInvitationsModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { create, show } from '@/routes/invitations';
import type { InvitationCard, PendingTeamInvitation } from '@/types';

defineProps<{
    pendingInvitations?: PendingTeamInvitation[];
    invitations: InvitationCard[];
    canManageInvitations: boolean;
}>();
const page = usePage();
const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');
</script>

<template>
    <div class="mx-auto w-full max-w-6xl p-5 lg:p-8">
        <Head title="Invitations" />
        <PendingInvitationsModal
            v-if="pendingInvitations?.length"
            :invitations="pendingInvitations"
        />

        <header class="mb-8 flex items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">
                    Invitations
                </h1>
                <p class="mt-2 text-muted-foreground">
                    Every occasion, quest, and response in one place.
                </p>
            </div>
            <Button
                v-if="canManageInvitations"
                as-child
            >
                <Link :href="create(teamSlug)"> <Plus /> New invitation </Link>
            </Button>
        </header>
        <div
            v-if="invitations.length"
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <Link
                v-for="invitation in invitations"
                :key="invitation.publicId"
                :href="
                    show({
                        current_team: teamSlug,
                        invitation: invitation.publicId,
                    })
                "
                class="group"
            >
                <Card class="h-full transition group-hover:shadow-md"
                    ><CardHeader
                        ><div class="flex items-start justify-between gap-3">
                            <CardTitle>{{
                                invitation.title || 'Untitled invitation'
                            }}</CardTitle
                            ><Badge variant="secondary">{{
                                invitation.status
                            }}</Badge>
                        </div></CardHeader
                    ><CardContent
                        class="space-y-3 text-sm text-muted-foreground"
                        ><p class="flex items-center gap-2">
                            <CalendarDays class="size-4" />{{
                                invitation.startsAt
                                    ? new Date(
                                          invitation.startsAt,
                                      ).toLocaleString()
                                    : 'Date not set'
                            }}
                        </p>
                        <p class="flex items-center gap-2">
                            <Users class="size-4" />{{
                                invitation.completedCount
                            }}
                            / {{ invitation.recipientCount }} unlocked
                        </p></CardContent
                    ></Card
                >
            </Link>
        </div>
        <div
            v-else
            class="grid min-h-96 place-items-center rounded-3xl border border-dashed text-center"
        >
            <div class="max-w-md">
                <Sparkles class="mx-auto size-9 text-rose-600" />
                <h2 class="mt-5 text-xl font-semibold">No invitations yet</h2>
                <p class="mt-2 text-muted-foreground">
                    {{
                        canManageInvitations
                            ? 'Create a private invitation and give guests something delightful to unlock.'
                            : 'Ask a team owner or admin to create an invitation.'
                    }}
                </p>
                <Button
                    v-if="canManageInvitations"
                    as-child
                    class="mt-6 bg-brand-soft text-white hover:bg-brand-soft/90"
                    size="lg"
                >
                    <Link :href="create(teamSlug)">
                        <Plus /> Create an invitation
                    </Link>
                </Button>
            </div>
        </div>
    </div>
</template>
