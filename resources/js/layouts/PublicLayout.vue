<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
</script>

<template>
    <div class="min-h-screen bg-background">
        <main class="mx-auto w-full max-w-2xl px-4 py-6 sm:py-10">
            <slot />
        </main>
        <Toaster />
    </div>
</template>
