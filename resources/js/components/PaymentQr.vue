<script setup lang="ts">
defineProps<{
    qrSvg: string;
}>();
</script>

<template>
    <div class="hidden flex-col items-center gap-3 sm:flex">
        <div
            v-html="qrSvg"
            class="overflow-hidden rounded-2xl border bg-white [&>svg]:block [&>svg]:h-44 [&>svg]:w-44"
        />
        <p class="text-center">
            <strong>Scan deze QR-code met je bankapp</strong>
            (open je bankapp en kies scannen of QR-code). De overschrijving
            staat dan volledig klaar: het bedrag, ons rekeningnummer en de
            mededeling zijn al ingevuld. Je hoeft enkel nog te bevestigen.
        </p>
        <p class="text-center text-xs text-muted-foreground">
            <slot name="fallback">
                Lukt het niet? Vul de overschrijving dan zelf in met de gegevens
                hieronder.
            </slot>
        </p>
    </div>
</template>
