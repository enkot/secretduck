<script setup lang="ts">
import { computed } from 'vue';
import { redirect as redirectToGoogle } from '@/actions/App/Http/Controllers/Auth/GoogleAuthController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';

const props = withDefaults(
    defineProps<{
        error?: string;
        invitation?: string;
        label?: string;
    }>(),
    {
        error: undefined,
        invitation: undefined,
        label: 'Continue with Google',
    },
);

const googleUrl = computed(() =>
    redirectToGoogle.url({
        query: props.invitation ? { invitation: props.invitation } : {},
    }),
);
</script>

<template>
    <div class="grid gap-4">
        <Button as-child variant="outline" class="h-12 w-full text-base">
            <a :href="googleUrl" data-test="google-auth-button">
                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24">
                    <path
                        fill="#4285f4"
                        d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.91h5.38a4.6 4.6 0 0 1-2 3.02v2.54h3.24c1.9-1.75 2.98-4.33 2.98-7.4Z"
                    />
                    <path
                        fill="#34a853"
                        d="M12 22c2.7 0 4.97-.9 6.63-2.43l-3.24-2.54c-.9.6-2.05.96-3.39.96-2.6 0-4.81-1.76-5.6-4.13H3.06v2.62A10 10 0 0 0 12 22Z"
                    />
                    <path
                        fill="#fbbc05"
                        d="M6.4 13.86a6.01 6.01 0 0 1 0-3.72V7.52H3.06a10 10 0 0 0 0 8.96l3.34-2.62Z"
                    />
                    <path
                        fill="#ea4335"
                        d="M12 6.01c1.47 0 2.79.5 3.82 1.5l2.88-2.88A9.65 9.65 0 0 0 12 2a10 10 0 0 0-8.94 5.52l3.34 2.62C7.19 7.77 9.4 6.01 12 6.01Z"
                    />
                </svg>
                {{ label }}
            </a>
        </Button>

        <InputError :message="error" class="text-center" />

    </div>
</template>
