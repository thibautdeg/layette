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
        class="public-theme relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-background p-5 font-baby text-foreground md:p-10"
    >
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <span
                class="absolute top-[12%] left-[10%] size-5 rounded-full bg-sun inked"
            ></span>
            <span
                class="absolute top-[20%] right-[12%] h-3 w-7 rotate-30 rounded-md bg-sky inked"
            ></span>
            <span
                class="absolute bottom-[18%] left-[8%] h-3 w-6 -rotate-25 rounded-md bg-blush inked"
            ></span>
            <span
                class="absolute right-[14%] bottom-[12%] size-4 rounded-full bg-mint inked"
            ></span>
        </div>
        <div class="relative w-full max-w-sm">
            <div class="flex flex-col gap-6">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="font-display text-[2.5rem] leading-none font-bold tracking-tight"
                    >
                        layette
                        <span class="sr-only">— {{ title }}</span>
                    </Link>
                </div>
                <div
                    class="flex flex-col gap-6 rounded-[28px] bg-white p-6 chunky-lg"
                >
                    <div class="space-y-1.5 text-center">
                        <h1 class="font-display text-2xl font-bold">
                            {{ title }}
                        </h1>
                        <p
                            v-if="description"
                            class="text-sm font-semibold text-bark"
                        >
                            {{ description }}
                        </p>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
