<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { redirect } from '@/routes/oauth';

const page = usePage();

const socialError = computed(
    () => (page.props.errors as Record<string, string> | undefined)?.social,
);
</script>

<template>
    <div class="flex flex-col gap-4">
        <div
            v-if="socialError"
            class="rounded-md bg-destructive/10 p-3 text-sm text-destructive"
        >
            {{ socialError }}
        </div>

        <div class="grid gap-3">
            <Button as="a" variant="outline" :href="redirect.url('google')">
                <svg class="size-4" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        fill="#4285F4"
                        d="M23.52 12.27c0-.85-.08-1.66-.22-2.45H12v4.64h6.46a5.52 5.52 0 0 1-2.4 3.62v3h3.88c2.27-2.09 3.58-5.17 3.58-8.81Z"
                    />
                    <path
                        fill="#34A853"
                        d="M12 24c3.24 0 5.96-1.07 7.94-2.91l-3.88-3.01c-1.07.72-2.45 1.15-4.06 1.15-3.13 0-5.78-2.11-6.72-4.95H1.27v3.11A11.99 11.99 0 0 0 12 24Z"
                    />
                    <path
                        fill="#FBBC05"
                        d="M5.28 14.28a7.2 7.2 0 0 1 0-4.56V6.61H1.27a11.99 11.99 0 0 0 0 10.78l4.01-3.11Z"
                    />
                    <path
                        fill="#EA4335"
                        d="M12 4.77c1.76 0 3.35.61 4.59 1.8l3.44-3.44A11.53 11.53 0 0 0 12 0 11.99 11.99 0 0 0 1.27 6.61l4.01 3.11C6.22 6.88 8.87 4.77 12 4.77Z"
                    />
                </svg>
                Ga verder met Google
            </Button>

            <Button as="a" variant="outline" :href="redirect.url('facebook')">
                <svg class="size-4" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        fill="#1877F2"
                        d="M24 12a12 12 0 1 0-13.88 11.85v-8.38H7.08V12h3.04V9.36c0-3.01 1.8-4.67 4.54-4.67 1.31 0 2.68.23 2.68.23v2.95H15.83c-1.49 0-1.95.92-1.95 1.87V12h3.32l-.53 3.47h-2.79v8.38A12 12 0 0 0 24 12Z"
                    />
                </svg>
                Ga verder met Facebook
            </Button>
        </div>

        <div class="flex items-center gap-3">
            <span class="h-px flex-1 bg-border" />
            <span class="text-xs text-muted-foreground uppercase">of</span>
            <span class="h-px flex-1 bg-border" />
        </div>
    </div>
</template>
