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
</script>

<template>
    <div
        class="relative min-h-screen overflow-x-hidden bg-[linear-gradient(180deg,var(--background)_0%,var(--muted)_58%,var(--background)_100%)] font-baby"
    >
        <div
            class="pointer-events-none fixed inset-0 [background-image:linear-gradient(var(--border)_1px,transparent_1px),linear-gradient(90deg,var(--border)_1px,transparent_1px)] [mask-image:linear-gradient(to_bottom,black,transparent_82%)] [background-size:40px_40px] opacity-40 dark:opacity-20"
        ></div>

        <main
            class="relative mx-auto w-full px-4 py-6 sm:py-10"
            :class="wide ? 'max-w-5xl' : 'max-w-2xl'"
        >
            <slot />
        </main>

        <footer class="relative pb-8 text-center text-sm text-muted-foreground">
            Met liefde gemaakt voor ons kleintje&nbsp;🍼
        </footer>

        <Toaster />
    </div>
</template>
