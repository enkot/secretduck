<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, LockKeyhole } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { edit } from '@/routes/invitations';
import type { HostInvitation } from '@/types';

defineProps<{ invitation: HostInvitation }>();
const page = usePage();
const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');
</script>

<template>
    <Head :title="`Preview ${invitation.title}`" />
    <div class="min-h-full bg-[#f8f5ef] p-5 text-stone-900 lg:p-10">
        <div class="mx-auto max-w-4xl">
            <Link
                :href="
                    edit({
                        current_team: teamSlug,
                        invitation: invitation.publicId,
                    })
                "
                class="mb-8 inline-flex items-center gap-2 text-sm"
                ><ArrowLeft class="size-4" /> Back to builder</Link
            >
            <div class="mb-4 flex items-center gap-2">
                <Badge>Host-only preview</Badge
                ><span class="flex items-center gap-1 text-xs text-stone-500"
                    ><LockKeyhole class="size-3" /> Private configuration
                    visible only here</span
                >
            </div>
            <article class="overflow-hidden rounded-[2rem] bg-white shadow-xl">
                <div class="bg-stone-900 px-8 py-20 text-center text-white">
                    <p class="text-sm tracking-[0.2em] uppercase">
                        {{
                            invitation.teaserText || 'Your teaser appears here'
                        }}
                    </p>
                    <h1 class="mt-6 font-serif text-5xl">
                        {{
                            invitation.challenge
                                ? `${invitation.challenge.type} challenge`
                                : 'Choose a challenge'
                        }}
                    </h1>
                </div>
                <div class="space-y-5 p-8 text-center sm:p-14">
                    <p class="text-sm tracking-[0.2em] text-rose-700 uppercase">
                        {{ invitation.revealHeading || 'You are invited' }}
                    </p>
                    <h2 class="font-serif text-5xl">{{ invitation.title }}</h2>
                    <p class="text-lg text-stone-600">
                        Hosted by {{ invitation.hostNames || 'your hosts' }}
                    </p>
                    <p>
                        {{
                            invitation.description ||
                            'Your invitation description will be revealed here.'
                        }}
                    </p>
                    <div class="mx-auto max-w-md rounded-2xl bg-stone-100 p-5">
                        <p>
                            {{ invitation.startsAtLabel ?? 'Date not set' }}
                        </p>
                        <p>
                            {{ invitation.venueName }} {{ invitation.address }}
                        </p>
                    </div>
                </div>
            </article>
        </div>
    </div>
</template>
