<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink, Gift } from '@lucide/vue';
import { computed, ref } from 'vue';
import GiftContributionController from '@/actions/App/Http/Controllers/GiftContributionController';
import GiftListController from '@/actions/App/Http/Controllers/GiftListController';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { centsToEuroInput, euroInputToCents, formatEuro } from '@/lib/money';
import { percentage } from '@/lib/playful';

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
    shop_url: string | null;
    pledged: number;
    remaining: number;
    contributor_count: number;
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

const progress = computed(() =>
    percentage(props.gift.pledged, props.gift.price),
);

const submitLabel = computed(() => {
    if (isPurchase.value) {
        return 'Reserveer dit cadeau';
    }

    return selectedAmountCents.value > 0
        ? `Leg ${formatEuro(selectedAmountCents.value)} in de pot`
        : 'Bevestig mijn bijdrage';
});

const amountButtonClass = (active: boolean) =>
    active ? 'bg-ink text-cream' : 'bg-cream text-ink hover:bg-white';

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

    <div class="flex flex-col gap-5">
        <div class="flex items-center justify-between gap-3">
            <Link
                :href="GiftListController.view({ giftList: giftList.slug })"
                aria-label="Terug naar de lijst"
                class="flex size-11 shrink-0 pressable items-center justify-center rounded-[14px] bg-white chunky-sm"
            >
                <ArrowLeft class="size-5" :stroke-width="2.6" />
            </Link>
            <span class="font-display text-lg font-semibold">Cadeautje</span>
            <span class="size-11 shrink-0" aria-hidden="true"></span>
        </div>

        <div
            class="relative flex h-60 items-center justify-center rounded-[28px] bg-sky chunky-lg sm:h-72"
        >
            <span
                class="absolute top-4 left-4 size-5.5 rounded-full bg-sun inked"
                aria-hidden="true"
            ></span>
            <span
                class="absolute right-6 bottom-7 size-3.5 rounded-full bg-tangerine inked"
                aria-hidden="true"
            ></span>
            <span
                class="absolute top-10 right-12 size-2.5 rounded-full bg-white inked"
                aria-hidden="true"
            ></span>

            <img
                v-if="gift.image_url"
                :src="gift.image_url"
                alt=""
                class="h-[78%] max-w-[78%] rounded-3xl bg-white object-cover inked"
            />
            <Gift
                v-else
                class="size-36"
                :stroke-width="1.4"
                aria-hidden="true"
            />

            <span
                class="absolute -bottom-4 left-5 rounded-full px-3.5 py-1 font-display text-[15px] font-semibold inked"
                :class="gift.is_available ? 'bg-sun' : 'bg-ink text-cream'"
            >
                {{
                    !gift.is_available
                        ? 'Al geregeld'
                        : gift.pledged > 0
                          ? 'Er wordt al samen gegeven'
                          : 'Nog helemaal vrij'
                }}
            </span>
        </div>

        <div class="mt-3 flex flex-col gap-1.5">
            <h1
                class="font-display text-[2rem] leading-[1.05] font-bold tracking-tight break-words"
            >
                {{ gift.title }}
            </h1>
            <p class="text-lg font-extrabold">{{ formatEuro(gift.price) }}</p>
            <p
                v-if="gift.description"
                class="leading-snug font-semibold whitespace-pre-line"
            >
                {{ gift.description }}
            </p>
            <a
                v-if="gift.shop_url"
                :href="gift.shop_url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 self-start text-sm font-extrabold underline decoration-2 underline-offset-4"
            >
                Bekijk in de webshop
                <ExternalLink class="size-3.5" />
            </a>
        </div>

        <div
            v-if="!gift.is_available"
            class="rounded-3xl border-dashed bg-white p-5 text-center font-bold inked"
        >
            Dit cadeau is niet meer beschikbaar.
            <Link
                :href="GiftListController.view({ giftList: giftList.slug })"
                class="mt-2 block underline decoration-2 underline-offset-4"
            >
                Kies een ander cadeautje
            </Link>
        </div>

        <form v-else class="flex flex-col gap-5" @submit.prevent="submit">
            <InputError :message="giftError" />

            <div
                v-if="!isPurchase"
                class="flex flex-col gap-3.5 rounded-3xl bg-white p-4.5 chunky"
            >
                <div class="flex items-baseline justify-between gap-3">
                    <span class="font-display text-xl font-semibold">
                        {{
                            gift.allows_partial_contributions
                                ? 'Samen geven'
                                : 'Bijdragen'
                        }}
                    </span>
                    <span class="text-[15px] font-extrabold">
                        {{ formatEuro(gift.pledged) }} /
                        {{ formatEuro(gift.price) }}
                    </span>
                </div>

                <div
                    class="h-4.5 overflow-hidden rounded-full bg-cream inked"
                    role="progressbar"
                    :aria-valuenow="progress"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-label="Hoeveel er al in de pot zit"
                >
                    <div
                        class="h-full bg-tangerine"
                        :class="
                            progress > 0 && progress < 100
                                ? 'border-r-[2.5px] border-ink'
                                : ''
                        "
                        :style="{ width: `${progress}%` }"
                    />
                </div>

                <div
                    v-if="gift.contributor_count > 0"
                    class="flex items-center gap-2.5"
                >
                    <div class="flex" aria-hidden="true">
                        <span
                            v-for="(color, index) in [
                                'bg-sun',
                                'bg-mint',
                                'bg-blush',
                            ].slice(0, Math.min(3, gift.contributor_count))"
                            :key="color"
                            class="size-7.5 rounded-full inked"
                            :class="[color, index > 0 ? '-ml-2' : '']"
                        ></span>
                    </div>
                    <span class="text-sm font-bold">
                        {{
                            gift.contributor_count === 1
                                ? 'Er legde al iemand in'
                                : `${gift.contributor_count} mensen legden al samen`
                        }}
                    </span>
                </div>

                <template v-if="gift.allows_partial_contributions">
                    <Label
                        for="amount"
                        class="mt-0.5 text-[13px] tracking-[0.08em] uppercase"
                    >
                        Hoeveel leg jij in?
                    </Label>

                    <div
                        v-if="quickAmounts.length > 0"
                        class="grid grid-cols-2 gap-2 sm:grid-cols-4"
                    >
                        <button
                            v-for="quickAmount in quickAmounts"
                            :key="quickAmount"
                            type="button"
                            class="h-12 rounded-[14px] font-display text-[17px] font-semibold transition-colors inked"
                            :class="
                                amountButtonClass(
                                    selectedAmountCents === quickAmount,
                                )
                            "
                            :aria-pressed="selectedAmountCents === quickAmount"
                            @click="selectAmount(quickAmount)"
                        >
                            {{ formatEuro(quickAmount) }}
                        </button>
                        <button
                            type="button"
                            class="col-span-2 h-12 rounded-[14px] font-display text-[17px] font-semibold transition-colors inked sm:col-span-4"
                            :class="
                                amountButtonClass(
                                    selectedAmountCents === gift.remaining,
                                )
                            "
                            :aria-pressed="
                                selectedAmountCents === gift.remaining
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
                        required
                        placeholder="Of kies zelf een bedrag"
                    />
                    <p
                        v-if="gift.remaining < gift.price"
                        class="text-sm font-semibold text-bark"
                    >
                        Er is nog {{ formatEuro(gift.remaining) }} nodig — meer
                        dan dat kan je niet geven.
                    </p>
                </template>
                <p v-else class="text-sm font-semibold text-bark">
                    Voor dit cadeau ligt het bedrag vast op
                    {{ formatEuro(gift.price) }}.
                </p>
                <InputError :message="form.errors.amount" />
            </div>

            <div
                v-else
                class="flex flex-col gap-2 rounded-3xl bg-white p-4.5 chunky"
            >
                <span class="font-display text-xl font-semibold">
                    Ik koop hem zelf
                </span>
                <p class="leading-snug font-semibold">
                    Het cadeau wordt voor jou gereserveerd en verdwijnt uit de
                    beschikbare items. Je koopt en geeft het zelf, dus je hoeft
                    niets over te schrijven.
                </p>
            </div>

            <div class="flex flex-col gap-4 rounded-3xl bg-white p-4.5 chunky">
                <div class="grid gap-2">
                    <Label for="together-with"
                        >Ik geef samen met… (optioneel)</Label
                    >
                    <Input
                        id="together-with"
                        v-model="form.together_with"
                        placeholder="Bijvoorbeeld: Marcel, of oma en opa"
                    />
                    <p class="text-sm font-semibold text-bark">
                        Zo staan jullie er allebei bij, ook al doet één iemand
                        de {{ isPurchase ? 'reservatie' : 'overschrijving' }}.
                    </p>
                    <InputError :message="form.errors.together_with" />
                </div>

                <div class="grid gap-2">
                    <Label for="message">Een lief woordje erbij?</Label>
                    <textarea
                        id="message"
                        v-model="form.message"
                        rows="3"
                        maxlength="1000"
                        class="w-full resize-none bg-cream px-3 py-2.5 text-[15px] placeholder:text-bark/70"
                        placeholder="Bijvoorbeeld een gelukwens — enkel de ouders zien dit."
                    ></textarea>
                    <InputError :message="form.errors.message" />
                </div>
            </div>

            <p v-if="user" class="px-1 text-sm font-semibold text-bark">
                Je {{ isPurchase ? 'reserveert' : 'draagt bij' }} als
                <span class="font-extrabold text-ink">{{ user.name }}</span>
                ({{ user.email }}). De bevestiging{{
                    isPurchase ? '' : ' en betaalinstructies'
                }}
                sturen we naar dat adres.
            </p>

            <div class="flex flex-col gap-1">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex h-15 pressable items-center justify-center rounded-[20px] bg-tangerine px-4 font-display text-[19px] font-semibold chunky disabled:opacity-60"
                >
                    {{ submitLabel }}
                </button>
                <button
                    v-if="gift.allows_purchase"
                    type="button"
                    class="py-2.5 text-center text-[15px] font-extrabold underline-offset-4 hover:underline"
                    @click="type = isPurchase ? 'contribution' : 'purchase'"
                >
                    {{
                        isPurchase
                            ? 'Of: toch liever samen geven'
                            : 'Of: ik koop hem helemaal zelf'
                    }}
                </button>
            </div>
        </form>
    </div>
</template>
