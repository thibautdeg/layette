<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { AlertCircle, Check, Download, Pencil, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import ContributionCancellationController from '@/actions/App/Http/Controllers/Admin/ContributionCancellationController';
import ContributionConfirmationController from '@/actions/App/Http/Controllers/Admin/ContributionConfirmationController';
import ContributionController from '@/actions/App/Http/Controllers/Admin/ContributionController';
import ContributionExportController from '@/actions/App/Http/Controllers/Admin/ContributionExportController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { centsToEuroInput, euroInputToCents, formatEuro } from '@/lib/money';
import { edit as settingsEdit } from '@/routes/admin/settings';

interface TransactionInfo {
    amount: number;
    booked_at: string;
    counterparty_name: string | null;
}

interface ContributionEventInfo {
    id: number;
    label: string;
    by: string;
    at: string | null;
}

interface ContributionInfo {
    id: number;
    reference: string;
    type: string;
    type_label: string;
    name: string;
    email: string | null;
    amount: number | null;
    message: string | null;
    status: string;
    status_label: string;
    is_stale: boolean;
    days_open: number | null;
    created_at: string | null;
    confirmed_at: string | null;
    gift_title: string;
    transaction: TransactionInfo | null;
    events: ContributionEventInfo[];
}

interface WeekInfo {
    label: string;
    total: number;
    count: number;
}

const props = defineProps<{
    contributions: ContributionInfo[];
    stats: {
        funding_percentage: number;
        contribution_count: number;
        top_gift: string | null;
    };
    weekly: WeekInfo[];
    paymentDetailsMissing: boolean;
    nameGuesses: { id: number; name: string; by: string; at: string | null }[];
}>();

const weeklyMax = computed(() =>
    Math.max(...props.weekly.map((week) => week.total), 1),
);

const filter = ref<'all' | 'pending' | 'confirmed' | 'cancelled'>('all');

const filtered = computed(() =>
    filter.value === 'all'
        ? props.contributions
        : props.contributions.filter(
              (contribution) => contribution.status === filter.value,
          ),
);

const totalConfirmed = computed(() =>
    props.contributions
        .filter(
            (contribution) =>
                contribution.status === 'confirmed' &&
                contribution.amount !== null,
        )
        .reduce((sum, contribution) => sum + (contribution.amount ?? 0), 0),
);

const totalPending = computed(() =>
    props.contributions
        .filter(
            (contribution) =>
                contribution.status === 'pending' &&
                contribution.amount !== null,
        )
        .reduce((sum, contribution) => sum + (contribution.amount ?? 0), 0),
);

const editing = ref<ContributionInfo | null>(null);

const editForm = useForm({
    name: '',
    amount: '',
});

function openEdit(contribution: ContributionInfo) {
    editing.value = contribution;
    editForm.name = contribution.name;
    editForm.amount =
        contribution.amount !== null
            ? centsToEuroInput(contribution.amount)
            : '';
    editForm.clearErrors();
}

function submitEdit() {
    if (!editing.value) {
        return;
    }

    const isPurchase = editing.value.type === 'purchase';

    editForm
        .transform((data) => ({
            name: data.name,
            ...(isPurchase ? {} : { amount: euroInputToCents(data.amount) }),
        }))
        .patch(
            ContributionController.update.url({
                contribution: editing.value.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    editing.value = null;
                },
            },
        );
}

function confirmContribution(contribution: ContributionInfo) {
    router.post(
        ContributionConfirmationController.store.url({
            contribution: contribution.id,
        }),
        {},
        { preserveScroll: true },
    );
}

function cancelContribution(contribution: ContributionInfo) {
    if (
        !confirm(
            `Bijdrage ${contribution.reference} van ${contribution.name} annuleren?`,
        )
    ) {
        return;
    }

    router.post(
        ContributionCancellationController.store.url({
            contribution: contribution.id,
        }),
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Bijdragen" />

    <div class="flex flex-col gap-6 p-4">
        <div
            v-if="paymentDetailsMissing"
            class="flex flex-wrap items-center gap-2 rounded-md border border-amber-500/50 bg-amber-500/10 p-3 text-sm"
        >
            <AlertCircle class="size-4 shrink-0 text-amber-600" />
            <span>
                Je betaalgegevens zijn nog niet ingevuld — schenkers zien dan
                enkel hun referentie, zonder rekeningnummer om naar over te
                schrijven.
            </span>
            <Link
                :href="settingsEdit()"
                class="font-medium underline underline-offset-4"
            >
                Vul ze in bij Instellingen
            </Link>
        </div>

        <div class="flex items-start justify-between gap-2">
            <Heading
                title="Bijdragen"
                description="Wie gaf wat, en wat staat er nog open"
            />
            <Button variant="outline" size="sm" as-child>
                <a :href="ContributionExportController.index.url()" download>
                    <Download class="size-4" />
                    Exporteer (CSV)
                </a>
            </Button>
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            <Card>
                <CardContent class="p-4">
                    <p class="text-sm text-muted-foreground">Ontvangen</p>
                    <p class="text-xl font-bold">
                        {{ formatEuro(totalConfirmed) }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <p class="text-sm text-muted-foreground">In afwachting</p>
                    <p class="text-xl font-bold">
                        {{ formatEuro(totalPending) }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <p class="text-sm text-muted-foreground">
                        Lijst gefinancierd
                    </p>
                    <p class="text-xl font-bold">
                        {{ stats.funding_percentage }}%
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <p class="text-sm text-muted-foreground">Bijdragen</p>
                    <p class="text-xl font-bold">
                        {{ stats.contribution_count }}
                    </p>
                </CardContent>
            </Card>
            <Card class="col-span-2 lg:col-span-1">
                <CardContent class="p-4">
                    <p class="text-sm text-muted-foreground">
                        Populairste cadeau
                    </p>
                    <p
                        class="truncate text-base font-bold"
                        :title="stats.top_gift ?? undefined"
                    >
                        {{ stats.top_gift ?? '—' }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardContent class="p-4">
                <p class="mb-3 text-sm text-muted-foreground">
                    Toegezegd per week
                </p>
                <div class="flex h-28 items-end gap-2">
                    <div
                        v-for="week in weekly"
                        :key="week.label"
                        class="flex flex-1 flex-col items-center gap-1"
                    >
                        <span
                            v-if="week.total > 0"
                            class="text-xs text-muted-foreground"
                        >
                            {{ formatEuro(week.total) }}
                        </span>
                        <div
                            class="w-full max-w-10 rounded-t-md bg-primary/80"
                            :style="{
                                height: `${Math.max((week.total / weeklyMax) * 72, week.total > 0 ? 6 : 2)}px`,
                            }"
                            :title="`${week.count} bijdragen`"
                        ></div>
                        <span class="text-xs text-muted-foreground">
                            {{ week.label }}
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card v-if="nameGuesses.length > 0">
            <CardContent class="p-4">
                <p class="mb-3 text-sm text-muted-foreground">
                    🔮 Naamgokjes ({{ nameGuesses.length }})
                </p>
                <ul class="flex flex-col gap-1 text-sm">
                    <li
                        v-for="guess in nameGuesses"
                        :key="guess.id"
                        class="flex flex-wrap items-baseline gap-x-2"
                    >
                        <span class="font-semibold">{{ guess.name }}</span>
                        <span class="text-muted-foreground">
                            — {{ guess.by
                            }}<template v-if="guess.at">
                                ({{ guess.at }})</template
                            >
                        </span>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <div class="flex gap-2">
            <Button
                v-for="option in [
                    'all',
                    'pending',
                    'confirmed',
                    'cancelled',
                ] as const"
                :key="option"
                :variant="filter === option ? 'default' : 'outline'"
                size="sm"
                @click="filter = option"
            >
                {{
                    {
                        all: 'Alles',
                        pending: 'In afwachting',
                        confirmed: 'Bevestigd',
                        cancelled: 'Geannuleerd',
                    }[option]
                }}
            </Button>
        </div>

        <p
            v-if="filtered.length === 0"
            class="rounded-md bg-muted p-4 text-sm text-muted-foreground"
        >
            Geen bijdragen gevonden.
        </p>

        <div class="flex flex-col gap-3">
            <Card v-for="contribution in filtered" :key="contribution.id">
                <CardContent class="flex flex-col gap-2 p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium">{{ contribution.name }}</span>
                        <span
                            v-if="contribution.amount !== null"
                            class="font-semibold"
                            >{{ formatEuro(contribution.amount) }}</span
                        >
                        <Badge
                            v-if="contribution.type === 'purchase'"
                            variant="outline"
                            >Koopt zelf</Badge
                        >
                        <Badge
                            :variant="
                                contribution.status === 'confirmed'
                                    ? 'default'
                                    : contribution.status === 'cancelled'
                                      ? 'outline'
                                      : 'secondary'
                            "
                        >
                            {{ contribution.status_label }}
                        </Badge>
                        <span
                            v-if="contribution.is_stale"
                            class="inline-flex items-center gap-1 text-sm font-medium text-amber-600"
                        >
                            <AlertCircle class="size-4" />
                            al {{ contribution.days_open }} dagen open
                        </span>
                    </div>

                    <p class="text-sm text-muted-foreground">
                        {{ contribution.gift_title }} —
                        <span class="font-mono">{{
                            contribution.reference
                        }}</span>
                        <template v-if="contribution.email">
                            — {{ contribution.email }}</template
                        >
                        <template v-if="contribution.created_at">
                            — {{ contribution.created_at }}</template
                        >
                    </p>

                    <p
                        v-if="contribution.message"
                        class="rounded-md bg-muted p-2 text-sm italic"
                    >
                        "{{ contribution.message }}"
                    </p>

                    <p
                        v-if="contribution.transaction"
                        class="text-sm text-muted-foreground"
                    >
                        Gekoppelde storting:
                        {{ formatEuro(contribution.transaction.amount) }} op
                        {{ contribution.transaction.booked_at }}
                        <template
                            v-if="contribution.transaction.counterparty_name"
                        >
                            van {{ contribution.transaction.counterparty_name }}
                        </template>
                        <span
                            v-if="
                                contribution.amount !== null &&
                                contribution.transaction.amount !==
                                    contribution.amount
                            "
                            class="font-medium text-amber-600"
                        >
                            (wijkt af van aangekondigd bedrag)
                        </span>
                    </p>

                    <div class="mt-1 flex flex-wrap gap-2">
                        <Button
                            v-if="contribution.status === 'pending'"
                            variant="outline"
                            size="sm"
                            @click="confirmContribution(contribution)"
                        >
                            <Check class="size-4" />
                            Bevestigen
                        </Button>
                        <Button
                            v-if="contribution.status !== 'cancelled'"
                            variant="outline"
                            size="sm"
                            @click="openEdit(contribution)"
                        >
                            <Pencil class="size-4" />
                            Aanpassen
                        </Button>
                        <Button
                            v-if="contribution.status !== 'cancelled'"
                            variant="ghost"
                            size="sm"
                            class="text-destructive"
                            @click="cancelContribution(contribution)"
                        >
                            <X class="size-4" />
                            Annuleren
                        </Button>
                    </div>

                    <details
                        v-if="contribution.events.length > 0"
                        class="text-sm text-muted-foreground"
                    >
                        <summary class="cursor-pointer select-none">
                            Geschiedenis ({{ contribution.events.length }})
                        </summary>
                        <ul class="mt-1 flex flex-col gap-0.5 pl-4">
                            <li
                                v-for="event in contribution.events"
                                :key="event.id"
                            >
                                {{ event.at }} — {{ event.label }} door
                                {{ event.by }}
                            </li>
                        </ul>
                    </details>
                </CardContent>
            </Card>
        </div>
    </div>

    <Dialog
        :open="editing !== null"
        @update:open="
            (open: boolean) => {
                if (!open) editing = null;
            }
        "
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Bijdrage aanpassen</DialogTitle>
            </DialogHeader>
            <form class="flex flex-col gap-4" @submit.prevent="submitEdit">
                <div class="grid gap-2">
                    <Label for="contribution-name">Naam</Label>
                    <Input
                        id="contribution-name"
                        v-model="editForm.name"
                        required
                    />
                    <InputError :message="editForm.errors.name" />
                </div>
                <div v-if="editing?.type !== 'purchase'" class="grid gap-2">
                    <Label for="contribution-amount">Bedrag (euro)</Label>
                    <Input
                        id="contribution-amount"
                        v-model="editForm.amount"
                        type="number"
                        step="0.01"
                        min="1"
                        required
                    />
                    <InputError :message="editForm.errors.amount" />
                </div>
                <DialogFooter>
                    <Button type="submit" :disabled="editForm.processing"
                        >Bewaren</Button
                    >
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
