<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, ref } from 'vue';
import GiftContributionController from '@/actions/App/Http/Controllers/GiftContributionController';
import GiftListController from '@/actions/App/Http/Controllers/GiftListController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { centsToEuroInput, euroInputToCents, formatEuro } from '@/lib/money';

interface GiftListInfo {
    slug: string;
    title: string;
    is_closed: boolean;
}

interface GiftInfo {
    id: number;
    title: string;
    description: string | null;
    price: number;
    image_url: string | null;
    remaining: number;
    allows_partial_contributions: boolean;
    allows_purchase: boolean;
    is_available: boolean;
}

const props = defineProps<{
    giftList: GiftListInfo;
    gift: GiftInfo;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const type = ref<'contribution' | 'purchase'>('contribution');

const quickAmounts = props.gift.allows_partial_contributions
    ? [1000, 2500, 5000, 10000].filter(
          (amount) => amount < props.gift.remaining,
      )
    : [];

const suggestedAmount = props.gift.allows_partial_contributions
    ? (quickAmounts[0] ?? props.gift.remaining)
    : props.gift.price;

const form = useForm({
    amount: centsToEuroInput(suggestedAmount),
    message: '',
    together_with: '',
});

const isPurchase = computed(() => type.value === 'purchase');

const giftError = computed(() => (form.errors as Record<string, string>).gift);

const selectedAmountCents = computed(() => euroInputToCents(form.amount));

function selectAmount(cents: number) {
    form.amount = centsToEuroInput(cents);
}

function submit() {
    form.transform((data) => ({
        type: type.value,
        message: data.message === '' ? null : data.message,
        together_with: data.together_with === '' ? null : data.together_with,
        ...(isPurchase.value ? {} : { amount: euroInputToCents(data.amount) }),
    })).post(
        GiftContributionController.store.url({
            giftList: props.giftList.slug,
            gift: props.gift.id,
        }),
    );
}
</script>

<template>
    <Head :title="`Bijdragen aan ${gift.title}`" />

    <Link
        :href="GiftListController.view({ giftList: giftList.slug })"
        class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-white/80 px-3 py-1.5 text-sm font-bold text-muted-foreground shadow-sm hover:text-foreground"
    >
        <ArrowLeft class="size-4" />
        Terug naar de lijst
    </Link>

    <Card class="rounded-3xl border-2 border-white/90 shadow-md">
        <CardContent class="p-4 sm:p-6">
            <div class="mb-6 flex gap-4">
                <img
                    v-if="gift.image_url"
                    :src="gift.image_url"
                    alt=""
                    class="h-20 w-20 shrink-0 rounded-2xl object-cover ring-2 ring-white"
                />
                <div>
                    <h1 class="font-display text-2xl font-bold">
                        {{ gift.title }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Richtprijs: {{ formatEuro(gift.price) }}
                    </p>
                    <p
                        v-if="
                            gift.allows_partial_contributions &&
                            gift.remaining < gift.price
                        "
                        class="text-sm text-muted-foreground"
                    >
                        Nog {{ formatEuro(gift.remaining) }} nodig
                    </p>
                </div>
            </div>

            <div
                v-if="!gift.is_available"
                class="rounded-md bg-muted p-4 text-sm text-muted-foreground"
            >
                Dit cadeau is niet meer beschikbaar.
            </div>

            <form v-else class="flex flex-col gap-5" @submit.prevent="submit">
                <div v-if="gift.allows_purchase" class="grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        class="rounded-2xl border-2 p-3 text-sm font-bold transition-colors"
                        :class="
                            !isPurchase
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'hover:bg-accent/50'
                        "
                        @click="type = 'contribution'"
                    >
                        🤝 Ik draag bij
                    </button>
                    <button
                        type="button"
                        class="rounded-2xl border-2 p-3 text-sm font-bold transition-colors"
                        :class="
                            isPurchase
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'hover:bg-accent/50'
                        "
                        @click="type = 'purchase'"
                    >
                        🛍️ Ik koop dit zelf
                    </button>
                </div>

                <InputError :message="giftError" />

                <p v-if="isPurchase" class="text-sm text-muted-foreground">
                    Het cadeau wordt voor jou gereserveerd en verdwijnt uit de
                    beschikbare items. Je koopt en geeft het zelf, dus je hoeft
                    niets over te schrijven.
                </p>

                <div v-if="!isPurchase" class="grid gap-2">
                    <Label for="amount">Bedrag (euro)</Label>

                    <div
                        v-if="quickAmounts.length > 0"
                        class="flex flex-wrap gap-2"
                    >
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
                        <button
                            type="button"
                            class="rounded-full border-2 px-4 py-1.5 text-sm font-bold transition-colors"
                            :class="
                                selectedAmountCents === gift.remaining
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-border bg-white/80 shadow-sm hover:bg-accent/40'
                            "
                            @click="selectAmount(gift.remaining)"
                        >
                            De rest ({{ formatEuro(gift.remaining) }})
                        </button>
                    </div>

                    <Input
                        id="amount"
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="1"
                        :max="gift.remaining / 100"
                        inputmode="decimal"
                        :disabled="!gift.allows_partial_contributions"
                        required
                        placeholder="Of kies zelf een bedrag"
                    />
                    <p
                        v-if="!gift.allows_partial_contributions"
                        class="text-sm text-muted-foreground"
                    >
                        Voor dit cadeau ligt het bedrag vast.
                    </p>
                    <p
                        v-else-if="gift.remaining < gift.price"
                        class="text-sm text-muted-foreground"
                    >
                        Er is nog {{ formatEuro(gift.remaining) }} nodig — meer
                        dan dat kan je niet geven.
                    </p>
                    <InputError :message="form.errors.amount" />
                </div>

                <p
                    v-if="user"
                    class="rounded-md bg-muted p-3 text-sm text-muted-foreground"
                >
                    Je {{ isPurchase ? 'reserveert' : 'draagt bij' }} als
                    <span class="font-medium text-foreground">{{
                        user.name
                    }}</span>
                    ({{ user.email }}). De bevestiging{{
                        isPurchase ? '' : ' en betaalinstructies'
                    }}
                    sturen we naar dat adres.
                </p>

                <div class="grid gap-2">
                    <Label for="together-with"
                        >Ik geef samen met… (optioneel)</Label
                    >
                    <Input
                        id="together-with"
                        v-model="form.together_with"
                        placeholder="Bijvoorbeeld: Marcel, of oma en opa"
                    />
                    <p class="text-sm text-muted-foreground">
                        Zo staan jullie er allebei bij, ook al doet één iemand
                        de {{ isPurchase ? 'reservatie' : 'overschrijving' }}.
                    </p>
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

                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-full font-bold"
                >
                    {{
                        isPurchase
                            ? 'Reserveer dit cadeau'
                            : 'Bevestig mijn bijdrage'
                    }}
                </Button>
            </form>
        </CardContent>
    </Card>
</template>
