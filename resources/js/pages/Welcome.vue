<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Eye, Link as LinkIcon, Lock, Smartphone } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { login, register } from '@/routes';
import { index as invitationsIndex } from '@/routes/invitations';

const page = usePage();

const invitationsUrl = computed(() =>
    page.props.currentTeam
        ? invitationsIndex(page.props.currentTeam.slug)
        : '/',
);

const createUrl = computed(() =>
    page.props.auth.user ? invitationsUrl.value : register(),
);

/* The teaser lock: a 4x4 latin square with four squares knocked out.
   Tapping a blank cycles it 1..4; filling all four correctly opens the seal. */
const solution = [1, 2, 3, 4, 3, 4, 1, 2, 2, 1, 4, 3, 4, 3, 2, 1];
const blanks = [1, 6, 8, 15];

const values = ref<Record<number, number>>({});

const solved = computed(() =>
    blanks.every((i) => values.value[i] === solution[i]),
);
const filledCount = computed(
    () => blanks.filter((i) => values.value[i]).length,
);
const allFilled = computed(() => filledCount.value === blanks.length);

const cells = computed(() =>
    solution.map((answer, i) => {
        const isBlank = blanks.includes(i);
        const value = isBlank ? (values.value[i] ?? null) : answer;
        const isWrong = isBlank && allFilled.value && value !== answer;

        return {
            display: value ? String(value) : '',
            isBlank,
            background: !isBlank
                ? '#f1f0ed'
                : isWrong
                  ? 'rgba(242,120,154,0.22)'
                  : value
                    ? 'rgba(139,125,246,0.16)'
                    : 'rgba(139,125,246,0.09)',
            color: !isBlank ? '#1a1a18' : isWrong ? '#c83b66' : '#6d5be0',
        };
    }),
);

function pick(index: number) {
    if (solved.value || !blanks.includes(index)) {
        return;
    }

    const current = values.value[index] ?? 0;
    values.value[index] = current >= 4 ? 1 : current + 1;
}

function reseal() {
    values.value = {};
}

const steps = [
    {
        number: '01',
        title: 'Write & lock',
        body: 'Title, date, place — then choose the challenge that guards it.',
        image: '/images/duck-challenge.png',
        imageAlt: 'Duck choosing a challenge to seal an invitation',
    },
    {
        number: '02',
        title: 'Drop the link',
        body: 'secretduck.app/i/ABC123 lands in the group chat. Curiosity does the rest.',
        image: '/images/duck-link.png',
        imageAlt: 'Duck sharing a sealed invitation link',
    },
    {
        number: '03',
        title: 'Watch it open',
        body: 'Every solve pings your host view — RSVPs arrive already warmed up.',
        image: '/images/duck-watch.png',
        imageAlt: 'Duck watching guests solve and open invitations',
    },
];

const challengeTypes = [
    { label: 'Sudoku', class: 'bg-[rgba(37,99,255,0.12)] text-[#2563ff]' },
    {
        label: 'Memory match',
        class: 'bg-[rgba(139,125,246,0.16)] text-[#6d5be0]',
    },
    { label: 'Riddle', class: 'bg-[rgba(245,182,62,0.25)] text-[#8a5f04]' },
    {
        label: 'Scratch to reveal',
        class: 'bg-[rgba(242,120,154,0.18)] text-[#c83b66]',
    },
    {
        label: '+ more soon',
        class: 'bg-[#f1f0ed] font-semibold text-[#97968f]',
    },
];

const features = [
    {
        icon: Smartphone,
        title: 'Mobile-first, truly',
        body: 'Built for one thumb on a bus. Desktop works; phones shine.',
    },
    {
        icon: LinkIcon,
        title: 'Nothing to install',
        body: 'Guests never sign up. The link is the whole experience.',
    },
    {
        icon: Eye,
        title: 'Live host view',
        body: "See who unlocked, who's stuck on question 3, who's ghosting.",
    },
    {
        icon: Lock,
        title: 'Spoiler-proof',
        body: 'Details stay sealed until solved — surprise parties survive screenshots.',
    },
];
</script>

<template>
    <Head title="Playful private invitations" />

    <div
        class="relative min-h-screen bg-[#fcfcfa] font-sans text-[#1a1a18] antialiased"
    >
        <!-- decorative party icons scattered behind the page -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 overflow-hidden"
        >
            <svg
                class="absolute top-[9%] left-[3%] size-16 -rotate-12 opacity-8"
                viewBox="0 0 24 24"
                fill="none"
                stroke="#1a1a18"
                stroke-width="1.6"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M20 12v10H4V12" />
                <path d="M2 7h20v5H2z" />
                <path d="M12 22V7" />
                <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z" />
                <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z" />
            </svg>
            <svg
                class="absolute top-[4%] right-[5%] size-14 rotate-10 opacity-7"
                viewBox="0 0 24 24"
                fill="none"
                stroke="#1a1a18"
                stroke-width="1.6"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path
                    d="M12 3a6 7 0 0 1 6 7c0 4-3 7-6 7s-6-3-6-7a6 7 0 0 1 6-7z"
                />
                <path d="M12 17l-1 2h2l-1 2" />
            </svg>
            <svg
                class="absolute top-[20%] left-[46%] size-13 rotate-8 opacity-7"
                viewBox="0 0 24 24"
                fill="none"
                stroke="#b87d05"
                stroke-width="1.6"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path
                    d="M12 3l1.9 5.8L19.7 12l-5.8 1.9L12 19.7l-1.9-5.8L4.3 12l5.8-1.9z"
                />
            </svg>
            <svg
                class="absolute top-[31%] left-[6%] size-15 rotate-6 opacity-8"
                viewBox="0 0 24 24"
                fill="none"
                stroke="#1a1a18"
                stroke-width="1.6"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8" />
                <path
                    d="M4 16s.5-1 2-1 2.5 2 4 2 2.5-2 4-2 2.5 2 4 2 2-1 2-1"
                />
                <path d="M2 21h20" />
                <path d="M7 8v3" />
                <path d="M12 8v3" />
                <path d="M17 8v3" />
                <path d="M7 4h.01" />
                <path d="M12 4h.01" />
                <path d="M17 4h.01" />
            </svg>
            <svg
                class="absolute top-[36%] right-[4%] size-14 -rotate-8 opacity-8"
                viewBox="0 0 24 24"
                fill="none"
                stroke="#1a1a18"
                stroke-width="1.6"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M8 22h8" />
                <path d="M12 15v7" />
                <path
                    d="M12 15a5 5 0 0 0 5-5c0-2-.5-4-2-8H9c-1.5 4-2 6-2 8a5 5 0 0 0 5 5z"
                />
            </svg>
            <svg
                class="absolute top-[52%] left-[4%] size-13 rotate-12 opacity-7"
                viewBox="0 0 24 24"
                fill="none"
                stroke="#6d5be0"
                stroke-width="1.6"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path
                    d="M12 3a6 7 0 0 1 6 7c0 4-3 7-6 7s-6-3-6-7a6 7 0 0 1 6-7z"
                />
                <path d="M12 17l-1 2h2l-1 2" />
            </svg>
            <svg
                class="absolute top-[57%] right-[7%] size-15 -rotate-12 opacity-8"
                viewBox="0 0 24 24"
                fill="none"
                stroke="#1a1a18"
                stroke-width="1.6"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M20 12v10H4V12" />
                <path d="M2 7h20v5H2z" />
                <path d="M12 22V7" />
                <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z" />
                <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z" />
            </svg>
            <svg
                class="absolute top-[72%] left-[8%] size-12 -rotate-6 opacity-7"
                viewBox="0 0 24 24"
                fill="none"
                stroke="#1a1a18"
                stroke-width="1.6"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M8 22h8" />
                <path d="M12 15v7" />
                <path
                    d="M12 15a5 5 0 0 0 5-5c0-2-.5-4-2-8H9c-1.5 4-2 6-2 8a5 5 0 0 0 5 5z"
                />
            </svg>
            <svg
                class="absolute top-[76%] right-[3%] size-14 rotate-9 opacity-8"
                viewBox="0 0 24 24"
                fill="none"
                stroke="#1a1a18"
                stroke-width="1.6"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8" />
                <path
                    d="M4 16s.5-1 2-1 2.5 2 4 2 2.5-2 4-2 2.5 2 4 2 2-1 2-1"
                />
                <path d="M2 21h20" />
                <path d="M7 8v3" />
                <path d="M12 8v3" />
                <path d="M17 8v3" />
            </svg>
            <svg
                class="absolute top-[88%] left-[48%] size-11 -rotate-12 opacity-7"
                viewBox="0 0 24 24"
                fill="none"
                stroke="#c83b66"
                stroke-width="1.6"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path
                    d="M12 3a6 7 0 0 1 6 7c0 4-3 7-6 7s-6-3-6-7a6 7 0 0 1 6-7z"
                />
                <path d="M12 17l-1 2h2l-1 2" />
            </svg>
        </div>

        <div class="relative mx-auto max-w-[1240px]">
            <header
                class="flex h-[76px] items-center justify-between gap-4 px-5 lg:px-11"
            >
                <Link
                    href="/"
                    class="flex items-center gap-2.5 text-[19px] font-bold tracking-[-0.01em]"
                >
                    <AppLogoIcon
                        alt="SecretDuck"
                        class="size-[34px] rounded-full"
                    />
                    SecretDuck
                </Link>

                <nav
                    class="hidden gap-2 rounded-full bg-[#f1f0ed] p-1.5 md:flex"
                    aria-label="Primary navigation"
                >
                    <a
                        href="#top"
                        class="rounded-full bg-white px-4 py-2 text-sm font-semibold shadow-[0_1px_2px_rgba(0,0,0,0.05)]"
                        >Home</a
                    >
                    <a
                        href="#how"
                        class="rounded-full px-4 py-2 text-sm font-medium text-[#5f5e58]"
                        >How it works</a
                    >
                    <a
                        href="#challenges"
                        class="rounded-full px-4 py-2 text-sm font-medium text-[#5f5e58]"
                        >Challenges</a
                    >
                </nav>

                <div class="flex flex-none items-center gap-1">
                    <Link
                        v-if="!page.props.auth.user"
                        :href="login()"
                        class="rounded-full px-3 py-3 text-[15px] font-semibold whitespace-nowrap text-[#5f5e58] sm:px-4"
                        >Log in</Link
                    >
                    <Link
                        :href="createUrl"
                        class="rounded-full bg-brand px-4 py-3 text-[15px] font-bold whitespace-nowrap text-white sm:px-5 lg:px-6"
                    >
                        <template v-if="page.props.auth.user">
                            Invitations
                        </template>
                        <template v-else>
                            <span class="sm:hidden">Create</span>
                            <span class="hidden sm:inline"
                                >Create an invitation</span
                            >
                        </template>
                    </Link>
                </div>
            </header>

            <section
                id="top"
                class="grid items-center gap-10 px-5 pt-10 pb-16 lg:grid-cols-[1fr_0.92fr] lg:gap-4 lg:px-11 lg:pt-11 lg:pb-16"
            >
                <div>
                    <p
                        class="inline-flex items-center gap-2 rounded-full bg-[rgba(139,125,246,0.14)] px-4 py-2 text-[13px] font-bold text-[#6d5be0]"
                    >
                        <Lock class="size-3.5" :stroke-width="2.4" />
                        The invitation that fights back
                    </p>

                    <h1
                        class="mt-5 text-[44px] leading-[1.02] font-extrabold tracking-[-0.02em] sm:text-[56px] lg:text-[68px]"
                    >
                        Getting in is half the fun.
                    </h1>

                    <p
                        class="mt-5 mb-8 max-w-[440px] text-[18px] leading-[1.7] text-[#5f5e58]"
                    >
                        Host makes an invite. SecretDuck locks it. Guests play a
                        tiny game to open it — right in the browser, right on
                        their phone. There's a sealed one waiting
                        <a href="#challenges" class="font-semibold text-inherit"
                            >just below</a
                        >. ↓
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link
                            :href="createUrl"
                            class="rounded-full bg-brand px-8 py-4 text-base font-bold text-white shadow-glow-brand"
                            >Create an invitation</Link
                        >
                        <span class="text-sm text-[#97968f]"
                            >Free while in beta</span
                        >
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl">
                    <img
                        src="/images/duck-coffee.png"
                        alt="Duck sending a new secret invitation"
                        class="block h-auto w-full"
                    />
                </div>
            </section>
        </div>

        <section
            id="how"
            class="scroll-mt-6 bg-[#0b0a11] px-5 py-16 text-[#f7f8fa] lg:px-11"
        >
            <div class="mx-auto max-w-[1240px]">
                <div
                    class="mb-9 flex flex-col gap-3 sm:flex-row sm:items-baseline sm:justify-between sm:gap-6"
                >
                    <h2
                        class="text-[26px] font-bold tracking-[-0.01em] sm:text-[32px]"
                    >
                        Host seals. Guest solves. Party happens.
                    </h2>
                    <span class="type-eyebrow whitespace-nowrap text-gold"
                        >How it works</span
                    >
                </div>

                <div class="grid gap-5 md:grid-cols-3">
                    <div
                        v-for="step in steps"
                        :key="step.number"
                        class="overflow-hidden p-8 rounded-lg border-2 border-[rgba(247,248,250,0.08)] transition hover:border-[rgba(247,248,250,0.16)]"
                    >
                        <img
                            :src="step.image"
                            :alt="step.imageAlt"
                            loading="lazy"
                            decoding="async"
                            class="block aspect-3/2 w-full object-cover"
                        />
                        <div>
                            <p class="mb-3 font-mono text-[13px] text-gold">
                                {{ step.number }}
                            </p>
                            <h3 class="type-heading-sm mb-1.5">
                                {{ step.title }}
                            </h3>
                            <p
                                class="text-sm leading-[1.6] text-[rgba(247,248,250,0.64)]"
                            >
                                {{ step.body }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="relative mx-auto max-w-[1240px]">
            <section
                id="challenges"
                class="grid scroll-mt-6 items-center gap-12 px-5 py-16 lg:grid-cols-2 lg:px-11"
            >
                <div
                    class="rounded-2xl"
                >
                    <div
                        v-if="!solved"
                        class="flex flex-col items-center gap-4"
                    >
                        <p
                            class="type-micro flex items-center gap-2 text-[#5f5e58] uppercase"
                        >
                            <Lock class="size-3.5" />
                            Sealed with a mini sudoku · {{ filledCount }} of
                            {{ blanks.length }} squares filled
                        </p>

                        <div class="grid grid-cols-4 justify-center gap-2">
                            <button
                                v-for="(cell, index) in cells"
                                :key="index"
                                type="button"
                                :disabled="!cell.isBlank"
                                :aria-label="
                                    cell.isBlank
                                        ? `Blank square, currently ${cell.display || 'empty'}. Tap to cycle 1 to 4.`
                                        : `Given square, ${cell.display}`
                                "
                                class="flex size-14 items-center justify-center rounded-sm text-2xl font-bold transition-colors not-disabled:cursor-pointer sm:size-16"
                                :style="{
                                    background: cell.background,
                                    color: cell.color,
                                }"
                                @click="pick(index)"
                            >
                                {{ cell.display }}
                            </button>
                        </div>

                        <p
                            class="max-w-[300px] text-center text-[13px] text-[#97968f]"
                        >
                            Tap an empty square to cycle 1–4. Every row and
                            column needs all four.
                        </p>
                    </div>

                    <div
                        v-else
                        class="flex animate-in flex-col items-center gap-1.5 text-center duration-300 zoom-in-95"
                    >
                        <img
                            src="/images/envelope-open.png"
                            alt="Envelope opened"
                            class="size-[180px] rounded-sm object-cover"
                        />
                        <p class="type-micro text-[#0f9d63] uppercase">
                            Seal broken
                        </p>
                        <h3 class="type-heading-md mt-0.5">
                            Duck's rooftop party 🎉
                        </h3>
                        <p class="mt-1 mb-2.5 text-sm text-[#5f5e58]">
                            Saturday, July 25 · 8pm · Pier 9 rooftop
                        </p>
                        <button
                            type="button"
                            class="cursor-pointer rounded-full bg-[rgba(24,20,14,0.055)] px-4 py-2.5 text-[13.5px] font-semibold"
                            @click="reseal"
                        >
                            Seal it again
                        </button>
                    </div>
                </div>

                <div>
                    <span class="type-eyebrow text-[#b87d05]"
                        >Challenge types</span
                    >
                    <h2
                        class="mt-3 mb-4 text-[30px] font-bold tracking-[-0.01em] sm:text-[36px]"
                    >
                        One link, many locks.
                    </h2>
                    <p
                        class="mb-6 max-w-[420px] text-base leading-[1.7] text-[#5f5e58]"
                    >
                        Swap the lock without touching the invite. Start with
                        these four — more hatching in the pond.
                    </p>
                    <div class="flex flex-wrap gap-2.5">
                        <span
                            v-for="type in challengeTypes"
                            :key="type.label"
                            class="rounded-md px-5 py-3 text-[15px] font-bold"
                            :class="type.class"
                            >{{ type.label }}</span
                        >
                    </div>
                </div>
            </section>

            <section
                class="mx-5 rounded-2xl bg-primary px-6 py-11 text-white sm:px-12 lg:mx-11"
            >
                <div class="mb-10 max-w-[560px]">
                    <span class="type-eyebrow text-white/70"
                        >Why SecretDuck</span
                    >
                    <h2
                        class="mt-3 text-[28px] font-bold tracking-[-0.01em] sm:text-[34px]"
                    >
                        Serious about being unserious.
                    </h2>
                </div>

                <div class="grid gap-7 md:grid-cols-2 md:gap-x-12">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="flex items-start gap-3.5"
                    >
                        <span
                            class="flex size-10 flex-none items-center justify-center rounded-full bg-white/15"
                        >
                            <component
                                :is="feature.icon"
                                class="size-[19px]"
                                :stroke-width="2"
                            />
                        </span>
                        <div>
                            <h3 class="mb-1 text-[17px] font-bold">
                                {{ feature.title }}
                            </h3>
                            <p
                                class="text-[14.5px] leading-[1.6] text-white/80"
                            >
                                {{ feature.body }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-5 pt-14 pb-12 lg:px-11">
                <figure class="mx-auto max-w-[760px] text-center">
                    <span
                        class="text-[44px] leading-none font-extrabold text-gold"
                        aria-hidden="true"
                        >"</span
                    >
                    <blockquote
                        class="mt-1.5 mb-4 text-[22px] leading-[1.5] font-semibold tracking-[-0.005em] sm:text-[26px]"
                    >
                        We hid our wedding venue behind a couples quiz. Zero
                        spoilers leaked — and the group chat argued about
                        question 3 for a week.
                    </blockquote>
                    <figcaption
                        class="flex items-center justify-center gap-2.5 text-sm text-[#5f5e58]"
                    >
                        <span
                            class="flex size-[34px] items-center justify-center rounded-full bg-[#f2789a] text-[13px] font-bold text-white"
                            aria-hidden="true"
                            >L</span
                        >
                        <span>
                            <strong class="text-[#1a1a18]"
                                >Lena &amp; Tom</strong
                            >
                            · sealed their wedding invite
                        </span>
                    </figcaption>
                </figure>
            </section>
        </div>

        <footer
            class="bg-[#0b0a11] px-5 pt-14 pb-11 text-center text-[#f7f8fa] lg:px-11"
        >
            <div class="mx-auto max-w-[1240px]">
                <AppLogoIcon
                    alt=""
                    class="mx-auto mb-4.5 size-14 rounded-full"
                />
                <h2
                    class="mb-2.5 text-[30px] font-extrabold tracking-[-0.015em] sm:text-[36px]"
                >
                    Seal your first invitation.
                </h2>
                <p class="mb-6 text-base text-[rgba(247,248,250,0.64)]">
                    Two minutes to make. A week of group-chat drama.
                </p>
                <Link
                    :href="createUrl"
                    class="inline-block rounded-full bg-gold px-8 py-4 text-base font-bold text-[#1a1a18]"
                    >Create an invitation — free</Link
                >

                <div
                    class="mt-9 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 border-t border-white/8 pt-6"
                >
                    <span class="text-[13px] text-[rgba(247,248,250,0.38)]"
                        >© 2026 SecretDuck</span
                    >
                    <a
                        href="#top"
                        class="text-[13px] text-[rgba(247,248,250,0.64)]"
                        >Privacy</a
                    >
                    <a
                        href="#top"
                        class="text-[13px] text-[rgba(247,248,250,0.64)]"
                        >Contact</a
                    >
                    <span class="text-[13px] text-[rgba(247,248,250,0.38)]"
                        >Sealed with a quack 🦆</span
                    >
                </div>
            </div>
        </footer>
    </div>
</template>
