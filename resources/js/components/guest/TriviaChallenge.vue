<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    configuration: {
        question: string;
        options: { id: string; label: string }[];
    };
    processing: boolean;
}>();
const emit = defineEmits<{
    submit: [payload: { optionId: string }];
    start: [];
}>();
const selected = ref('');
function submit() {
    if (selected.value) {
        emit('submit', { optionId: selected.value });
    }
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <fieldset>
            <legend class="mb-5 font-serif text-3xl leading-tight">
                {{ props.configuration.question }}
            </legend>
            <div class="grid gap-3">
                <label
                    v-for="option in props.configuration.options"
                    :key="option.id"
                    class="flex min-h-14 cursor-pointer items-center gap-3 rounded-2xl border border-stone-900/15 bg-white/60 px-5 py-3 transition has-[:checked]:border-stone-900 has-[:checked]:bg-stone-900 has-[:checked]:text-white"
                    ><input
                        v-model="selected"
                        type="radio"
                        name="trivia-answer"
                        :value="option.id"
                        class="size-4"
                        @change="emit('start')"
                    /><span>{{ option.label }}</span></label
                >
            </div>
        </fieldset>
        <Button
            class="min-h-12 w-full rounded-full"
            :disabled="!selected || processing"
            >{{ processing ? 'Checking…' : 'Unlock invitation' }}</Button
        >
    </form>
</template>
