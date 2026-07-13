<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    configuration: {
        givens: (number | null)[];
        hintsRemaining: number;
        label: string;
    };
    processing: boolean;
}>();
const emit = defineEmits<{
    submit: [payload: { grid: (number | null)[] }];
    hint: [payload: { grid: (number | null)[] }];
    start: [];
}>();
const grid = ref<(number | null)[]>(
    props.configuration.givens.map((value) => value),
);
const selected = ref<number | null>(null);
const cells = ref<HTMLInputElement[]>([]);
const editable = computed(() =>
    props.configuration.givens
        .map((value, index) => (value === null ? index : -1))
        .filter((index) => index >= 0),
);
function choose(index: number) {
    if (props.configuration.givens[index] === null) {
        selected.value = index;
    }
}
function put(value: number | null) {
    if (selected.value === null) {
        return;
    }

    grid.value[selected.value] = value;
    emit('start');
}
function reset() {
    grid.value = props.configuration.givens.map((value) => value);
}
function keydown(event: KeyboardEvent, index: number) {
    if (/^[1-4]$/.test(event.key)) {
        grid.value[index] = Number(event.key);
        emit('start');
        event.preventDefault();
    }

    if (event.key === 'Delete' || event.key === 'Backspace') {
        grid.value[index] = null;
        event.preventDefault();
    }

    const delta = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -4, ArrowDown: 4 }[
        event.key
    ];

    if (delta) {
        const next = Math.max(0, Math.min(15, index + delta));
        nextTick(() => cells.value[next]?.focus());
        event.preventDefault();
    }
}
</script>

<template>
    <div class="space-y-5">
        <div>
            <h2 class="font-serif text-3xl">A tiny 4×4 Sudoku</h2>
            <p class="mt-2 text-sm text-stone-600">
                Fill every row, column, and 2×2 box with 1–4.
                {{ configuration.label }}
            </p>
        </div>
        <div
            class="mx-auto grid aspect-square max-w-sm grid-cols-4 overflow-hidden rounded-xl border-2 border-stone-900 bg-white"
            role="grid"
            aria-label="4 by 4 Sudoku puzzle"
        >
            <input
                v-for="(value, index) in grid"
                :key="index"
                :ref="
                    (element) => {
                        if (element) cells[index] = element as HTMLInputElement;
                    }
                "
                :value="value ?? ''"
                inputmode="numeric"
                maxlength="1"
                :readonly="configuration.givens[index] !== null"
                :aria-label="`Row ${Math.floor(index / 4) + 1}, column ${(index % 4) + 1}${configuration.givens[index] !== null ? ', given' : ''}`"
                class="aspect-square min-h-14 border-stone-300 text-center text-2xl font-semibold outline-none read-only:bg-stone-100 read-only:text-stone-500 focus:bg-amber-100 [&:nth-child(-n+12)]:border-b [&:nth-child(2n)]:border-r-2 [&:nth-child(n+5):nth-child(-n+8)]:border-b-2"
                @focus="choose(index)"
                @keydown="keydown($event, index)"
                @input="
                    grid[index] =
                        Number(($event.target as HTMLInputElement).value) ||
                        null;
                    emit('start');
                "
            />
        </div>
        <div class="flex justify-center gap-2" aria-label="Sudoku number pad">
            <Button
                v-for="number in 4"
                :key="number"
                type="button"
                variant="outline"
                size="icon-lg"
                class="min-h-12 min-w-12 text-lg"
                @click="put(number)"
                >{{ number }}</Button
            ><Button
                type="button"
                variant="outline"
                size="icon-lg"
                class="min-h-12 min-w-12"
                aria-label="Clear selected cell"
                @click="put(null)"
                >×</Button
            >
        </div>
        <div class="flex flex-wrap justify-center gap-2">
            <Button type="button" variant="outline" @click="reset"
                >Reset grid</Button
            ><Button
                type="button"
                variant="outline"
                :disabled="configuration.hintsRemaining < 1 || processing"
                @click="emit('hint', { grid })"
                >Hint ({{ configuration.hintsRemaining }})</Button
            ><Button
                type="button"
                :disabled="
                    processing || editable.some((index) => grid[index] === null)
                "
                @click="emit('submit', { grid })"
                >Check puzzle</Button
            >
        </div>
    </div>
</template>
