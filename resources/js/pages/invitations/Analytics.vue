<script setup lang="ts">
import { Deferred, Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted } from 'vue';
import RsvpResponseDetails from '@/components/RsvpResponseDetails.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { show } from '@/routes/invitations';
import type { HostRsvpResponse } from '@/types';

interface AnalyticsRecipient {
    publicId: string;
    name: string;
    email: string | null;
    openedAt: string | null;
    completedAt: string | null;
    revealedAt: string | null;
    revokedAt: string | null;
    rsvp: HostRsvpResponse | null;
}
defineProps<{
    invitation: { publicId: string; title: string };
    summary: Record<string, number>;
    recipients?: AnalyticsRecipient[];
}>();
const page = usePage();
const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');
let poll: number | undefined;
onMounted(() => {
    poll = window.setInterval(() => {
        if (!document.hidden) {
            router.reload({
                only: ['summary', 'recipients'],
                preserveErrors: true,
            });
        }
    }, 15000);
});
onBeforeUnmount(() => window.clearInterval(poll));
</script>

<template>
    <Head :title="`${invitation.title} analytics`" />
    <div class="mx-auto w-full max-w-6xl space-y-8 p-5 lg:p-8">
        <header>
            <Link
                :href="
                    show({
                        current_team: teamSlug,
                        invitation: invitation.publicId,
                    })
                "
                class="mb-5 inline-flex items-center gap-2 text-sm text-muted-foreground"
                ><ArrowLeft class="size-4" /> Invitation</Link
            >
            <h1 class="text-3xl font-semibold">Guest journey</h1>
            <p class="mt-2 text-muted-foreground">
                {{ invitation.title }} · refreshes while this tab is visible
            </p>
        </header>
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card
                v-for="metric in [
                    ['Active recipients', 'total'],
                    ['Opened', 'opened'],
                    ['Unlocked', 'completed'],
                    ['RSVP rate', 'rsvpRate'],
                ]"
                :key="metric[1]"
                ><CardHeader
                    ><CardTitle class="text-sm text-muted-foreground">{{
                        metric[0]
                    }}</CardTitle></CardHeader
                ><CardContent class="text-3xl font-semibold"
                    >{{ summary[metric[1]] ?? 0
                    }}<span v-if="metric[1] === 'rsvpRate'" class="text-base"
                        >%</span
                    ></CardContent
                ></Card
            >
        </section>
        <Deferred data="recipients"
            ><template #fallback
                ><div class="space-y-3">
                    <Skeleton
                        v-for="n in 5"
                        :key="n"
                        class="h-16 w-full"
                    /></div
            ></template>
            <div class="overflow-hidden rounded-xl border">
                <article
                    v-for="recipient in recipients"
                    :key="recipient.publicId"
                    class="grid gap-4 border-b p-4 last:border-0"
                >
                    <div
                        class="grid gap-2 md:grid-cols-[1fr_auto_auto] md:items-center"
                    >
                        <div>
                            <p class="font-medium">{{ recipient.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ recipient.email || 'No email' }}
                            </p>
                        </div>
                        <Badge variant="secondary">{{
                            recipient.revokedAt
                                ? 'revoked'
                                : recipient.rsvp?.responseLabel ||
                                  (recipient.completedAt
                                      ? 'unlocked'
                                      : recipient.openedAt
                                        ? 'opened'
                                        : 'invited')
                        }}</Badge
                        ><span class="text-sm text-muted-foreground">{{
                            recipient.rsvp
                                ? `${recipient.rsvp.guestCount} guests`
                                : '—'
                        }}</span>
                    </div>
                    <RsvpResponseDetails
                        v-if="recipient.rsvp"
                        :rsvp="recipient.rsvp"
                    />
                </article></div
        ></Deferred>
    </div>
</template>
