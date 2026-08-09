<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, Copy } from '@lucide/vue';
import { ref } from 'vue';
import GiftListController from '@/actions/App/Http/Controllers/GiftListController';
import PaymentQr from '@/components/PaymentQr.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
    <Head title="Betaalinstructies" />

    <Link
        :href="GiftListController.view({ giftList: listSlug })"
        class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-white/80 px-3 py-1.5 text-sm font-bold text-muted-foreground shadow-sm hover:text-foreground"
    >
        <ArrowLeft class="size-4" />
        Terug naar de lijst
    </Link>

    <template v-if="isPurchase">
        <Card class="rounded-3xl border-2 border-white/90 shadow-md">
            <CardContent class="p-6 text-center">
                <div class="mb-2 text-4xl" aria-hidden="true">🎀</div>
                <h1 class="font-display text-3xl font-bold">
                    Bedankt, {{ contribution.name }}!
                </h1>
                <p class="mt-2 text-muted-foreground">
                    <strong>{{ gift.title }}</strong> staat nu als gereserveerd
                    op de lijst. Je koopt en geeft het zelf, dus je hoeft verder
                    niets te doen.
                </p>
            </CardContent>
        </Card>
    </template>

    <template v-else>
        <header class="mb-6 text-center">
            <div class="mb-2 text-4xl" aria-hidden="true">💛</div>
            <h1 class="font-display text-3xl font-bold">
                Bedankt, {{ contribution.name }}!
            </h1>
            <p v-if="gift.is_free" class="mt-2 text-muted-foreground">
                Je geeft
                <strong>{{ formatEuro(contribution.amount ?? 0) }}</strong> als
                vrije bijdrage. Wat lief!
            </p>
            <p v-else class="mt-2 text-muted-foreground">
                Je draagt
                <strong>{{ formatEuro(contribution.amount ?? 0) }}</strong> bij
                aan <strong>{{ gift.title }}</strong
                >.
            </p>
            <Badge variant="secondary" class="mt-2">{{
                contribution.status_label
            }}</Badge>
        </header>

        <div class="flex flex-col gap-4">
            <Card
                v-if="payment.iban"
                class="rounded-3xl border-2 border-white/90 shadow-sm"
            >
                <CardHeader class="pb-2">
                    <CardTitle class="font-display text-lg"
                        >Via overschrijving</CardTitle
                    >
                </CardHeader>
                <CardContent class="flex flex-col gap-2 text-sm">
                    <PaymentQr
                        v-if="payment.qr_svg"
                        :qr-svg="payment.qr_svg"
                        :reference="contribution.reference"
                        class="mb-2"
                    />
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-muted-foreground">Rekeningnummer</p>
                            <p class="truncate font-mono font-medium">
                                {{ payment.iban }}
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            size="icon-sm"
                            @click="copy('iban', payment.iban)"
                        >
                            <Check
                                v-if="copiedField === 'iban'"
                                class="size-4 text-green-600"
                            />
                            <Copy v-else class="size-4" />
                        </Button>
                    </div>
                    <div
                        v-if="payment.account_holder"
                        class="flex items-center justify-between gap-2"
                    >
                        <div class="min-w-0">
                            <p class="text-muted-foreground">Naam</p>
                            <p class="truncate font-medium">
                                {{ payment.account_holder }}
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            size="icon-sm"
                            @click="copy('holder', payment.account_holder)"
                        >
                            <Check
                                v-if="copiedField === 'holder'"
                                class="size-4 text-green-600"
                            />
                            <Copy v-else class="size-4" />
                        </Button>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-muted-foreground">Bedrag</p>
                            <p class="font-medium">
                                {{ formatEuro(contribution.amount ?? 0) }}
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            size="icon-sm"
                            @click="
                                copy(
                                    'amount',
                                    ((contribution.amount ?? 0) / 100).toFixed(
                                        2,
                                    ),
                                )
                            "
                        >
                            <Check
                                v-if="copiedField === 'amount'"
                                class="size-4 text-green-600"
                            />
                            <Copy v-else class="size-4" />
                        </Button>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-muted-foreground">Mededeling</p>
                            <p class="font-mono font-medium">
                                {{ contribution.reference }}
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            size="icon-sm"
                            @click="copy('reference2', contribution.reference)"
                        >
                            <Check
                                v-if="copiedField === 'reference2'"
                                class="size-4 text-green-600"
                            />
                            <Copy v-else class="size-4" />
                        </Button>
                    </div>

                    <Button
                        variant="secondary"
                        class="mt-2 w-full rounded-full font-bold"
                        @click="copyEverything"
                    >
                        <Check
                            v-if="copiedField === 'everything'"
                            class="size-4 text-green-600"
                        />
                        <Copy v-else class="size-4" />
                        {{
                            copiedField === 'everything'
                                ? 'Alles gekopieerd!'
                                : 'Kopieer alles in één keer'
                        }}
                    </Button>
                </CardContent>
            </Card>

            <Card
                v-if="!payment.iban"
                class="rounded-3xl border-2 border-white/90 shadow-sm"
            >
                <CardContent class="p-4 text-sm text-muted-foreground">
                    We bezorgen je onze rekeninggegevens persoonlijk. Hou zeker
                    je referentie
                    <span class="font-mono font-medium text-foreground">{{
                        contribution.reference
                    }}</span>
                    bij de hand voor de mededeling van je overschrijving.
                </CardContent>
            </Card>
        </div>

        <p class="mt-6 text-center text-sm text-muted-foreground">
            Zodra we je storting op de rekening zien, bevestigen we je bijdrage.
            Je vindt deze pagina ook terug via je account of via de link in je
            bevestigingsmail.
        </p>
    </template>
</template>
