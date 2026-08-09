<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Upload } from '@lucide/vue';
import { ref } from 'vue';
import BankStatementImportController from '@/actions/App/Http/Controllers/Admin/BankStatementImportController';
import BankTransactionIgnoreController from '@/actions/App/Http/Controllers/Admin/BankTransactionIgnoreController';
import BankTransactionMatchController from '@/actions/App/Http/Controllers/Admin/BankTransactionMatchController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatEuro } from '@/lib/money';

interface TransactionInfo {
    id: number;
    booked_at: string;
    amount: number;
    counterparty_name: string | null;
    counterparty_account: string | null;
    description: string | null;
    is_ignored: boolean;
    contribution_reference: string | null;
}

interface PendingContributionOption {
    id: number;
    label: string;
}

defineProps<{
    unmatched: TransactionInfo[];
    recent: TransactionInfo[];
    pendingContributions: PendingContributionOption[];
}>();

const page = usePage<{
    flash?: {
        import_result?: {
            created: number;
            duplicates: number;
            matched: number;
            unmatched: number;
        };
    };
    import_result?: {
        created: number;
        duplicates: number;
        matched: number;
        unmatched: number;
    };
}>();

const importForm = useForm({
    statement: null as File | null,
});

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    importForm.statement = input.files?.[0] ?? null;
}

function submitImport() {
    importForm.post(BankStatementImportController.store.url(), {
        preserveScroll: true,
        onSuccess: () => importForm.reset(),
    });
}

const selectedContribution = ref<Record<number, string>>({});

function match(transaction: TransactionInfo) {
    const contributionId = selectedContribution.value[transaction.id];

    if (!contributionId) {
        return;
    }

    router.post(
        BankTransactionMatchController.store.url({
            bankTransaction: transaction.id,
        }),
        { contribution_id: Number(contributionId) },
        { preserveScroll: true },
    );
}

function ignore(transaction: TransactionInfo) {
    router.post(
        BankTransactionIgnoreController.store.url({
            bankTransaction: transaction.id,
        }),
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Bank" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Bankafschrift"
            description="Importeer je afschrift en punt bijdragen af"
        />

        <Card>
            <CardHeader class="pb-2">
                <CardTitle class="text-base">Afschrift importeren</CardTitle>
            </CardHeader>
            <CardContent>
                <form
                    class="flex flex-col gap-3 sm:flex-row sm:items-start"
                    @submit.prevent="submitImport"
                >
                    <div class="flex-1">
                        <Input
                            type="file"
                            accept=".csv,.txt"
                            @change="onFileChange"
                        />
                        <InputError
                            class="mt-1"
                            :message="importForm.errors.statement"
                        />
                        <p class="mt-1 text-sm text-muted-foreground">
                            Exporteer je verrichtingen als CSV uit je
                            bankomgeving. Hetzelfde bestand twee keer opladen
                            kan geen kwaad.
                        </p>
                    </div>
                    <Button
                        type="submit"
                        :disabled="
                            importForm.processing || !importForm.statement
                        "
                    >
                        <Upload class="size-4" />
                        Importeren
                    </Button>
                </form>

                <div
                    v-if="page.props.import_result"
                    class="mt-3 rounded-md bg-muted p-3 text-sm"
                >
                    {{ page.props.import_result.created }} nieuwe verrichtingen
                    ingelezen,
                    {{ page.props.import_result.duplicates }} overgeslagen (al
                    gekend), {{ page.props.import_result.matched }} automatisch
                    gekoppeld, {{ page.props.import_result.unmatched }} zonder
                    herkenbare referentie.
                </div>
            </CardContent>
        </Card>

        <section>
            <h2 class="mb-3 font-semibold">
                Openstaande transacties ({{ unmatched.length }})
            </h2>

            <p
                v-if="unmatched.length === 0"
                class="rounded-md bg-muted p-4 text-sm text-muted-foreground"
            >
                Niets meer te koppelen. Alles is afgepunt!
            </p>

            <div class="flex flex-col gap-3">
                <Card v-for="transaction in unmatched" :key="transaction.id">
                    <CardContent class="flex flex-col gap-2 p-4">
                        <div class="flex flex-wrap items-baseline gap-2">
                            <span class="font-semibold">{{
                                formatEuro(transaction.amount)
                            }}</span>
                            <span class="text-sm text-muted-foreground">{{
                                transaction.booked_at
                            }}</span>
                            <span
                                v-if="transaction.counterparty_name"
                                class="text-sm"
                                >{{ transaction.counterparty_name }}</span
                            >
                        </div>
                        <p
                            v-if="transaction.description"
                            class="text-sm text-muted-foreground"
                        >
                            Mededeling: "{{ transaction.description }}"
                        </p>

                        <div class="mt-1 flex flex-col gap-2 sm:flex-row">
                            <select
                                v-model="selectedContribution[transaction.id]"
                                class="h-9 flex-1 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                            >
                                <option value="" disabled selected>
                                    Koppel aan een bijdrage…
                                </option>
                                <option
                                    v-for="option in pendingContributions"
                                    :key="option.id"
                                    :value="String(option.id)"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="
                                        !selectedContribution[transaction.id]
                                    "
                                    @click="match(transaction)"
                                >
                                    Koppelen
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="ignore(transaction)"
                                >
                                    Niet relevant
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </section>

        <section v-if="recent.length > 0">
            <h2 class="mb-3 font-semibold">Recent verwerkt</h2>
            <div class="flex flex-col gap-2">
                <div
                    v-for="transaction in recent"
                    :key="transaction.id"
                    class="flex flex-wrap items-center gap-2 rounded-md border p-3 text-sm"
                >
                    <span class="font-medium">{{
                        formatEuro(transaction.amount)
                    }}</span>
                    <span class="text-muted-foreground">{{
                        transaction.booked_at
                    }}</span>
                    <span
                        v-if="transaction.counterparty_name"
                        class="text-muted-foreground"
                        >{{ transaction.counterparty_name }}</span
                    >
                    <Badge
                        v-if="transaction.contribution_reference"
                        variant="secondary"
                    >
                        {{ transaction.contribution_reference }}
                    </Badge>
                    <Badge v-else-if="transaction.is_ignored" variant="outline"
                        >Niet relevant</Badge
                    >
                </div>
            </div>
        </section>
    </div>
</template>
