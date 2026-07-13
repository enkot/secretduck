<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
</script>

<template>
    <div class="min-h-screen bg-[#f8f5ef] text-stone-900">
        <header
            class="mx-auto flex max-w-5xl items-center justify-between px-5 py-5"
        >
            <Link
                :href="home()"
                class="flex items-center gap-2 font-semibold tracking-tight"
            >
                <AppLogoIcon alt="" class="size-9 rounded-full" />
                SecretDuck
            </Link>
            <span class="text-xs tracking-[0.2em] text-stone-600 uppercase"
                >A private invitation</span
            >
        </header>
        <main><slot /></main>
    </div>
</template>
