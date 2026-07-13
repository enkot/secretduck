<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Sparkles } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
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
import { index, store } from '@/routes/invitations';

const page = usePage();
const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');
</script>

<template>
    <Head title="New invitation" />
    <div class="mx-auto w-full max-w-3xl p-5 lg:p-10">
        <Link
            :href="index(teamSlug)"
            class="mb-8 inline-flex items-center gap-2 text-sm text-muted-foreground"
            ><ArrowLeft class="size-4" /> Invitations</Link
        >
        <div class="mb-8 flex items-start gap-4">
            <span
                class="grid size-12 place-items-center rounded-2xl bg-rose-100 text-rose-700"
                ><Sparkles class="size-5"
            /></span>
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">
                    Name the occasion
                </h1>
                <p class="mt-2 text-muted-foreground">
                    We’ll create a private draft, then guide you through the
                    details.
                </p>
            </div>
        </div>
        <Card>
            <CardHeader
                ><CardTitle>What are you celebrating?</CardTitle
                ><CardDescription
                    >This remains editable until you are ready to
                    publish.</CardDescription
                ></CardHeader
            >
            <CardContent>
                <Form
                    v-bind="store.form(teamSlug)"
                    v-slot="{ errors, processing }"
                    class="space-y-6"
                >
                    <div class="grid gap-2">
                        <Label for="title">Invitation title</Label
                        ><Input
                            id="title"
                            name="title"
                            required
                            autofocus
                            placeholder="Emma & Daniel's wedding"
                        /><InputError :message="errors.title" />
                    </div>
                    <Button size="lg" :disabled="processing"
                        >Create draft <ArrowRight
                    /></Button>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
