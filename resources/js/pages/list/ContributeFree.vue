<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import FreeContributionController from '@/actions/App/Http/Controllers/FreeContributionController';
import GiftListController from '@/actions/App/Http/Controllers/GiftListController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { centsToEuroInput, euroInputToCents, formatEuro } from '@/lib/money';

const props = defineProps<{
    giftList: {
        slug: string;
        title: string;
        is_closed: boolean;
    };
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const quickAmounts = [1000, 2500, 5000, 10000];

const form = useForm({
    amount: centsToEuroInput(quickAmounts[0]),
    message: '',
    together_with: '',
});

const selectedAmountCents = computed(() => euroInputToCents(form.amount));

function selectAmount(cents: number) {
    form.amount = centsToEuroInput(cents);
}

const giftError = computed(() => (form.errors as Record<string, string>).gift);

function submit() {
    form.transform((data) => ({
        amount: euroInputToCents(data.amount),
        message: data.message === '' ? null : data.message,
        together_with: data.together_with === '' ? null : data.together_with,
    })).post(
        FreeContributionController.store.url({
            giftList: props.giftList.slug,
        }),
    );
}
</script>

<template>
    <Head title="Vrije bijdrage" />

    <Link
        :href="GiftListController.view({ giftList: giftList.slug })"
        class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-white/80 px-3 py-1.5 text-sm font-bold text-muted-foreground shadow-sm hover:text-foreground"
    >
        <ArrowLeft class="size-4" />
        Terug naar de lijst
    </Link>

    <Card class="rounded-3xl border-2 border-white/90 shadow-md">
        <CardContent class="p-4 sm:p-6">
            <div class="mb-6 flex items-center gap-4">
                <div
                    class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-primary/10 text-3xl shadow-sm"
                    aria-hidden="true"
                >
                    💝
                </div>
                <div>
                    <h1 class="font-display text-2xl font-bold">
                        Vrije bijdrage
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Niet aan één cadeau gebonden — gewoon iets voor de
                        spaarpot van de kleine.
                    </p>
                </div>
            </div>

            <div
                v-if="giftList.is_closed"
                class="rounded-md bg-muted p-4 text-sm text-muted-foreground"
            >
                De lijst is afgesloten, er zijn geen nieuwe bijdragen meer
                mogelijk.
            </div>

            <form v-else class="flex flex-col gap-5" @submit.prevent="submit">
                <InputError :message="giftError" />

                <div class="grid gap-2">
                    <Label for="amount">Bedrag (euro)</Label>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="quickAmount in quickAmounts"
                            :key="quickAmount"
                            type="button"
                            class="rounded-full border-2 px-4 py-1.5 text-sm font-bold transition-colors"
                            :class="
                                selectedAmountCents === quickAmount
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-border bg-white/80 shadow-sm hover:bg-accent/40'
                            "
                            @click="selectAmount(quickAmount)"
                        >
                            {{ formatEuro(quickAmount) }}
                        </button>
                    </div>

                    <Input
                        id="amount"
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="1"
                        inputmode="decimal"
                        required
                        placeholder="Of kies zelf een bedrag"
                    />
                    <InputError :message="form.errors.amount" />
                </div>

                <div class="grid gap-2">
                    <Label for="together-with"
                        >Ik geef samen met… (optioneel)</Label
                    >
                    <Input
                        id="together-with"
                        v-model="form.together_with"
                        placeholder="Bijvoorbeeld: Marcel, of oma en opa"
                    />
                    <InputError :message="form.errors.together_with" />
                </div>

                <div class="grid gap-2">
                    <Label for="message"
                        >Persoonlijk woordje aan ons (optioneel)</Label
                    >
                    <textarea
                        id="message"
                        v-model="form.message"
                        rows="3"
                        maxlength="1000"
                        class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        placeholder="Bijvoorbeeld een gelukwens — enkel wij zien dit."
                    ></textarea>
                    <InputError :message="form.errors.message" />
                </div>

                <p
                    v-if="user"
                    class="rounded-md bg-muted p-3 text-sm text-muted-foreground"
                >
                    Je geeft als
                    <span class="font-medium text-foreground">{{
                        user.name
                    }}</span>
                    ({{ user.email }}). De bevestiging en betaalinstructies
                    sturen we naar dat adres.
                </p>

                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-full font-bold"
                >
                    Bevestig mijn bijdrage
                </Button>
            </form>
        </CardContent>
    </Card>
</template>
