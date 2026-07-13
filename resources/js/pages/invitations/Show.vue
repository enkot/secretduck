<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    BarChart3,
    CalendarDays,
    Edit3,
    Eye,
    Pause,
    Play,
    Sparkles,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import InvitationStateController from '@/actions/App/Http/Controllers/Host/InvitationStateController';
import RsvpResponseDetails from '@/components/RsvpResponseDetails.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    analytics as analyticsRoute,
    edit,
    preview,
} from '@/routes/invitations';
import type { HostInvitation } from '@/types';

const props = defineProps<{
    invitation: HostInvitation;
    analytics: Record<string, number>;
}>();
const page = usePage();
const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const routeArgs = computed(() => ({
    current_team: teamSlug.value,
    invitation: props.invitation.publicId,
}));
const responseCount = computed(
    () =>
        props.invitation.recipients.filter(
            (recipient) => recipient.rsvp !== null,
        ).length,
);
const publishFieldLabels: Record<string, string> = {
    title: 'Title',
    host_names: 'Host names',
    starts_at: 'Event start',
    timezone: 'Timezone',
    teaser_text: 'Teaser text',
    challenge: 'Challenge',
    recipients: 'Recipients',
    rsvp_deadline_at: 'RSVP deadline',
    access_expires_at: 'Access expiration',
};

function publishFieldLabel(field: string): string {
    return publishFieldLabels[field] ?? field.replaceAll('_', ' ');
}
</script>

<template>
    <Head :title="invitation.title || 'Invitation'" />
    <div class="mx-auto w-full max-w-6xl space-y-8 p-5 lg:p-8">
        <header
            class="flex flex-col justify-between gap-5 md:flex-row md:items-end"
        >
            <div>
                <div class="mb-3 flex items-center gap-2">
                    <Badge>{{ invitation.status }}</Badge
                    ><span class="text-sm text-muted-foreground"
                        >Private recipient links</span
                    >
                </div>
                <h1 class="text-3xl font-semibold tracking-tight">
                    {{ invitation.title || 'Untitled invitation' }}
                </h1>
                <p class="mt-2 text-muted-foreground">
                    {{ invitation.startsAtLabel ?? 'Event date not set' }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child
                    ><Link :href="preview(routeArgs)"
                        ><Eye /> Preview</Link
                    ></Button
                ><Button variant="outline" as-child
                    ><Link :href="analyticsRoute(routeArgs)"
                        ><BarChart3 /> Analytics</Link
                    ></Button
                ><Button as-child
                    ><Link :href="edit(routeArgs)"><Edit3 /> Edit</Link></Button
                >
            </div>
        </header>

        <section
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
            aria-label="Invitation funnel"
        >
            <Card
                ><CardHeader
                    ><CardTitle class="text-sm text-muted-foreground"
                        >Recipients</CardTitle
                    ></CardHeader
                ><CardContent class="text-3xl font-semibold">{{
                    analytics.total ?? 0
                }}</CardContent></Card
            >
            <Card
                ><CardHeader
                    ><CardTitle class="text-sm text-muted-foreground"
                        >Opened</CardTitle
                    ></CardHeader
                ><CardContent class="text-3xl font-semibold">{{
                    analytics.opened ?? 0
                }}</CardContent></Card
            >
            <Card
                ><CardHeader
                    ><CardTitle class="text-sm text-muted-foreground"
                        >Unlocked</CardTitle
                    ></CardHeader
                ><CardContent class="text-3xl font-semibold">{{
                    analytics.completed ?? 0
                }}</CardContent></Card
            >
            <Card
                ><CardHeader
                    ><CardTitle class="text-sm text-muted-foreground"
                        >Attending</CardTitle
                    ></CardHeader
                ><CardContent class="text-3xl font-semibold">{{
                    analytics.attending ?? 0
                }}</CardContent></Card
            >
        </section>

        <Card
            ><CardHeader><CardTitle>Event readiness</CardTitle></CardHeader
            ><CardContent
                class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4"
                ><p class="flex items-center gap-2">
                    <CalendarDays class="size-4" />
                    {{
                        invitation.startsAt ? 'Date configured' : 'Date needed'
                    }}
                </p>
                <p class="flex items-center gap-2">
                    <Users class="size-4" />
                    {{ invitation.recipients.length }} recipients
                </p>
                <p class="flex items-center gap-2">
                    <Play class="size-4" />
                    {{
                        invitation.challenge
                            ? `${invitation.challenge.type} challenge`
                            : 'Challenge needed'
                    }}
                </p>
                <p class="flex items-center gap-2">
                    <Sparkles class="size-4" />
                    {{
                        invitation.teaserText
                            ? 'Teaser configured'
                            : 'Teaser needed'
                    }}
                </p></CardContent
            ></Card
        >

        <Card>
            <CardHeader>
                <CardTitle>Guest responses</CardTitle>
                <CardDescription>
                    {{ responseCount }} of
                    {{ invitation.recipients.length }} recipients have replied.
                    Responses are read-only.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="invitation.recipients.length"
                    class="divide-y rounded-xl border"
                >
                    <article
                        v-for="recipient in invitation.recipients"
                        :key="recipient.publicId"
                        class="grid gap-4 p-4 sm:p-5"
                    >
                        <div
                            class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"
                        >
                            <div>
                                <p class="font-medium">{{ recipient.name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ recipient.email || 'No email' }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-if="recipient.rsvp"
                                    variant="secondary"
                                >
                                    {{ recipient.rsvp.responseLabel }}
                                </Badge>
                                <Badge
                                    v-if="recipient.revokedAt"
                                    variant="destructive"
                                >
                                    Revoked
                                </Badge>
                            </div>
                        </div>

                        <RsvpResponseDetails
                            v-if="recipient.rsvp"
                            :rsvp="recipient.rsvp"
                        />
                        <p v-else class="text-sm text-muted-foreground">
                            No RSVP submitted yet.
                        </p>
                    </article>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    Add recipients to start collecting responses.
                </p>
            </CardContent>
        </Card>

        <div class="flex flex-wrap gap-3 border-t pt-6">
            <Form
                v-if="invitation.status === 'draft'"
                v-bind="InvitationStateController.publish.form(routeArgs)"
                v-slot="{ errors, processing }"
                class="grid w-full gap-3"
            >
                <div
                    v-if="Object.keys(errors).length > 0"
                    role="alert"
                    aria-live="assertive"
                    class="rounded-xl border border-destructive/30 bg-destructive/5 p-4 text-sm"
                >
                    <p
                        class="flex items-center gap-2 font-medium text-destructive"
                    >
                        <AlertCircle class="size-4" /> Invitation is not ready
                        to publish
                    </p>
                    <ul class="mt-2 grid gap-1 pl-6">
                        <li
                            v-for="(message, field) in errors"
                            :key="field"
                            class="list-disc"
                        >
                            <span class="font-medium"
                                >{{ publishFieldLabel(field) }}:</span
                            >
                            {{ message }}
                        </li>
                    </ul>
                    <Button
                        type="button"
                        variant="outline"
                        class="mt-4"
                        as-child
                    >
                        <Link :href="edit(routeArgs)">Complete invitation</Link>
                    </Button>
                </div>
                <Button type="submit" :disabled="processing"
                    ><Play /> Publish invitation</Button
                >
            </Form>
            <Form
                v-if="invitation.status === 'published'"
                v-bind="InvitationStateController.pause.form(routeArgs)"
                v-slot="{ processing }"
                ><Button variant="outline" :disabled="processing"
                    ><Pause /> Pause access</Button
                ></Form
            >
            <Form
                v-if="invitation.status === 'paused'"
                v-bind="InvitationStateController.resume.form(routeArgs)"
                v-slot="{ processing }"
                ><Button :disabled="processing"><Play /> Resume</Button></Form
            >
            <Form
                v-if="invitation.status !== 'archived'"
                v-bind="InvitationStateController.archive.form(routeArgs)"
                v-slot="{ processing }"
                ><Button variant="destructive" :disabled="processing"
                    >Archive</Button
                ></Form
            >
        </div>
    </div>
</template>
