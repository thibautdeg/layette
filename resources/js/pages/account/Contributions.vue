<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, ref } from 'vue';
import AccountContributionController from '@/actions/App/Http/Controllers/AccountContributionController';
import ContributionInstructionController from '@/actions/App/Http/Controllers/ContributionInstructionController';
import GiftListController from '@/actions/App/Http/Controllers/GiftListController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatEuro } from '@/lib/money';
import { logout } from '@/routes';

interface ContributionInfo {
    id: number;
    reference: string;
    type: string;
    type_label: string;
    amount: number | null;
    message: string | null;
    together_with: string | null;
    status: string;
    status_label: string;
    is_editable: boolean;
    created_at: string | null;
    gift: { title: string; is_full: boolean };
}

defineProps<{
    contributions: ContributionInfo[];
    payment: {
        iban: string | null;
        account_holder: string | null;
        wero_phone: string | null;
    };
    listSlug: string;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const editing = ref<ContributionInfo | null>(null);

const editForm = useForm({
    message: '',
    together_with: '',
});

function openEdit(contribution: ContributionInfo) {
    editing.value = contribution;
    editForm.message = contribution.message ?? '';
    editForm.together_with = contribution.together_with ?? '';
    editForm.clearErrors();
}

function submitEdit() {
    if (!editing.value) {
        return;
    }

    editForm
        .transform((data) => ({
            message: data.message === '' ? null : data.message,
            together_with:
                data.together_with === '' ? null : data.together_with,
        }))
        .patch(
            AccountContributionController.update.url({
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

function cancelContribution(contribution: ContributionInfo) {
    if (
        !confirm(
            `Ben je zeker dat je je ${contribution.type === 'purchase' ? 'reservatie' : 'bijdrage'} voor "${contribution.gift.title}" wil annuleren?`,
        )
    ) {
        return;
    }

    router.delete(
        AccountContributionController.destroy.url({
            contribution: contribution.id,
        }),
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head title="Mijn bijdragen" />

    <div class="mb-6 flex items-center justify-between">
        <Link
            :href="GiftListController.view({ giftList: listSlug })"
            class="inline-flex items-center gap-1.5 rounded-full bg-white/80 px-3 py-1.5 text-sm font-bold text-muted-foreground shadow-sm hover:text-foreground"
        >
            <ArrowLeft class="size-4" />
            Naar de lijst
        </Link>
        <Link
            :href="logout()"
            as="button"
            class="text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
        >
            Afmelden
        </Link>
    </div>

    <h1 class="mb-1 font-display text-3xl font-bold">
        Dag {{ user?.name?.split(' ')[0] }}! 👋
    </h1>
    <p class="mb-6 text-sm text-muted-foreground">
        Dit zijn de bijdragen van je account ({{ user?.email }}).
    </p>

    <p
        v-if="contributions.length === 0"
        class="rounded-md bg-muted p-4 text-sm text-muted-foreground"
    >
        Je hebt nog geen bijdragen gedaan.
    </p>

    <div class="flex flex-col gap-4">
        <Card
            v-for="contribution in contributions"
            :key="contribution.id"
            class="rounded-3xl border-2 border-white/90 shadow-sm"
        >
            <CardHeader class="pb-2">
                <div class="flex items-start justify-between gap-2">
                    <CardTitle class="text-base">{{
                        contribution.gift.title
                    }}</CardTitle>
                    <Badge
                        :variant="
                            contribution.status === 'confirmed'
                                ? 'default'
                                : 'secondary'
                        "
                    >
                        {{ contribution.status_label }}
                    </Badge>
                </div>
            </CardHeader>
            <CardContent class="flex flex-col gap-3 text-sm">
                <div
                    class="flex flex-wrap gap-x-6 gap-y-1 text-muted-foreground"
                >
                    <span>{{ contribution.type_label }}</span>
                    <span v-if="contribution.together_with"
                        >samen met {{ contribution.together_with }}</span
                    >
                    <span
                        v-if="contribution.amount !== null"
                        class="font-medium text-foreground"
                    >
                        {{ formatEuro(contribution.amount) }}
                    </span>
                    <span v-if="contribution.created_at">{{
                        contribution.created_at
                    }}</span>
                    <span
                        v-if="contribution.gift.is_full"
                        class="text-foreground"
                        >Cadeau intussen volzet 🎉</span
                    >
                </div>

                <p
                    v-if="contribution.message"
                    class="rounded-md bg-muted p-2 italic"
                >
                    "{{ contribution.message }}"
                </p>

                <div
                    v-if="
                        contribution.status === 'pending' &&
                        contribution.type === 'contribution'
                    "
                    class="rounded-md border p-3"
                >
                    <p class="mb-1 font-medium">Nog over te schrijven</p>
                    <p class="text-muted-foreground">
                        Referentie:
                        <span class="font-mono font-medium text-foreground">{{
                            contribution.reference
                        }}</span>
                    </p>
                    <p v-if="payment.wero_phone" class="text-muted-foreground">
                        Wero:
                        <span class="text-foreground">{{
                            payment.wero_phone
                        }}</span>
                    </p>
                    <p v-if="payment.iban" class="text-muted-foreground">
                        Rekening:
                        <span class="font-mono text-foreground">{{
                            payment.iban
                        }}</span>
                        <template v-if="payment.account_holder">
                            ({{ payment.account_holder }})</template
                        >
                    </p>
                    <Link
                        :href="
                            ContributionInstructionController.view({
                                contribution: contribution.reference,
                            })
                        "
                        class="mt-1 inline-block underline underline-offset-4"
                    >
                        Alle betaalinstructies
                    </Link>
                </div>

                <div v-if="contribution.is_editable" class="flex gap-2">
                    <Dialog
                        :open="editing?.id === contribution.id"
                        @update:open="
                            (open: boolean) => {
                                if (!open) editing = null;
                            }
                        "
                    >
                        <DialogTrigger as-child>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="openEdit(contribution)"
                                >Aanpassen</Button
                            >
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Bijdrage aanpassen</DialogTitle>
                                <DialogDescription>
                                    Je kan je persoonlijk woordje aanpassen
                                    zolang de bijdrage niet bevestigd is.
                                </DialogDescription>
                            </DialogHeader>
                            <form
                                class="flex flex-col gap-4"
                                @submit.prevent="submitEdit"
                            >
                                <div class="grid gap-2">
                                    <Label for="edit-together-with"
                                        >Ik geef samen met…</Label
                                    >
                                    <Input
                                        id="edit-together-with"
                                        v-model="editForm.together_with"
                                        placeholder="Bijvoorbeeld: Marcel"
                                    />
                                    <InputError
                                        :message="editForm.errors.together_with"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="edit-message"
                                        >Persoonlijk woordje</Label
                                    >
                                    <textarea
                                        id="edit-message"
                                        v-model="editForm.message"
                                        rows="3"
                                        maxlength="1000"
                                        class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                    ></textarea>
                                    <InputError
                                        :message="editForm.errors.message"
                                    />
                                </div>
                                <DialogFooter>
                                    <Button
                                        type="submit"
                                        :disabled="editForm.processing"
                                        >Bewaren</Button
                                    >
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-destructive"
                        @click="cancelContribution(contribution)"
                    >
                        Annuleren
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
