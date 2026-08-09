<script setup lang="ts">
import { Check, ImageDown } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    qrSvg: string;
    reference: string;
}>();

const qrSaved = ref(false);

function saveQr() {
    const svgUrl = URL.createObjectURL(
        new Blob([props.qrSvg], { type: 'image/svg+xml' }),
    );

    const image = new Image();
    image.onload = () => {
        const canvas = document.createElement('canvas');
        canvas.width = canvas.height = 768;
        const context = canvas.getContext('2d')!;
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        URL.revokeObjectURL(svgUrl);

        canvas.toBlob((blob) => {
            if (!blob) {
                return;
            }

            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `overschrijving-${props.reference}.png`;
            link.click();
            URL.revokeObjectURL(link.href);

            qrSaved.value = true;
            setTimeout(() => (qrSaved.value = false), 3000);
        });
    };
    image.src = svgUrl;
}
</script>

<template>
    <div class="flex flex-col items-center gap-3">
        <div
            v-html="qrSvg"
            class="overflow-hidden rounded-2xl border bg-white [&>svg]:block [&>svg]:h-44 [&>svg]:w-44"
        />
        <p class="hidden text-center sm:block">
            <strong>Scan deze QR-code met je bankapp</strong>
            (open je bankapp en kies scannen of QR-code). De overschrijving
            staat dan volledig klaar: het bedrag, ons rekeningnummer en de
            mededeling zijn al ingevuld. Je hoeft enkel nog te bevestigen.
        </p>
        <p class="text-center sm:hidden">
            <strong>Op je telefoon?</strong> Sla de QR-code op als afbeelding en
            kies die foto in je bankapp bij het scannen. De overschrijving staat
            dan volledig klaar: het bedrag, ons rekeningnummer en de mededeling
            zijn al ingevuld.
        </p>
        <Button
            variant="secondary"
            class="rounded-full font-bold sm:hidden"
            @click="saveQr"
        >
            <Check v-if="qrSaved" class="size-4 text-green-600" />
            <ImageDown v-else class="size-4" />
            {{ qrSaved ? 'Opgeslagen!' : 'QR-code opslaan' }}
        </Button>
        <p class="text-center text-xs text-muted-foreground">
            <slot name="fallback">
                Lukt het niet? Vul de overschrijving dan zelf in met de gegevens
                hieronder.
            </slot>
        </p>
    </div>
</template>
