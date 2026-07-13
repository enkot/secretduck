<script setup lang="ts">
import { Form, Head, Link, router, useHttp, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    Check,
    ChevronLeft,
    ChevronRight,
    Clipboard,
    Eye,
    ImagePlus,
    LockKeyhole,
    Palette,
    Puzzle,
    RefreshCw,
    Send,
    UserPlus,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ChallengeController from '@/actions/App/Http/Controllers/Host/ChallengeController';
import CoverImageController from '@/actions/App/Http/Controllers/Host/CoverImageController';
import InvitationController from '@/actions/App/Http/Controllers/Host/InvitationController';
import InvitationRecipientController from '@/actions/App/Http/Controllers/Host/InvitationRecipientController';
import InvitationStateController from '@/actions/App/Http/Controllers/Host/InvitationStateController';
import RecipientAccessController from '@/actions/App/Http/Controllers/Host/RecipientAccessController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Stepper,
    StepperDescription,
    StepperIndicator,
    StepperItem,
    StepperSeparator,
    StepperTitle,
    StepperTrigger,
} from '@/components/ui/stepper';
import { preview, show } from '@/routes/invitations';
import type { ChallengeType, HostInvitation } from '@/types';

const props = defineProps<{
    invitation: HostInvitation;
    challengeTypes: { value: ChallengeType; label: string }[];
    themes: { value: string; label: string }[];
    sudokuTemplates: { key: string; label: string; difficulty: string }[];
}>();
const page = usePage();
const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const routeArgs = computed(() => ({
    current_team: teamSlug.value,
    invitation: props.invitation.publicId,
}));
const selectedChallenge = ref<ChallengeType>(
    props.invitation.challenge?.type ?? 'trivia',
);
const activeStep = ref(1);
const steps = [
    {
        step: 1,
        title: 'Event',
        description: 'When and where',
        icon: CalendarDays,
    },
    {
        step: 2,
        title: 'Appearance',
        description: 'Theme and cover',
        icon: Palette,
    },
    {
        step: 3,
        title: 'Challenge',
        description: 'The playful gate',
        icon: Puzzle,
    },
    {
        step: 4,
        title: 'Recipients',
        description: 'Private links',
        icon: Users,
    },
    {
        step: 5,
        title: 'Review',
        description: 'Preview and publish',
        icon: Eye,
    },
] as const;
const recipientHttp = useHttp<
    {
        name: string;
        max_guests: number;
    },
    { url: string }
>({
    name: '',
    max_guests: props.invitation.defaultMaxGuests,
});
const accessHttp = useHttp<
    Record<string, never>,
    { url?: string; revoked?: boolean }
>({});
const copied = ref<string | null>(null);
const triviaOptions = computed(
    () =>
        (props.invitation.challenge?.configuration.options ?? []) as {
            label: string;
        }[],
);
const activeRecipientCount = computed(
    () =>
        props.invitation.recipients.filter(
            (recipient) => recipient.revokedAt === null,
        ).length,
);
const coreReadiness = computed(() => [
    { label: 'Event details', complete: Boolean(props.invitation.startsAt) },
    { label: 'Teaser copy', complete: Boolean(props.invitation.teaserText) },
    { label: 'Challenge', complete: props.invitation.challenge !== null },
    { label: 'Active recipient', complete: activeRecipientCount.value > 0 },
]);
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

function triviaOptionLabel(index: number): string {
    return triviaOptions.value[index]?.label ?? '';
}

function publishFieldLabel(field: string): string {
    return publishFieldLabels[field] ?? field.replaceAll('_', ' ');
}

async function addRecipient() {
    const response = await recipientHttp.post(
        InvitationRecipientController.store(routeArgs.value).url,
    );
    await navigator.clipboard.writeText(response.url);
    copied.value = 'new';
    recipientHttp.reset();
    router.reload({ only: ['invitation'] });
}

async function recipientAction(
    recipientId: string,
    action: 'link' | 'regenerate' | 'revoke' | 'reactivate',
) {
    const args = { ...routeArgs.value, recipient: recipientId };
    const target = RecipientAccessController[action](args).url;
    const response = await accessHttp.post(target);

    if (response.url) {
        await navigator.clipboard.writeText(response.url);
        copied.value = recipientId;
    }

    router.reload({ only: ['invitation'] });
}
</script>

<template>
    <Head :title="`Edit ${invitation.title || 'invitation'}`" />
    <div class="mx-auto w-full max-w-5xl space-y-8 p-5 pb-20 lg:p-8">
        <header>
            <Link
                :href="show(routeArgs)"
                class="mb-5 inline-flex items-center gap-2 text-sm text-muted-foreground"
                ><ArrowLeft class="size-4" /> Invitation</Link
            >
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-semibold">Build your invitation</h1>
                <Badge>{{ invitation.status }}</Badge>
            </div>
            <p class="mt-2 text-muted-foreground">
                Save each section explicitly, then preview and publish.
            </p>
        </header>

        <Stepper v-model="activeStep" :linear="false" class="flex-col gap-8">
            <nav
                class="overflow-x-auto pb-2"
                aria-label="Invitation builder progress"
            >
                <div class="flex min-w-[46rem] items-start px-1">
                    <StepperItem
                        v-for="item in steps"
                        :key="item.step"
                        v-slot="{ state }"
                        :step="item.step"
                        class="relative flex-1 flex-col gap-0"
                    >
                        <StepperTrigger
                            class="group min-h-20 w-full px-2 py-1 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <StepperIndicator
                                data-test="invitation-step-indicator"
                                class="size-10 border bg-background text-foreground shadow-sm group-data-[state=active]:border-[#fab52b] group-data-[state=active]:bg-[#fab52b] group-data-[state=active]:text-[#1a1a18] group-data-[state=completed]:border-primary group-data-[state=completed]:bg-primary group-data-[state=completed]:text-primary-foreground"
                            >
                                <Check
                                    v-if="state === 'completed'"
                                    class="size-5"
                                />
                                <component
                                    v-else
                                    :is="item.icon"
                                    class="size-5"
                                />
                            </StepperIndicator>
                            <div class="grid gap-0.5">
                                <StepperTitle class="text-sm">{{
                                    item.title
                                }}</StepperTitle>
                                <StepperDescription>{{
                                    item.description
                                }}</StepperDescription>
                            </div>
                        </StepperTrigger>
                        <StepperSeparator
                            v-if="item.step < steps.length"
                            class="absolute top-5 left-[calc(50%+1.5rem)] h-px w-[calc(100%-3rem)] group-data-[state=completed]:bg-primary"
                        />
                    </StepperItem>
                </div>
            </nav>

            <p class="sr-only" aria-live="polite">
                Step {{ activeStep }} of {{ steps.length }}:
                {{ steps[activeStep - 1]?.title }}
            </p>

            <section
                v-show="activeStep === 1"
                aria-labelledby="event-step-title"
            >
                <Form
                    id="event"
                    v-bind="InvitationController.update.form(routeArgs)"
                    v-slot="{ errors, processing }"
                    class="scroll-mt-6"
                >
                    <Card
                        ><CardHeader
                            ><CardTitle id="event-step-title"
                                >Event details</CardTitle
                            ><CardDescription
                                >Only the essential details guests need to
                                know.</CardDescription
                            ></CardHeader
                        ><CardContent class="grid gap-5 md:grid-cols-2">
                            <div class="grid gap-2 md:col-span-2">
                                <Label for="title">Title *</Label
                                ><Input
                                    id="title"
                                    name="title"
                                    :default-value="invitation.title || ''"
                                /><InputError :message="errors.title" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="host_names">Host names *</Label
                                ><Input
                                    id="host_names"
                                    name="host_names"
                                    :default-value="invitation.hostNames || ''"
                                /><InputError :message="errors.host_names" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="starts_at">Starts *</Label
                                ><Input
                                    id="starts_at"
                                    name="starts_at"
                                    type="datetime-local"
                                    :default-value="invitation.startsAt || ''"
                                /><InputError :message="errors.starts_at" />
                            </div>
                            <input
                                type="hidden"
                                name="timezone"
                                :value="
                                    invitation.timezone ||
                                    Intl.DateTimeFormat().resolvedOptions()
                                        .timeZone
                                "
                            />
                            <div class="grid gap-2">
                                <Label for="venue_name">Venue</Label
                                ><Input
                                    id="venue_name"
                                    name="venue_name"
                                    :default-value="invitation.venueName || ''"
                                />
                            </div>
                            <div class="md:col-span-2">
                                <Button :disabled="processing"
                                    >Save event</Button
                                >
                            </div>
                        </CardContent></Card
                    >
                </Form>
            </section>

            <section
                v-show="activeStep === 2"
                class="space-y-6"
                aria-labelledby="appearance-step-title"
            >
                <Form
                    id="appearance"
                    v-bind="InvitationController.update.form(routeArgs)"
                    v-slot="{ processing }"
                    class="scroll-mt-6"
                >
                    <Card
                        ><CardHeader
                            ><CardTitle id="appearance-step-title"
                                >Appearance & reveal</CardTitle
                            ><CardDescription
                                >Choose a look and write the message guests see
                                before the challenge.</CardDescription
                            ></CardHeader
                        ><CardContent class="grid gap-5">
                            <div class="grid gap-2">
                                <Label for="theme">Theme</Label
                                ><select
                                    id="theme"
                                    name="theme"
                                    :value="invitation.theme"
                                    class="h-10 rounded-md border bg-background px-3"
                                >
                                    <option
                                        v-for="theme in themes"
                                        :key="theme.value"
                                        :value="theme.value"
                                    >
                                        {{ theme.label }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="teaser_text">Teaser *</Label
                                ><Input
                                    id="teaser_text"
                                    name="teaser_text"
                                    :default-value="invitation.teaserText || ''"
                                    placeholder="A little quest stands between you and the details…"
                                />
                            </div>
                            <div>
                                <Button :disabled="processing"
                                    >Save appearance</Button
                                >
                            </div>
                        </CardContent></Card
                    >
                </Form>

                <Card>
                    <CardHeader>
                        <CardTitle>Private cover image</CardTitle>
                        <CardDescription>
                            Stored privately and shown only to authorized
                            guests.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-wrap items-end gap-4">
                        <Form
                            v-bind="CoverImageController.store.form(routeArgs)"
                            v-slot="{ errors, processing }"
                            class="flex flex-wrap items-end gap-3"
                        >
                            <div class="grid gap-2">
                                <Label for="cover"
                                    >JPG, PNG, or WebP · max 8 MB</Label
                                >
                                <Input
                                    id="cover"
                                    name="cover"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    required
                                />
                                <InputError :message="errors.cover" />
                            </div>
                            <Button variant="outline" :disabled="processing">
                                <ImagePlus />
                                {{
                                    invitation.hasCover
                                        ? 'Replace cover'
                                        : 'Upload cover'
                                }}
                            </Button>
                        </Form>
                        <Form
                            v-if="invitation.hasCover"
                            v-bind="
                                CoverImageController.destroy.form(routeArgs)
                            "
                            v-slot="{ processing }"
                        >
                            <Button
                                variant="destructive"
                                :disabled="processing"
                            >
                                Remove
                            </Button>
                        </Form>
                    </CardContent>
                </Card>
            </section>

            <section
                v-show="activeStep === 3"
                aria-labelledby="challenge-step-title"
            >
                <Card id="challenge" class="scroll-mt-6"
                    ><CardHeader
                        ><div class="flex items-center justify-between gap-4">
                            <div>
                                <CardTitle id="challenge-step-title"
                                    >Challenge</CardTitle
                                ><CardDescription
                                    >Only safe configuration is sent to guests.
                                    Answers stay encrypted on the
                                    server.</CardDescription
                                >
                            </div>
                            <LockKeyhole
                                v-if="invitation.status !== 'draft'"
                                class="text-muted-foreground"
                            /></div></CardHeader
                    ><CardContent>
                        <div
                            v-if="invitation.status !== 'draft'"
                            class="rounded-xl bg-muted p-5"
                        >
                            <p class="font-medium">
                                {{ invitation.challenge?.type || 'No' }}
                                challenge locked
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Published challenge configuration is immutable.
                            </p>
                        </div>
                        <div v-else class="space-y-5">
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-for="type in challengeTypes"
                                    :key="type.value"
                                    type="button"
                                    :variant="
                                        selectedChallenge === type.value
                                            ? 'default'
                                            : 'outline'
                                    "
                                    @click="selectedChallenge = type.value"
                                    >{{ type.label }}</Button
                                >
                            </div>
                            <Form
                                v-if="selectedChallenge === 'scratch'"
                                v-bind="
                                    ChallengeController.update.form(routeArgs)
                                "
                                v-slot="{ errors, processing }"
                                class="grid gap-4"
                                ><input
                                    type="hidden"
                                    name="type"
                                    value="scratch"
                                /><input
                                    type="hidden"
                                    name="max_attempts"
                                    value="5"
                                /><input
                                    type="hidden"
                                    name="configuration[prompt]"
                                    :value="
                                        String(
                                            invitation.challenge?.configuration
                                                .prompt ||
                                                'Scratch to reveal your invitation',
                                        )
                                    "
                                /><input
                                    type="hidden"
                                    name="configuration[threshold]"
                                    :value="
                                        Number(
                                            invitation.challenge?.configuration
                                                .threshold || 65,
                                        )
                                    "
                                />
                                <InputError
                                    :message="errors['configuration.threshold']"
                                /><Button class="w-fit" :disabled="processing"
                                    >Use scratch challenge</Button
                                ></Form
                            >
                            <Form
                                v-if="selectedChallenge === 'trivia'"
                                v-bind="
                                    ChallengeController.update.form(routeArgs)
                                "
                                v-slot="{ errors, processing }"
                                class="grid gap-4"
                                ><input
                                    type="hidden"
                                    name="type"
                                    value="trivia"
                                /><input
                                    type="hidden"
                                    name="max_attempts"
                                    value="5"
                                />
                                <div class="grid gap-2">
                                    <Label>Question</Label
                                    ><Input
                                        name="configuration[question]"
                                        :default-value="
                                            String(
                                                invitation.challenge
                                                    ?.configuration.question ||
                                                    '',
                                            )
                                        "
                                    />
                                </div>
                                <div
                                    v-for="(id, i) in ['a', 'b', 'c']"
                                    :key="id"
                                    class="grid grid-cols-[auto_1fr] items-center gap-3"
                                >
                                    <input
                                        type="radio"
                                        name="configuration[correctOptionId]"
                                        :value="id"
                                        :checked="
                                            String(
                                                invitation.challenge
                                                    ?.configuration
                                                    .correctOptionId || 'a',
                                            ) === id
                                        "
                                        :aria-label="`Answer ${i + 1} is correct`"
                                    /><input
                                        type="hidden"
                                        :name="`configuration[options][${i}][id]`"
                                        :value="id"
                                    /><Input
                                        :name="`configuration[options][${i}][label]`"
                                        :default-value="triviaOptionLabel(i)"
                                        :placeholder="`Answer ${i + 1}`"
                                    />
                                </div>
                                <input
                                    type="hidden"
                                    name="configuration[successMessage]"
                                    :value="
                                        String(
                                            invitation.challenge?.configuration
                                                .successMessage ||
                                                'That is it!',
                                        )
                                    "
                                /><input
                                    type="hidden"
                                    name="configuration[failureMessage]"
                                    :value="
                                        String(
                                            invitation.challenge?.configuration
                                                .failureMessage ||
                                                'Not quite—try again.',
                                        )
                                    "
                                /><InputError
                                    :message="errors['configuration.options']"
                                /><Button class="w-fit" :disabled="processing"
                                    >Save trivia challenge</Button
                                ></Form
                            >
                            <Form
                                v-if="selectedChallenge === 'sudoku'"
                                v-bind="
                                    ChallengeController.update.form(routeArgs)
                                "
                                v-slot="{ processing }"
                                class="grid gap-4"
                                ><input
                                    type="hidden"
                                    name="type"
                                    value="sudoku"
                                /><input
                                    type="hidden"
                                    name="max_attempts"
                                    value="5"
                                />
                                <div class="grid gap-2">
                                    <Label>4×4 template</Label
                                    ><select
                                        name="configuration[template]"
                                        class="h-10 rounded-md border bg-background px-3"
                                    >
                                        <option
                                            v-for="template in sudokuTemplates"
                                            :key="template.key"
                                            :value="template.key"
                                        >
                                            {{ template.label }} ·
                                            {{ template.difficulty }}
                                        </option>
                                    </select>
                                </div>
                                <input
                                    type="hidden"
                                    name="configuration[allowedHints]"
                                    :value="
                                        Number(
                                            invitation.challenge?.configuration
                                                .allowedHints ?? 1,
                                        )
                                    "
                                />
                                <Button class="w-fit" :disabled="processing"
                                    >Save Sudoku challenge</Button
                                ></Form
                            >
                        </div>
                    </CardContent></Card
                >
            </section>

            <section
                v-show="activeStep === 4"
                aria-labelledby="recipients-step-title"
            >
                <Card id="recipients" class="scroll-mt-6"
                    ><CardHeader
                        ><CardTitle id="recipients-step-title"
                            >Recipients</CardTitle
                        ><CardDescription
                            >Add a guest and copy their private
                            link.</CardDescription
                        ></CardHeader
                    ><CardContent class="space-y-7">
                        <form
                            class="grid gap-3 rounded-xl border p-4"
                            @submit.prevent="addRecipient"
                        >
                            <div class="grid gap-2">
                                <Label for="recipient-name">Name</Label
                                ><Input
                                    id="recipient-name"
                                    v-model="recipientHttp.name"
                                    required
                                />
                            </div>
                            <InputError
                                :message="recipientHttp.errors.name"
                            /><Button
                                class="w-fit"
                                :disabled="recipientHttp.processing"
                                ><UserPlus /> Add & copy link</Button
                            >
                        </form>
                        <div class="divide-y rounded-xl border">
                            <div
                                v-for="recipient in invitation.recipients"
                                :key="recipient.publicId"
                                class="flex flex-col justify-between gap-4 p-4 sm:flex-row sm:items-center"
                            >
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium">
                                            {{ recipient.name }}
                                        </p>
                                        <Badge
                                            v-if="recipient.revokedAt"
                                            variant="destructive"
                                            >Revoked</Badge
                                        ><Badge
                                            v-else-if="recipient.rsvp"
                                            variant="secondary"
                                            >{{
                                                recipient.rsvp.responseLabel
                                            }}</Badge
                                        >
                                    </div>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        Up to {{ recipient.maxGuests }}
                                        {{
                                            recipient.maxGuests === 1
                                                ? 'guest'
                                                : 'guests'
                                        }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        v-if="!recipient.revokedAt"
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        :disabled="accessHttp.processing"
                                        @click="
                                            recipientAction(
                                                recipient.publicId,
                                                'link',
                                            )
                                        "
                                        ><Clipboard />
                                        {{
                                            copied === recipient.publicId
                                                ? 'Copied'
                                                : 'Copy'
                                        }}</Button
                                    ><Button
                                        v-if="!recipient.revokedAt"
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        @click="
                                            recipientAction(
                                                recipient.publicId,
                                                'regenerate',
                                            )
                                        "
                                        ><RefreshCw /> Rotate</Button
                                    ><Button
                                        v-if="!recipient.revokedAt"
                                        type="button"
                                        size="sm"
                                        variant="destructive"
                                        @click="
                                            recipientAction(
                                                recipient.publicId,
                                                'revoke',
                                            )
                                        "
                                        >Revoke</Button
                                    ><Button
                                        v-else
                                        type="button"
                                        size="sm"
                                        @click="
                                            recipientAction(
                                                recipient.publicId,
                                                'reactivate',
                                            )
                                        "
                                        >Reactivate & copy</Button
                                    >
                                </div>
                            </div>
                            <p
                                v-if="!invitation.recipients.length"
                                class="p-5 text-sm text-muted-foreground"
                            >
                                No recipients yet.
                            </p>
                        </div>
                    </CardContent></Card
                >
            </section>

            <section
                v-show="activeStep === 5"
                aria-labelledby="review-step-title"
            >
                <Card>
                    <CardHeader>
                        <CardTitle id="review-step-title"
                            >Review & publish</CardTitle
                        >
                        <CardDescription>
                            Check the essentials, preview the guest experience,
                            then make the invitation available.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="item in coreReadiness"
                                :key="item.label"
                                class="flex items-center gap-3 rounded-xl border p-4"
                            >
                                <span
                                    class="flex size-9 shrink-0 items-center justify-center rounded-full"
                                    :class="
                                        item.complete
                                            ? 'bg-primary text-primary-foreground'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    <Check
                                        v-if="item.complete"
                                        class="size-4"
                                    />
                                    <span v-else aria-hidden="true">—</span>
                                </span>
                                <div>
                                    <p class="font-medium">{{ item.label }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{
                                            item.complete
                                                ? 'Ready'
                                                : 'Needs attention'
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <Button variant="outline" as-child>
                                <Link :href="preview(routeArgs)"
                                    ><Eye /> Preview guest experience</Link
                                >
                            </Button>
                            <Button variant="outline" as-child>
                                <Link :href="show(routeArgs)"
                                    >Invitation overview</Link
                                >
                            </Button>
                        </div>

                        <Form
                            v-if="invitation.status === 'draft'"
                            v-bind="
                                InvitationStateController.publish.form(
                                    routeArgs,
                                )
                            "
                            v-slot="{ errors, processing }"
                            class="grid gap-4 border-t pt-6"
                        >
                            <div
                                v-if="Object.keys(errors).length > 0"
                                role="alert"
                                aria-live="assertive"
                                class="rounded-xl border border-destructive/30 bg-destructive/5 p-4 text-sm"
                            >
                                <p class="font-medium text-destructive">
                                    Complete these items before publishing:
                                </p>
                                <ul class="mt-2 grid gap-1 pl-5">
                                    <li
                                        v-for="(message, field) in errors"
                                        :key="field"
                                        class="list-disc"
                                    >
                                        <span class="font-medium"
                                            >{{
                                                publishFieldLabel(field)
                                            }}:</span
                                        >
                                        {{ message }}
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <Button :disabled="processing">
                                    <Send /> Publish invitation
                                </Button>
                            </div>
                        </Form>

                        <div
                            v-else
                            class="flex items-center gap-3 rounded-xl border border-primary/20 bg-primary/5 p-4"
                        >
                            <Check class="size-5 text-primary" />
                            <div>
                                <p class="font-medium">
                                    Invitation {{ invitation.status }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Challenge settings are locked after
                                    publishing.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <div
                class="sticky bottom-4 z-20 flex items-center justify-between gap-3 rounded-2xl border bg-background/95 p-3 shadow-lg backdrop-blur"
            >
                <Button
                    type="button"
                    variant="outline"
                    :disabled="activeStep === 1"
                    @click="activeStep -= 1"
                >
                    <ChevronLeft /> Back
                </Button>
                <p class="text-center text-sm text-muted-foreground">
                    <span class="font-medium text-foreground">{{
                        steps[activeStep - 1]?.title
                    }}</span>
                    <span class="hidden sm:inline">
                        · Step {{ activeStep }} of {{ steps.length }}</span
                    >
                </p>
                <Button
                    v-if="activeStep < steps.length"
                    type="button"
                    @click="activeStep += 1"
                >
                    Next <ChevronRight />
                </Button>
                <Button v-else type="button" variant="outline" as-child>
                    <Link :href="preview(routeArgs)"><Eye /> Preview</Link>
                </Button>
            </div>
        </Stepper>
    </div>
</template>
