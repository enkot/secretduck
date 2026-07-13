<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    configuration: { prompt: string; threshold: number };
    processing: boolean;
}>();
const emit = defineEmits<{
    submit: [payload: { thresholdReached: true }];
    start: [];
}>();
const canvas = ref<HTMLCanvasElement | null>(null);
const progress = ref(0);
const announcedProgress = ref(0);
let context: CanvasRenderingContext2D | null = null;
let drawing = false;
let started = false;
let last = { x: 0, y: 0 };
let frame = 0;

function setup() {
    const element = canvas.value;

    if (!element) {
        return;
    }

    const rect = element.getBoundingClientRect();
    const ratio = window.devicePixelRatio || 1;
    element.width = rect.width * ratio;
    element.height = rect.height * ratio;
    context = element.getContext('2d');

    if (!context) {
        return;
    }

    context.scale(ratio, ratio);
    context.fillStyle = '#27221f';
    context.fillRect(0, 0, rect.width, rect.height);
    context.fillStyle = '#f8f5ef';
    context.textAlign = 'center';
    context.font = '600 18px sans-serif';
    context.fillText(
        props.configuration.prompt,
        rect.width / 2,
        rect.height / 2,
    );
}
function point(event: PointerEvent) {
    const rect = canvas.value!.getBoundingClientRect();

    return { x: event.clientX - rect.left, y: event.clientY - rect.top };
}
function down(event: PointerEvent) {
    drawing = true;
    canvas.value?.setPointerCapture(event.pointerId);
    last = point(event);

    if (!started) {
        started = true;
        emit('start');
    }
}
function move(event: PointerEvent) {
    if (!drawing || !context) {
        return;
    }

    const next = point(event);
    context.globalCompositeOperation = 'destination-out';
    context.lineCap = 'round';
    context.lineJoin = 'round';
    context.lineWidth = 38;
    context.beginPath();
    context.moveTo(last.x, last.y);
    context.lineTo(next.x, next.y);
    context.stroke();
    last = next;

    if (!frame) {
        frame = requestAnimationFrame(measure);
    }
}
function up() {
    drawing = false;
    measure();
}
function measure() {
    frame = 0;

    if (!context || !canvas.value) {
        return;
    }

    const { width, height } = canvas.value;
    const data = context.getImageData(0, 0, width, height).data;
    let clear = 0;
    let samples = 0;

    for (let y = 0; y < height; y += 12) {
        for (let x = 0; x < width; x += 12) {
            samples++;

            if (data[(y * width + x) * 4 + 3] < 32) {
                clear++;
            }
        }
    }

    progress.value = Math.round((clear / samples) * 100);
    const milestone = Math.floor(progress.value / 25) * 25;

    if (milestone > announcedProgress.value) {
        announcedProgress.value = milestone;
    }

    if (progress.value >= props.configuration.threshold) {
        emit('submit', { thresholdReached: true });
    }
}
function reset() {
    progress.value = 0;
    announcedProgress.value = 0;
    setup();
}
onMounted(setup);
onBeforeUnmount(() => cancelAnimationFrame(frame));
</script>

<template>
    <div class="space-y-4">
        <div class="relative overflow-hidden rounded-3xl bg-rose-100 p-3">
            <canvas
                ref="canvas"
                class="h-64 w-full touch-none rounded-2xl"
                aria-label="Scratch surface"
                @pointerdown="down"
                @pointermove="move"
                @pointerup="up"
                @pointercancel="up"
            />
            <p class="sr-only" aria-live="polite">
                {{ announcedProgress }} percent scratched
            </p>
        </div>
        <div class="flex items-center justify-between text-sm text-stone-600">
            <span>{{ progress }}% cleared</span
            ><button type="button" class="underline" @click="reset">
                Reset surface
            </button>
        </div>
        <Button
            class="min-h-12 w-full rounded-full"
            :disabled="processing"
            @click="emit('submit', { thresholdReached: true })"
            >Reveal without scratching</Button
        >
        <p class="text-center text-xs text-stone-500">
            Keyboard-friendly and reduced-motion alternative
        </p>
    </div>
</template>
