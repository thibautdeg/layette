<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Check, Copy } from '@lucide/vue';
import { ref } from 'vue';
import GiftListSettingController from '@/actions/App/Http/Controllers/Admin/GiftListSettingController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface GiftListInfo {
    slug: string;
    url: string;
    title: string;
    baby_name: string | null;
    baby_gender: string | null;
    intro: string | null;
    photo_url: string | null;
    iban: string | null;
    account_holder: string | null;
    wero_phone: string | null;
    expected_at: string | null;
    is_closed: boolean;
}

const props = defineProps<{
    giftList: GiftListInfo;
}>();

const copied = ref(false);

async function copyUrl() {
    await navigator.clipboard.writeText(props.giftList.url);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}

const form = useForm({
    title: props.giftList.title,
    baby_name: props.giftList.baby_name ?? '',
    baby_gender: props.giftList.baby_gender ?? '',
    intro: props.giftList.intro ?? '',
    iban: props.giftList.iban ?? '',
    account_holder: props.giftList.account_holder ?? '',
    wero_phone: props.giftList.wero_phone ?? '',
    expected_at: props.giftList.expected_at ?? '',
    is_closed: props.giftList.is_closed,
    photo: null as File | null,
});

function onPhotoChange(event: Event) {
    const input = event.target as HTMLInputElement;
    form.photo = input.files?.[0] ?? null;
}

function submit() {
    form.transform((data) => ({
        title: data.title,
        baby_name: data.baby_name === '' ? null : data.baby_name,
        baby_gender: data.baby_gender === '' ? null : data.baby_gender,
        intro: data.intro === '' ? null : data.intro,
        iban: data.iban === '' ? null : data.iban.replace(/\s/g, ''),
        account_holder: data.account_holder === '' ? null : data.account_holder,
        wero_phone: data.wero_phone === '' ? null : data.wero_phone,
        expected_at: data.expected_at === '' ? null : data.expected_at,
        is_closed: data.is_closed,
        ...(data.photo ? { photo: data.photo } : {}),
    })).post(GiftListSettingController.update.url(), { preserveScroll: true });
}
</script>

<template>
    <Head title="Instellingen" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Instellingen"
            description="De lijst, de intro en de betaalgegevens"
        />

        <Card>
            <CardHeader class="pb-2">
                <CardTitle class="text-base">Deelbare link</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex items-center gap-2">
                    <Input
                        :model-value="giftList.url"
                        readonly
                        class="font-mono text-sm"
                    />
                    <Button variant="outline" size="icon" @click="copyUrl">
                        <Check v-if="copied" class="size-4 text-green-600" />
                        <Copy v-else class="size-4" />
                    </Button>
                </div>
                <p class="mt-2 text-sm text-muted-foreground">
                    Deel deze link via het geboortekaartje of WhatsApp. De lijst
                    is niet vindbaar via zoekmachines.
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="p-4 sm:p-6">
                <form class="flex flex-col gap-5" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="list-title">Titel</Label>
                        <Input id="list-title" v-model="form.title" required />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="list-baby-name"
                            >Naam van het kindje (optioneel)</Label
                        >
                        <Input
                            id="list-baby-name"
                            v-model="form.baby_name"
                            placeholder="Bijvoorbeeld: Nora"
                        />
                        <p class="text-sm text-muted-foreground">
                            Verschijnt bovenaan het profiel op de lijst. Laat
                            leeg als jullie de naam nog geheim houden.
                        </p>
                        <InputError :message="form.errors.baby_name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="list-baby-gender">Jongen of meisje?</Label>
                        <select
                            id="list-baby-gender"
                            v-model="form.baby_gender"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        >
                            <option value="">Nog niet tonen</option>
                            <option value="girl">🎀 Meisje</option>
                            <option value="boy">💙 Jongen</option>
                            <option value="surprise">
                                🎁 Verrassing — we houden het geheim
                            </option>
                        </select>
                        <InputError :message="form.errors.baby_gender" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="list-intro">Intro</Label>
                        <textarea
                            id="list-intro"
                            v-model="form.intro"
                            rows="4"
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                            placeholder="Wie zijn jullie, wanneer wordt de baby verwacht…"
                        ></textarea>
                        <InputError :message="form.errors.intro" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="list-photo">Foto</Label>
                        <img
                            v-if="giftList.photo_url"
                            :src="giftList.photo_url"
                            alt=""
                            class="h-24 w-24 rounded-full object-cover"
                        />
                        <Input
                            id="list-photo"
                            type="file"
                            accept="image/*"
                            @change="onPhotoChange"
                        />
                        <InputError :message="form.errors.photo" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="list-expected-at">Verwachte datum</Label>
                        <Input
                            id="list-expected-at"
                            v-model="form.expected_at"
                            type="date"
                        />
                        <InputError :message="form.errors.expected_at" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="list-iban">Rekeningnummer (IBAN)</Label>
                        <Input
                            id="list-iban"
                            v-model="form.iban"
                            placeholder="BE68 5390 0754 7034"
                        />
                        <InputError :message="form.errors.iban" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="list-account-holder"
                            >Naam rekeninghouder</Label
                        >
                        <Input
                            id="list-account-holder"
                            v-model="form.account_holder"
                        />
                        <InputError :message="form.errors.account_holder" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="list-wero-phone">Wero gsm-nummer</Label>
                        <Input
                            id="list-wero-phone"
                            v-model="form.wero_phone"
                            placeholder="+32 470 12 34 56"
                        />
                        <InputError :message="form.errors.wero_phone" />
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <Checkbox
                            :model-value="form.is_closed"
                            @update:model-value="
                                (value: boolean | 'indeterminate') =>
                                    (form.is_closed = value === true)
                            "
                        />
                        Lijst afsluiten (geen nieuwe bijdragen meer, pagina
                        blijft bereikbaar)
                    </label>

                    <div>
                        <Button type="submit" :disabled="form.processing"
                            >Bewaren</Button
                        >
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
