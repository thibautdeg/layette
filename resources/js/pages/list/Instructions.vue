<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Check, Copy, Gift, MailCheck, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import GiftListController from '@/actions/App/Http/Controllers/GiftListController';
import PaymentQr from '@/components/PaymentQr.vue';
import { formatEuro } from '@/lib/money';

interface ContributionInfo {
    reference: string;
    type: string;
    name: string;
    amount: number | null;
    status: string;
    status_label: string;
}

const props = defineProps<{
    contribution: ContributionInfo;
    gift: { title: string | null; is_free: boolean };
    payment: {
        iban: string | null;
        account_holder: string | null;
        qr_svg: string | null;
    };
    listSlug: string;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const copiedField = ref<string | null>(null);

async function copy(field: string, value: string) {
    await navigator.clipboard.writeText(value);
    copiedField.value = field;
    setTimeout(() => {
        if (copiedField.value === field) {
            copiedField.value = null;
        }
    }, 2000);
}

const isPurchase = props.contribution.type === 'purchase';

function euroValue(cents: number): string {
    return (cents / 100).toFixed(2).replace('.', ',');
}

const paymentFields = computed(() =>
    [
        {
            key: 'iban',
            label: 'Rekeningnummer',
            display: props.payment.iban,
            value: props.payment.iban,
            mono: true,
        },
        {
            key: 'holder',
            label: 'Naam',
            display: props.payment.account_holder,
            value: props.payment.account_holder,
            mono: false,
        },
        {
            key: 'amount',
            label: 'Bedrag',
            display: formatEuro(props.contribution.amount ?? 0),
            value: ((props.contribution.amount ?? 0) / 100).toFixed(2),
            mono: false,
        },
        {
            key: 'reference',
            label: 'Mededeling',
            display: props.contribution.reference,
            value: props.contribution.reference,
            mono: true,
        },
    ].filter(
        (field): field is typeof field & { display: string; value: string } =>
            field.value !== null && field.display !== null,
    ),
);

function copyEverything() {
    const lines = [
        `Rekeningnummer: ${props.payment.iban}`,
        props.payment.account_holder
            ? `Naam: ${props.payment.account_holder}`
            : null,
        `Bedrag: ${euroValue(props.contribution.amount ?? 0)} euro`,
        `Mededeling: ${props.contribution.reference}`,
    ].filter(Boolean);

    copy('everything', lines.join('\n'));
}
</script>

<template>
    <Head :title="isPurchase ? 'Gereserveerd!' : 'Betaalinstructies'" />

    <div class="flex flex-col gap-5">
        <section
            class="relative flex flex-col items-center gap-4 overflow-hidden rounded-[28px] px-5 pt-4 pb-7 text-center chunky-lg"
            :class="isPurchase ? 'bg-sky' : 'bg-tangerine'"
        >
            <span
                class="absolute top-16 left-7 size-4.5 rounded-full bg-sun inked"
                aria-hidden="true"
            ></span>
            <span
                class="absolute top-28 right-9 h-3 w-6.5 rotate-30 rounded-md inked"
                :class="isPurchase ? 'bg-tangerine' : 'bg-sky'"
                aria-hidden="true"
            ></span>
            <span
                class="absolute top-56 left-5 h-2.5 w-6 -rotate-25 rounded-md bg-white inked"
                aria-hidden="true"
            ></span>
            <span
                class="absolute top-48 right-6 size-3.5 rounded-full bg-mint inked"
                aria-hidden="true"
            ></span>
            <span
                class="absolute top-8 left-1/3 size-3 rotate-45 bg-cream inked"
                aria-hidden="true"
            ></span>

            <Link
                :href="GiftListController.view({ giftList: listSlug })"
                aria-label="Terug naar de lijst"
                class="relative flex size-11 pressable items-center justify-center self-end rounded-[14px] bg-white chunky-sm"
            >
                <X class="size-5" :stroke-width="2.6" />
            </Link>

            <div
                class="relative flex size-40 items-center justify-center rounded-full border-[2.5px] border-ink bg-cream shadow-[6px_6px_0_var(--color-ink)]"
            >
                <Gift class="size-22" :stroke-width="1.5" aria-hidden="true" />
            </div>

            <h1
                class="relative font-display text-5xl leading-none font-bold tracking-tight"
            >
                Joepie!
            </h1>

            <p
                v-if="isPurchase"
                class="relative max-w-sm text-[17px] leading-snug font-bold"
            >
                Bedankt, {{ contribution.name }}!
                <strong class="font-extrabold">{{ gift.title }}</strong> staat
                nu als gereserveerd op de lijst. Je koopt en geeft het zelf, dus
                je hoeft verder niets te doen.
            </p>
            <p
                v-else-if="gift.is_free"
                class="relative max-w-sm text-[17px] leading-snug font-bold"
            >
                Bedankt, {{ contribution.name }}! Je geeft
                <strong class="font-extrabold">{{
                    formatEuro(contribution.amount ?? 0)
                }}</strong>
                als vrije bijdrage. Wat lief!
            </p>
            <p
                v-else
                class="relative max-w-sm text-[17px] leading-snug font-bold"
            >
                Bedankt, {{ contribution.name }}! Jouw
                <strong class="font-extrabold">{{
                    formatEuro(contribution.amount ?? 0)
                }}</strong>
                gaat in de pot voor
                <strong class="font-extrabold">{{ gift.title }}</strong
                >.
            </p>

            <span
                v-if="!isPurchase"
                class="relative rounded-full border-2 border-ink bg-cream px-3 py-1 text-xs font-extrabold"
            >
                {{ contribution.status_label }}
            </span>
        </section>

        <template v-if="!isPurchase">
            <div
                v-if="user && payment.iban"
                class="flex items-start gap-3 rounded-3xl bg-mint p-4 text-sm font-semibold inked"
            >
                <MailCheck class="mt-0.5 size-5 shrink-0" />
                <p>
                    We stuurden alle betaalgegevens naar
                    <strong class="font-extrabold">{{ user.email }}</strong
                    >. Je hoeft nu niets te doen — maak het over wanneer het je
                    uitkomt.
                </p>
            </div>

            <div
                v-if="payment.iban"
                class="flex flex-col gap-3 rounded-3xl bg-white p-4.5 chunky"
            >
                <h2 class="font-display text-xl font-semibold">
                    Via overschrijving
                </h2>

                <PaymentQr v-if="payment.qr_svg" :qr-svg="payment.qr_svg" />

                <div
                    v-for="field in paymentFields"
                    :key="field.key"
                    class="flex items-center justify-between gap-2 rounded-2xl bg-cream px-3 py-2"
                >
                    <div class="min-w-0">
                        <p class="text-xs font-extrabold text-bark uppercase">
                            {{ field.label }}
                        </p>
                        <p
                            class="truncate font-bold"
                            :class="field.mono ? 'font-mono' : ''"
                        >
                            {{ field.display }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="flex size-9 shrink-0 pressable items-center justify-center rounded-xl bg-white chunky-sm"
                        :aria-label="`Kopieer ${field.label.toLowerCase()}`"
                        @click="copy(field.key, field.value)"
                    >
                        <Check
                            v-if="copiedField === field.key"
                            class="size-4 text-green-700"
                        />
                        <Copy v-else class="size-4" />
                    </button>
                </div>

                <button
                    type="button"
                    class="mt-1 flex h-13 pressable items-center justify-center gap-2 rounded-[18px] bg-sun font-extrabold chunky"
                    @click="copyEverything"
                >
                    <Check v-if="copiedField === 'everything'" class="size-4" />
                    <Copy v-else class="size-4" />
                    {{
                        copiedField === 'everything'
                            ? 'Alles gekopieerd!'
                            : 'Kopieer alles in één keer'
                    }}
                </button>
            </div>

            <div
                v-else
                class="rounded-3xl bg-white p-4.5 text-sm font-semibold inked"
            >
                We bezorgen je onze rekeninggegevens persoonlijk. Hou zeker je
                referentie
                <span class="font-mono font-extrabold">{{
                    contribution.reference
                }}</span>
                bij de hand voor de mededeling van je overschrijving.
            </div>

            <p class="px-2 text-center text-sm font-semibold text-bark">
                Zodra we je storting op de rekening zien, bevestigen we je
                bijdrage. Vind je de mail niet meer terug? Alle gegevens staan
                ook bij je bijdragen in je account.
            </p>
        </template>

        <Link
            :href="GiftListController.view({ giftList: listSlug })"
            class="flex h-15 items-center justify-center rounded-[20px] border-[2.5px] border-ink bg-ink font-display text-[19px] font-semibold text-cream"
        >
            Terug naar de lijst
        </Link>
    </div>
</template>
