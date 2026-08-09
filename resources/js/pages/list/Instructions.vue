<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, Copy } from '@lucide/vue';
import { ref } from 'vue';
import GiftListController from '@/actions/App/Http/Controllers/GiftListController';
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
    gift: { title: string };
    payment: {
        iban: string | null;
        account_holder: string | null;
        wero_phone: string | null;
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
</script>

<template>
    <Head title="Betaalinstructies" />

    <Link
        :href="GiftListController.view({ giftList: listSlug })"
        class="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
    >
        <ArrowLeft class="size-4" />
        Terug naar de lijst
    </Link>

    <template v-if="isPurchase">
        <Card>
            <CardContent class="p-6 text-center">
                <h1 class="text-2xl font-bold">
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
            <h1 class="text-2xl font-bold">
                Bedankt, {{ contribution.name }}!
            </h1>
            <p class="mt-2 text-muted-foreground">
                Je draagt
                <strong>{{ formatEuro(contribution.amount ?? 0) }}</strong> bij
                aan <strong>{{ gift.title }}</strong
                >.
            </p>
            <Badge variant="secondary" class="mt-2">{{
                contribution.status_label
            }}</Badge>
        </header>

        <Card class="mb-4 border-primary">
            <CardHeader class="pb-2">
                <CardTitle class="text-base">Jouw referentie</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    class="flex items-center justify-between gap-2 rounded-md bg-muted p-3"
                >
                    <span class="font-mono text-xl font-bold tracking-wider">{{
                        contribution.reference
                    }}</span>
                    <Button
                        variant="outline"
                        size="icon-sm"
                        @click="copy('reference', contribution.reference)"
                    >
                        <Check
                            v-if="copiedField === 'reference'"
                            class="size-4 text-green-600"
                        />
                        <Copy v-else class="size-4" />
                    </Button>
                </div>
                <p class="mt-2 text-sm text-muted-foreground">
                    Zet deze code in de mededeling van je overschrijving. Zo
                    weten we meteen waarvoor je storting dient.
                </p>
            </CardContent>
        </Card>

        <div class="flex flex-col gap-4">
            <Card v-if="payment.wero_phone">
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">1. Via Wero</CardTitle>
                </CardHeader>
                <CardContent class="text-sm">
                    <p>
                        Stuur
                        <strong>{{
                            formatEuro(contribution.amount ?? 0)
                        }}</strong>
                        via Wero (in je bankapp) naar
                        <strong class="whitespace-nowrap">{{
                            payment.wero_phone
                        }}</strong>
                        en zet de referentie in het bericht.
                    </p>
                    <p class="mt-1 text-muted-foreground">
                        Instant en gratis, vanuit de bankapp die je al hebt.
                    </p>
                </CardContent>
            </Card>

            <Card v-if="payment.iban">
                <CardHeader class="pb-2">
                    <CardTitle class="text-base"
                        >{{ payment.wero_phone ? '2.' : '1.' }} Via
                        overschrijving</CardTitle
                    >
                </CardHeader>
                <CardContent class="flex flex-col gap-2 text-sm">
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
