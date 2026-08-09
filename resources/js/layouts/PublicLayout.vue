<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';

withDefaults(
    defineProps<{
        wide?: boolean;
    }>(),
    {
        wide: false,
    },
);

const floatingShapes = [
    { emoji: '🎈', class: 'top-28 left-[4%]', rotate: '-6deg', delay: '0s' },
    {
        emoji: '☁️',
        class: 'top-[38%] right-[5%]',
        rotate: '0deg',
        delay: '1.4s',
    },
    {
        emoji: '⭐',
        class: 'top-[78%] left-[6%]',
        rotate: '8deg',
        delay: '2.4s',
    },
];
</script>

<template>
    <div
        class="public-theme relative min-h-screen overflow-x-hidden bg-background font-baby text-foreground"
    >
        <div
            class="fixed inset-x-0 top-0 z-20 h-1.5 bg-[linear-gradient(90deg,hsl(348_75%_76%)_0%,hsl(348_75%_76%)_20%,hsl(44_90%_72%)_20%,hsl(44_90%_72%)_40%,hsl(158_50%_74%)_40%,hsl(158_50%_74%)_60%,hsl(203_75%_76%)_60%,hsl(203_75%_76%)_80%,hsl(280_55%_82%)_80%,hsl(280_55%_82%)_100%)]"
            aria-hidden="true"
        ></div>

        <div
            class="pointer-events-none fixed inset-0 [background-image:radial-gradient(hsl(348_78%_62%/0.06)_2px,transparent_2px),radial-gradient(hsl(203_80%_62%/0.06)_2px,transparent_2px)] [background-size:64px_64px,64px_64px] [background-position:0_0,32px_36px]"
            aria-hidden="true"
        ></div>

        <div
            class="pointer-events-none absolute inset-0 hidden sm:block"
            aria-hidden="true"
        >
            <span
                v-for="shape in floatingShapes"
                :key="shape.emoji"
                class="absolute animate-float-soft text-3xl opacity-20 select-none"
                :class="shape.class"
                :style="{
                    '--float-rotate': shape.rotate,
                    animationDelay: shape.delay,
                }"
            >
                {{ shape.emoji }}
            </span>
        </div>

        <main
            class="relative mx-auto w-full px-4 py-8 sm:py-12"
            :class="wide ? 'max-w-5xl' : 'max-w-2xl'"
        >
            <slot />
        </main>

        <footer
            class="relative pb-8 text-center text-sm font-semibold text-muted-foreground"
        >
            <span aria-hidden="true">🧸</span>
            Met liefde gemaakt voor ons kleintje
            <span aria-hidden="true">🍼</span>
        </footer>

        <Toaster />
    </div>
</template>
