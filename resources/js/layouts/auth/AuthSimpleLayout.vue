<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 font-baby md:p-10"
    >
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div
                class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-primary/10 blur-3xl"
            ></div>
            <div
                class="absolute -right-28 bottom-10 h-80 w-80 rounded-full bg-secondary/70 blur-3xl dark:bg-secondary/30"
            ></div>
        </div>
        <div class="relative w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-2xl"
                            aria-hidden="true"
                        >
                            🍼
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-extrabold">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
