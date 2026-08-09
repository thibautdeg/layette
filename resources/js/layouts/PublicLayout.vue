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
    <div class="relative min-h-screen bg-background font-baby">
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div
                class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-primary/10 blur-3xl"
            ></div>
            <div
                class="absolute top-48 -right-28 h-80 w-80 rounded-full bg-accent/70 blur-3xl dark:bg-accent/30"
            ></div>
            <div
                class="absolute bottom-0 left-1/4 h-72 w-72 rounded-full bg-secondary/70 blur-3xl dark:bg-secondary/30"
            ></div>
        </div>

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
