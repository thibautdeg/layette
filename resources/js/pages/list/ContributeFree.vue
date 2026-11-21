<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, PiggyBank } from '@lucide/vue';
import { computed } from 'vue';
import FreeContributionController from '@/actions/App/Http/Controllers/FreeContributionController';
import GiftListController from '@/actions/App/Http/Controllers/GiftListController';
import InputError from '@/components/InputError.vue';
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

    <div class="flex flex-col gap-5">
        <div class="flex items-center justify-between gap-3">
            <Link
                :href="GiftListController.view({ giftList: giftList.slug })"
                aria-label="Terug naar de lijst"
                class="flex size-11 shrink-0 pressable items-center justify-center rounded-[14px] bg-white chunky-sm"
            >
                <ArrowLeft class="size-5" :stroke-width="2.6" />
            </Link>
            <span class="font-display text-lg font-semibold"
                >Vrije bijdrage</span
            >
            <span class="size-11 shrink-0" aria-hidden="true"></span>
        </div>

        <div
            class="relative flex h-52 items-center justify-center rounded-[28px] bg-sun chunky-lg"
        >
            <span
                class="absolute top-5 left-5 size-5 rounded-full bg-sky inked"
                aria-hidden="true"
            ></span>
            <span
                class="absolute right-7 bottom-7 size-3.5 rounded-full bg-blush inked"
                aria-hidden="true"
            ></span>
            <span
                class="absolute top-9 right-14 size-2.5 rotate-45 bg-cream inked"
                aria-hidden="true"
            ></span>
            <PiggyBank class="size-32" :stroke-width="1.4" aria-hidden="true" />
        </div>

        <div class="flex flex-col gap-1.5">
            <h1
                class="font-display text-[2rem] leading-[1.05] font-bold tracking-tight"
            >
                Verras ons!
            </h1>
            <p class="leading-snug font-semibold">
                Niet aan één cadeau gebonden — gewoon iets voor de spaarpot van
                de kleine.
            </p>
        </div>

        <div
            v-if="giftList.is_closed"
            class="rounded-3xl border-dashed bg-white p-5 text-center font-bold inked"
        >
            De lijst is afgesloten, er zijn geen nieuwe bijdragen meer mogelijk.
        </div>

        <form v-else class="flex flex-col gap-5" @submit.prevent="submit">
            <InputError :message="giftError" />

            <div
                class="flex flex-col gap-3.5 rounded-3xl bg-white p-4.5 chunky"
            >
                <Label
                    for="amount"
                    class="text-[13px] tracking-[0.08em] uppercase"
                >
                    Hoeveel geef jij?
                </Label>

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <button
                        v-for="quickAmount in quickAmounts"
                        :key="quickAmount"
                        type="button"
                        class="h-12 rounded-[14px] font-display text-[17px] font-semibold transition-colors inked"
                        :class="
                            selectedAmountCents === quickAmount
                                ? 'bg-ink text-cream'
                                : 'bg-cream text-ink hover:bg-white'
                        "
                        :aria-pressed="selectedAmountCents === quickAmount"
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
                Je geeft als
                <span class="font-extrabold text-ink">{{ user.name }}</span>
                ({{ user.email }}). De bevestiging en betaalinstructies sturen
                we naar dat adres.
            </p>

            <button
                type="submit"
                :disabled="form.processing"
                class="flex h-15 pressable items-center justify-center rounded-[20px] bg-tangerine px-4 font-display text-[19px] font-semibold chunky disabled:opacity-60"
            >
                {{
                    selectedAmountCents > 0
                        ? `Geef ${formatEuro(selectedAmountCents)}`
                        : 'Bevestig mijn bijdrage'
                }}
            </button>
        </form>
    </div>
</template>
