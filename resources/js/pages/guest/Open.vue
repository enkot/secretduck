<script setup lang="ts">
import { Head, useHttp } from '@inertiajs/vue3';
import { CalendarPlus, ExternalLink, MapPin, Sparkles } from '@lucide/vue';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import GuestChallengeController from '@/actions/App/Http/Controllers/Guest/ChallengeController';
import GuestAuthorizationController from '@/actions/App/Http/Controllers/Guest/GuestAuthorizationController';
import RevealController from '@/actions/App/Http/Controllers/Guest/RevealController';
import RsvpController from '@/actions/App/Http/Controllers/Guest/RsvpController';
import ScratchChallenge from '@/components/guest/ScratchChallenge.vue';
import SudokuChallenge from '@/components/guest/SudokuChallenge.vue';
import TriviaChallenge from '@/components/guest/TriviaChallenge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { GuestState, RevealPayload } from '@/types';

interface ChallengeOutcome {
    completed: boolean;
    message: string;
    attemptsRemaining: number | null;
    lockedUntil: string | null;
}
interface ChallengeSubmission {
    optionId?: string;
    thresholdReached?: boolean;
    grid?: (number | null)[];
}
interface HintSubmission {
    grid: (number | null)[];
}
const props = defineProps<{ publicId: string; state: GuestState }>();
const state = ref<GuestState>(props.state);
const payload = ref<RevealPayload | null>(null);
const notice = ref('');
const unavailable = ref(false);
const revealHeading = ref<HTMLElement | null>(null);
const authHttp = useHttp<{ token: string }, GuestState>({ token: '' });
const startHttp = useHttp<Record<string, never>, { startedAt: string }>({});
const challengeHttp = useHttp<ChallengeSubmission, ChallengeOutcome>({});
const hintHttp = useHttp<
    HintSubmission,
    { index: number; value: number; hintsRemaining: number }
>({ grid: [] });
const revealHttp = useHttp<Record<string, never>, RevealPayload>({});
const rsvpHttp = useHttp<
    {
        respondent_name: string;
        attendance: string;
        guest_count: number;
        dietary_notes: string;
        message: string;
    },
    { saved: boolean }
>({
    respondent_name: '',
    attendance: 'attending',
    guest_count: 1,
    dietary_notes: '',
    message: '',
});
const available = computed(() => state.value.availability === 'available');
const challengeConfig = computed(
    () => state.value.challenge?.publicConfiguration ?? {},
);
const triviaConfiguration = computed(
    () =>
        challengeConfig.value as {
            question: string;
            options: { id: string; label: string }[];
        },
);
const sudokuConfiguration = computed(
    () =>
        challengeConfig.value as {
            givens: (number | null)[];
            hintsRemaining: number;
            label: string;
        },
);
const scratchConfiguration = computed(
    () => challengeConfig.value as { prompt: string; threshold: number },
);
const themeClass = computed(
    () =>
        ({
            elegant: 'bg-[#f8f5ef]',
            romantic: 'bg-[#fff1f2]',
            minimal: 'bg-white',
            playful: 'bg-[#fff7d6]',
        })[state.value.theme?.key ?? 'elegant'],
);

watch(
    () => rsvpHttp.attendance,
    (attendance) => {
        if (attendance === 'not_attending') {
            rsvpHttp.guest_count = 0;
        } else if (rsvpHttp.guest_count === 0) {
            rsvpHttp.guest_count = 1;
        }
    },
);

async function authorizeFromFragment() {
    const parameters = new URLSearchParams(window.location.hash.slice(1));
    const token = parameters.get('t');

    if (!token) {
        unavailable.value =
            state.value.availability === 'authorization_required';

        return;
    }

    authHttp.token = token;

    try {
        state.value = await authHttp.post(
            GuestAuthorizationController(props.publicId).url,
        );
        window.history.replaceState(
            {},
            document.title,
            window.location.pathname,
        );

        if (state.value.unlocked) {
            await reveal();
        }
    } catch {
        unavailable.value = true;
        window.history.replaceState(
            {},
            document.title,
            window.location.pathname,
        );
    }
}

async function start() {
    if (!startHttp.wasSuccessful && !startHttp.processing) {
        await startHttp.post(
            GuestChallengeController.start(props.publicId).url,
        );
    }
}
async function submitChallenge(submission: ChallengeSubmission) {
    notice.value = '';
    challengeHttp.transform(() => submission);

    try {
        const result = await challengeHttp.post(
            GuestChallengeController.submit(props.publicId).url,
        );
        notice.value = result.message;

        if (state.value.challenge) {
            state.value.challenge.attemptsRemaining =
                result.attemptsRemaining ??
                state.value.challenge.attemptsRemaining;
            state.value.challenge.lockedUntil = result.lockedUntil;
        }

        if (result.completed) {
            state.value.unlocked = true;
            await reveal();
        }
    } catch {
        notice.value = 'That could not be checked just now. Please try again.';
    }
}
async function hint(submission: HintSubmission) {
    hintHttp.transform(() => submission);

    try {
        const result = await hintHttp.post(
            GuestChallengeController.hint(props.publicId).url,
        );
        const grid = submission.grid as (number | null)[];
        grid[result.index] = result.value;

        if (state.value.challenge) {
            state.value.challenge.publicConfiguration.hintsRemaining =
                result.hintsRemaining;
        }

        notice.value = `Hint placed in row ${Math.floor(result.index / 4) + 1}, column ${(result.index % 4) + 1}.`;
    } catch {
        notice.value = 'No hint is available.';
    }
}
async function reveal() {
    payload.value = await revealHttp.get(RevealController(props.publicId).url);
    const current = payload.value.rsvp.currentResponse;
    rsvpHttp.respondent_name =
        current?.respondentName ?? state.value.recipient?.name ?? '';
    rsvpHttp.attendance = current?.response ?? 'attending';
    rsvpHttp.guest_count = current?.guestCount ?? 1;
    rsvpHttp.dietary_notes = current?.dietaryNotes ?? '';
    rsvpHttp.message = current?.message ?? '';
    await nextTick();
    revealHeading.value?.focus();
}
async function saveRsvp() {
    rsvpHttp.transform((data) => ({ ...data, response: data.attendance }));
    await rsvpHttp.put(RsvpController(props.publicId).url);
    state.value.rsvpSubmitted = true;
    notice.value = 'Your RSVP is saved.';
}

onMounted(async () => {
    if (window.location.hash) {
        await authorizeFromFragment();
    } else if (state.value.unlocked) {
        await reveal();
    } else {
        unavailable.value =
            state.value.availability === 'authorization_required';
    }
});
</script>

<template>
    <Head :title="payload?.title || 'Private invitation'" />
    <div
        :class="[
            'min-h-[calc(100vh-80px)] px-5 pb-16 transition-colors',
            themeClass,
        ]"
        :style="
            state.theme?.accentColor
                ? { '--guest-accent': state.theme.accentColor }
                : undefined
        "
    >
        <div
            v-if="authHttp.processing"
            class="mx-auto grid min-h-[65vh] max-w-xl place-items-center text-center"
        >
            <div>
                <Sparkles class="mx-auto size-8 animate-pulse" />
                <h1 class="mt-5 font-serif text-4xl">
                    Opening your private link…
                </h1>
            </div>
        </div>
        <div
            v-else-if="
                unavailable || state.availability === 'authorization_required'
            "
            class="mx-auto grid min-h-[65vh] max-w-lg place-items-center text-center"
        >
            <div>
                <h1 class="font-serif text-4xl">
                    This invitation link is unavailable
                </h1>
                <p class="mt-4 text-stone-600">
                    Use the original private link from your host. It includes
                    the key needed to open this invitation.
                </p>
            </div>
        </div>
        <div
            v-else-if="!available"
            class="mx-auto grid min-h-[65vh] max-w-lg place-items-center text-center"
        >
            <div>
                <p class="text-sm tracking-[0.2em] uppercase">
                    For {{ state.recipient?.name }}
                </p>
                <h1 class="mt-5 font-serif text-5xl">
                    {{
                        state.availability === 'paused'
                            ? 'The host has paused this invitation'
                            : state.availability === 'expired'
                              ? 'This invitation has expired'
                              : 'This invitation is not available yet'
                    }}
                </h1>
                <p class="mt-4 text-stone-600">
                    Please check with your host if you think this is unexpected.
                </p>
            </div>
        </div>

        <div v-else-if="!payload" class="mx-auto max-w-xl pt-10 sm:pt-16">
            <div
                v-if="state.theme?.coverImageUrl"
                class="mb-8 aspect-[16/9] overflow-hidden rounded-[2rem]"
            >
                <img
                    :src="state.theme.coverImageUrl"
                    alt=""
                    class="h-full w-full object-cover"
                />
            </div>
            <section
                class="rounded-[2rem] bg-white/70 p-6 shadow-xl shadow-stone-900/5 backdrop-blur sm:p-10"
                aria-labelledby="challenge-heading"
            >
                <p
                    class="text-center text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase"
                >
                    {{
                        state.recipient?.greeting ||
                        `For ${state.recipient?.name}`
                    }}
                </p>
                <h1
                    id="challenge-heading"
                    class="mt-5 text-center font-serif text-4xl"
                >
                    {{ state.teaserText }}
                </h1>
                <div class="mt-8">
                    <TriviaChallenge
                        v-if="state.challenge?.type === 'trivia'"
                        :configuration="triviaConfiguration"
                        :processing="challengeHttp.processing"
                        @start="start"
                        @submit="submitChallenge"
                    />
                    <SudokuChallenge
                        v-else-if="state.challenge?.type === 'sudoku'"
                        :configuration="sudokuConfiguration"
                        :processing="
                            challengeHttp.processing || hintHttp.processing
                        "
                        @start="start"
                        @submit="submitChallenge"
                        @hint="hint"
                    />
                    <ScratchChallenge
                        v-else
                        :configuration="scratchConfiguration"
                        :processing="challengeHttp.processing"
                        @start="start"
                        @submit="submitChallenge"
                    />
                </div>
                <p
                    v-if="notice"
                    class="mt-5 text-center text-sm"
                    aria-live="polite"
                >
                    {{ notice }}
                </p>
                <p
                    v-if="state.challenge?.attemptsRemaining !== undefined"
                    class="mt-2 text-center text-xs text-stone-500"
                >
                    {{ state.challenge.attemptsRemaining }} attempts remaining
                </p>
            </section>
        </div>

        <article v-else class="mx-auto max-w-3xl pt-8 sm:pt-16">
            <div
                class="overflow-hidden rounded-[2.5rem] bg-white shadow-2xl shadow-stone-900/10 motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-4"
            >
                <header
                    class="bg-stone-900 px-6 py-16 text-center text-white sm:px-12 sm:py-24"
                >
                    <p class="text-xs tracking-[0.25em] uppercase opacity-70">
                        {{ payload.heading }}
                    </p>
                    <h1
                        ref="revealHeading"
                        tabindex="-1"
                        class="mt-6 font-serif text-5xl leading-none outline-none sm:text-7xl"
                    >
                        {{ payload.title }}
                    </h1>
                    <p class="mt-6 text-lg opacity-75">
                        Hosted by {{ payload.hostNames }}
                    </p>
                </header>
                <div class="space-y-10 p-6 sm:p-12">
                    <section class="grid gap-6 text-center sm:grid-cols-2">
                        <div>
                            <p
                                class="text-xs tracking-[0.2em] text-stone-500 uppercase"
                            >
                                When
                            </p>
                            <p class="mt-2 text-lg font-medium">
                                {{
                                    new Date(payload.startsAt).toLocaleString(
                                        [],
                                        {
                                            dateStyle: 'long',
                                            timeStyle: 'short',
                                            timeZone: payload.timezone,
                                        },
                                    )
                                }}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-xs tracking-[0.2em] text-stone-500 uppercase"
                            >
                                Where
                            </p>
                            <p class="mt-2 text-lg font-medium">
                                {{ payload.venueName }}
                            </p>
                            <p class="text-stone-600">{{ payload.address }}</p>
                        </div>
                    </section>
                    <p
                        v-if="payload.description"
                        class="text-center font-serif text-2xl leading-relaxed whitespace-pre-line"
                    >
                        {{ payload.description }}
                    </p>
                    <p v-if="payload.dressCode" class="text-center text-sm">
                        <span class="font-medium">Dress code:</span>
                        {{ payload.dressCode }}
                    </p>
                    <div class="flex flex-wrap justify-center gap-3">
                        <a
                            :href="payload.actions.calendarUrl"
                            class="inline-flex min-h-11 items-center gap-2 rounded-full border px-5 py-2.5 text-sm font-medium"
                            ><CalendarPlus class="size-4" /> Add to calendar</a
                        ><a
                            v-if="payload.actions.mapUrl"
                            :href="payload.actions.mapUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex min-h-11 items-center gap-2 rounded-full border px-5 py-2.5 text-sm font-medium"
                            ><MapPin class="size-4" /> Map</a
                        ><a
                            v-if="payload.actions.websiteUrl"
                            :href="payload.actions.websiteUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex min-h-11 items-center gap-2 rounded-full border px-5 py-2.5 text-sm font-medium"
                            ><ExternalLink class="size-4" /> Website</a
                        >
                    </div>

                    <form
                        class="space-y-5 border-t pt-10"
                        @submit.prevent="saveRsvp"
                    >
                        <div class="text-center">
                            <h2 class="font-serif text-4xl">
                                Will you join us?
                            </h2>
                            <p
                                v-if="payload.rsvp.deadline"
                                class="mt-2 text-sm text-stone-500"
                            >
                                Reply by
                                {{
                                    new Date(
                                        payload.rsvp.deadline,
                                    ).toLocaleDateString()
                                }}
                            </p>
                        </div>
                        <fieldset class="grid gap-2 sm:grid-cols-3">
                            <legend class="sr-only">RSVP response</legend>
                            <label
                                v-for="choice in [
                                    ['attending', 'Joyfully attending'],
                                    ['maybe', 'Maybe'],
                                    ['not_attending', 'Cannot attend'],
                                ]"
                                :key="choice[0]"
                                class="flex min-h-12 items-center gap-2 rounded-xl border px-4 has-[:checked]:border-stone-900 has-[:checked]:bg-stone-100"
                                ><input
                                    v-model="rsvpHttp.attendance"
                                    type="radio"
                                    :value="choice[0]"
                                />{{ choice[1] }}</label
                            >
                        </fieldset>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="respondent-name">Your name</Label
                                ><Input
                                    id="respondent-name"
                                    v-model="rsvpHttp.respondent_name"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="guest-count">Total attending</Label
                                ><Input
                                    id="guest-count"
                                    v-model="rsvpHttp.guest_count"
                                    type="number"
                                    :min="
                                        rsvpHttp.attendance === 'not_attending'
                                            ? 0
                                            : 1
                                    "
                                    :max="payload.rsvp.maxGuests"
                                    :disabled="
                                        rsvpHttp.attendance === 'not_attending'
                                    "
                                />
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="dietary">Dietary notes</Label
                            ><textarea
                                id="dietary"
                                v-model="rsvpHttp.dietary_notes"
                                class="min-h-24 rounded-xl border bg-transparent p-3"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="message">Message for the hosts</Label
                            ><textarea
                                id="message"
                                v-model="rsvpHttp.message"
                                class="min-h-24 rounded-xl border bg-transparent p-3"
                            />
                        </div>
                        <InputError
                            :message="rsvpHttp.errors.guest_count"
                        /><Button
                            class="min-h-12 w-full rounded-full"
                            :disabled="
                                rsvpHttp.processing || !payload.rsvp.canUpdate
                            "
                            >{{
                                state.rsvpSubmitted
                                    ? 'Update RSVP'
                                    : 'Save RSVP'
                            }}</Button
                        >
                        <p
                            v-if="notice"
                            class="text-center text-sm"
                            aria-live="polite"
                        >
                            {{ notice }}
                        </p>
                    </form>
                </div>
            </div>
        </article>
    </div>
</template>
