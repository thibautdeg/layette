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

const suggestedAmount = props.gift.allows_partial_contributions
    ? props.gift.remaining
    : props.gift.price;

const form = useForm({
    amount: centsToEuroInput(suggestedAmount),
    message: '',
});

const isPurchase = computed(() => type.value === 'purchase');

const giftError = computed(() => (form.errors as Record<string, string>).gift);

function submit() {
    form.transform((data) => ({
        type: type.value,
        message: data.message === '' ? null : data.message,
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
        class="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
    >
        <ArrowLeft class="size-4" />
        Terug naar de lijst
    </Link>

    <Card>
        <CardContent class="p-4 sm:p-6">
            <div class="mb-6 flex gap-4">
                <img
                    v-if="gift.image_url"
                    :src="gift.image_url"
                    alt=""
                    class="h-20 w-20 shrink-0 rounded-md object-cover"
                />
                <div>
                    <h1 class="text-xl font-semibold">{{ gift.title }}</h1>
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
                        class="rounded-md border p-3 text-sm font-medium transition-colors"
                        :class="
                            !isPurchase
                                ? 'border-primary bg-primary/5'
                                : 'hover:bg-accent'
                        "
                        @click="type = 'contribution'"
                    >
                        Ik draag bij
                    </button>
                    <button
                        type="button"
                        class="rounded-md border p-3 text-sm font-medium transition-colors"
                        :class="
                            isPurchase
                                ? 'border-primary bg-primary/5'
                                : 'hover:bg-accent'
                        "
                        @click="type = 'purchase'"
                    >
                        Ik koop dit zelf
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
                    <Input
                        id="amount"
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="1"
                        inputmode="decimal"
                        :disabled="!gift.allows_partial_contributions"
                        required
                    />
                    <p
                        v-if="!gift.allows_partial_contributions"
                        class="text-sm text-muted-foreground"
                    >
                        Voor dit cadeau ligt het bedrag vast.
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
                    class="w-full"
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
