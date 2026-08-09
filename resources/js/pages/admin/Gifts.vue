<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    Eye,
    EyeOff,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import GiftController from '@/actions/App/Http/Controllers/Admin/GiftController';
import GiftOrderController from '@/actions/App/Http/Controllers/Admin/GiftOrderController';
import GiftVisibilityController from '@/actions/App/Http/Controllers/Admin/GiftVisibilityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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

interface GiftInfo {
    id: number;
    title: string;
    description: string | null;
    price: number;
    image_url: string | null;
    shop_url: string | null;
    allows_partial_contributions: boolean;
    allows_purchase: boolean;
    is_hidden: boolean;
    status: string;
    status_label: string;
    pledged: number;
    confirmed: number;
    contribution_count: number;
}

const props = defineProps<{
    gifts: GiftInfo[];
}>();

const dialogOpen = ref(false);
const editingGift = ref<GiftInfo | null>(null);

const form = useForm({
    title: '',
    description: '',
    price: '',
    shop_url: '',
    allows_partial_contributions: true,
    allows_purchase: true,
    image: null as File | null,
});

function openCreate() {
    editingGift.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(gift: GiftInfo) {
    editingGift.value = gift;
    form.title = gift.title;
    form.description = gift.description ?? '';
    form.price = centsToEuroInput(gift.price);
    form.shop_url = gift.shop_url ?? '';
    form.allows_partial_contributions = gift.allows_partial_contributions;
    form.allows_purchase = gift.allows_purchase;
    form.image = null;
    form.clearErrors();
    dialogOpen.value = true;
}

function submit() {
    const transformed = form.transform((data) => ({
        title: data.title,
        description: data.description === '' ? null : data.description,
        price: euroInputToCents(data.price),
        shop_url: data.shop_url === '' ? null : data.shop_url,
        allows_partial_contributions: data.allows_partial_contributions,
        allows_purchase: data.allows_purchase,
        ...(data.image ? { image: data.image } : {}),
    }));

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
        },
    };

    if (editingGift.value) {
        transformed.post(
            GiftController.update.url({ gift: editingGift.value.id }),
            options,
        );
    } else {
        transformed.post(GiftController.store.url(), options);
    }
}

function toggleVisibility(gift: GiftInfo) {
    router.patch(
        GiftVisibilityController.update.url({ gift: gift.id }),
        {},
        { preserveScroll: true },
    );
}

function destroy(gift: GiftInfo) {
    if (
        !confirm(
            `"${gift.title}" en alle bijhorende bijdragen verwijderen? Dit kan niet ongedaan gemaakt worden.`,
        )
    ) {
        return;
    }

    router.delete(GiftController.destroy.url({ gift: gift.id }), {
        preserveScroll: true,
    });
}

function move(gift: GiftInfo, direction: -1 | 1) {
    const ids = props.gifts.map((item) => item.id);
    const index = ids.indexOf(gift.id);
    const target = index + direction;

    if (target < 0 || target >= ids.length) {
        return;
    }

    [ids[index], ids[target]] = [ids[target], ids[index]];

    router.patch(
        GiftOrderController.update.url(),
        { ids },
        { preserveScroll: true },
    );
}

function onImageChange(event: Event) {
    const input = event.target as HTMLInputElement;
    form.image = input.files?.[0] ?? null;
}
</script>

<template>
    <Head title="Cadeaus" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading
                title="Cadeaus"
                description="Beheer wat er op de lijst staat"
            />
            <Button @click="openCreate">
                <Plus class="size-4" />
                Nieuw cadeau
            </Button>
        </div>

        <p
            v-if="gifts.length === 0"
            class="rounded-md bg-muted p-4 text-sm text-muted-foreground"
        >
            Nog geen cadeaus. Voeg je eerste cadeau toe!
        </p>

        <div class="flex flex-col gap-3">
            <Card
                v-for="(gift, index) in gifts"
                :key="gift.id"
                :class="{ 'opacity-60': gift.is_hidden }"
            >
                <CardContent class="flex items-center gap-4 p-4">
                    <div class="flex flex-col gap-1">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :disabled="index === 0"
                            @click="move(gift, -1)"
                        >
                            <ArrowUp class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :disabled="index === gifts.length - 1"
                            @click="move(gift, 1)"
                        >
                            <ArrowDown class="size-4" />
                        </Button>
                    </div>

                    <img
                        v-if="gift.image_url"
                        :src="gift.image_url"
                        alt=""
                        class="h-16 w-16 shrink-0 rounded-md object-cover"
                    />

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">{{ gift.title }}</span>
                            <Badge variant="outline">{{
                                gift.status_label
                            }}</Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ formatEuro(gift.price) }}
                            <template v-if="gift.contribution_count > 0">
                                — {{ formatEuro(gift.pledged) }} toegezegd,
                                {{ formatEuro(gift.confirmed) }} ontvangen ({{
                                    gift.contribution_count
                                }}
                                {{
                                    gift.contribution_count === 1
                                        ? 'bijdrage'
                                        : 'bijdragen'
                                }})
                            </template>
                        </p>
                    </div>

                    <div class="flex shrink-0 gap-1">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :title="
                                gift.is_hidden ? 'Opnieuw tonen' : 'Verbergen'
                            "
                            @click="toggleVisibility(gift)"
                        >
                            <EyeOff v-if="gift.is_hidden" class="size-4" />
                            <Eye v-else class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            title="Aanpassen"
                            @click="openEdit(gift)"
                        >
                            <Pencil class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            title="Verwijderen"
                            class="text-destructive"
                            @click="destroy(gift)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>

    <Dialog v-model:open="dialogOpen">
        <DialogContent class="max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{{
                    editingGift ? 'Cadeau aanpassen' : 'Nieuw cadeau'
                }}</DialogTitle>
            </DialogHeader>

            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="gift-title">Titel</Label>
                    <Input id="gift-title" v-model="form.title" required />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="gift-description">Korte omschrijving</Label>
                    <textarea
                        id="gift-description"
                        v-model="form.description"
                        rows="2"
                        class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                    ></textarea>
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="gift-price">Richtprijs (euro)</Label>
                    <Input
                        id="gift-price"
                        v-model="form.price"
                        type="number"
                        step="0.01"
                        min="1"
                        required
                    />
                    <InputError :message="form.errors.price" />
                </div>

                <div class="grid gap-2">
                    <Label for="gift-shop-url">Link naar webshop</Label>
                    <Input
                        id="gift-shop-url"
                        v-model="form.shop_url"
                        type="url"
                        placeholder="https://"
                    />
                    <InputError :message="form.errors.shop_url" />
                </div>

                <div class="grid gap-2">
                    <Label for="gift-image">Foto</Label>
                    <Input
                        id="gift-image"
                        type="file"
                        accept="image/*"
                        @change="onImageChange"
                    />
                    <InputError :message="form.errors.image" />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <Checkbox
                        :model-value="form.allows_partial_contributions"
                        @update:model-value="
                            (value: boolean | 'indeterminate') =>
                                (form.allows_partial_contributions =
                                    value === true)
                        "
                    />
                    Gedeeltelijk bijdragen mag
                </label>

                <label class="flex items-center gap-2 text-sm">
                    <Checkbox
                        :model-value="form.allows_purchase"
                        @update:model-value="
                            (value: boolean | 'indeterminate') =>
                                (form.allows_purchase = value === true)
                        "
                    />
                    Zelf kopen mag
                </label>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        {{ editingGift ? 'Bewaren' : 'Toevoegen' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
