<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Blocks,
    CircleCheck,
    ExternalLink,
    HandCoins,
    Milk,
    Share2,
    ShoppingBag,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import FreeContributionController from '@/actions/App/Http/Controllers/FreeContributionController';
import GiftContributionController from '@/actions/App/Http/Controllers/GiftContributionController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatEuro } from '@/lib/money';

defineOptions({
    layout: {
        wide: true,
    },
});

interface GiftListInfo {
    slug: string;
    title: string;
    baby_name: string | null;
    baby_gender: { label: string; emoji: string } | null;
    intro: string | null;
    photo_url: string | null;
    is_closed: boolean;
}

interface GiftInfo {
    id: number;
    title: string;
    description: string | null;
    price: number;
    image_url: string | null;
    shop_url: string | null;
    status: string;
    status_label: string;
    pledged: number;
    remaining: number;
    allows_partial_contributions: boolean;
    allows_purchase: boolean;
    is_available: boolean;
}

const props = defineProps<{
    giftList: GiftListInfo;
    gifts: GiftInfo[];
}>();

function progressPercentage(gift: GiftInfo): number {
    if (gift.price === 0) {
        return 0;
    }

    return Math.min(100, Math.round((gift.pledged / gift.price) * 100));
}

async function share() {
    const url = window.location.href;
    const shareData = {
        title: props.giftList.title,
        text: 'Neem een kijkje op onze geboortelijst!',
        url,
    };

    if (navigator.share) {
        try {
            await navigator.share(shareData);
        } catch {
            // Sharing was dismissed by the user.
        }

        return;
    }

    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(url);
            toast.success(
                'Link gekopieerd! Plak hem in WhatsApp of een berichtje.',
            );

            return;
        }

        const textarea = document.createElement('textarea');
        textarea.value = url;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';

        document.body.append(textarea);
        textarea.select();

        const copied = document.execCommand('copy');
        textarea.remove();

        if (copied) {
            toast.success(
                'Link gekopieerd! Plak hem in WhatsApp of een berichtje.',
            );

            return;
        }
    } catch {
        // Fall through to a visible message below.
    }

    toast.error('Kopiëren lukte niet. Selecteer de link in je adresbalk.');
}

const sortBy = ref<'standaard' | 'prijs-op' | 'prijs-af'>('standaard');

function sorted(gifts: GiftInfo[]): GiftInfo[] {
    if (sortBy.value === 'prijs-op') {
        return [...gifts].sort((a, b) => a.price - b.price);
    }

    if (sortBy.value === 'prijs-af') {
        return [...gifts].sort((a, b) => b.price - a.price);
    }

    return gifts;
}

const availableGifts = computed(() =>
    sorted(props.gifts.filter((gift) => gift.is_available)),
);

const chosenGifts = computed(() =>
    props.gifts.filter((gift) => !gift.is_available),
);

const giftSectionIntro = computed(() => {
    if (props.giftList.is_closed) {
        return 'De lijst is afgesloten. Dankjewel voor alle liefde!';
    }

    if (props.gifts.length === 0) {
        return 'Er staan nog geen cadeaus op de lijst.';
    }

    if (availableGifts.value.length === 0) {
        return 'Alles is gekozen. Een vrije bijdrage kan nog altijd.';
    }

    return 'Kies een cadeau en geef het volledig of leg samen bij.';
});
</script>

<template>
    <div class="flex flex-col gap-10">
        <Head :title="giftList.title" />

        <section class="flex flex-col items-center text-center">
            <img
                v-if="giftList.photo_url"
                :src="giftList.photo_url"
                alt=""
                class="mb-4 size-28 rounded-full object-cover shadow-lg ring-4 ring-white"
            />
            <div
                v-else
                class="mb-4 flex size-20 items-center justify-center rounded-full bg-white text-primary shadow-md"
                aria-hidden="true"
            >
                <Milk class="size-9" />
            </div>

            <h1
                class="font-display text-4xl leading-tight font-bold tracking-tight sm:text-5xl"
            >
                {{ giftList.baby_name ?? giftList.title }}
            </h1>
            <p
                v-if="giftList.baby_name"
                class="mt-1 text-base text-muted-foreground"
            >
                {{ giftList.title }}
            </p>

            <span
                v-if="giftList.baby_gender"
                class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-accent px-3 py-1 text-sm font-bold text-accent-foreground"
            >
                {{ giftList.baby_gender.emoji }}
                {{ giftList.baby_gender.label }}
            </span>

            <p
                v-if="giftList.intro"
                class="mt-4 max-w-xl leading-6 whitespace-pre-line text-muted-foreground"
            >
                {{ giftList.intro }}
            </p>

            <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                <Button
                    v-if="!giftList.is_closed"
                    as-child
                    variant="outline"
                    class="rounded-full bg-card font-bold"
                >
                    <Link
                        :href="
                            FreeContributionController.create({
                                giftList: giftList.slug,
                            })
                        "
                    >
                        <HandCoins class="size-4" />
                        Vrij bedrag geven
                    </Link>
                </Button>
                <Button
                    variant="ghost"
                    class="rounded-full text-muted-foreground"
                    @click="share"
                >
                    <Share2 class="size-4" />
                    Lijst delen
                </Button>
            </div>
        </section>

        <section id="cadeaus" class="scroll-mt-20">
            <div
                class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <h2 class="font-display text-2xl font-bold">Cadeaus</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ giftSectionIntro }}
                    </p>
                </div>

                <select
                    v-if="availableGifts.length > 5"
                    v-model="sortBy"
                    aria-label="Sorteren"
                    class="h-9 rounded-full border border-input bg-card px-3 text-sm text-muted-foreground focus-visible:border-ring focus-visible:outline-none"
                >
                    <option value="standaard">Onze volgorde</option>
                    <option value="prijs-op">Prijs: laag naar hoog</option>
                    <option value="prijs-af">Prijs: hoog naar laag</option>
                </select>
            </div>

            <div v-if="availableGifts.length > 0" class="flex flex-col gap-3">
                <Card
                    v-for="gift in availableGifts"
                    :key="gift.id"
                    class="overflow-hidden rounded-3xl border-2 border-white/90 py-0 shadow-sm"
                >
                    <CardContent
                        class="grid grid-cols-[4.5rem_minmax(0,1fr)] gap-4 p-4 sm:grid-cols-[6rem_minmax(0,1fr)]"
                    >
                        <img
                            v-if="gift.image_url"
                            :src="gift.image_url"
                            alt=""
                            class="aspect-square w-full rounded-2xl object-cover"
                        />
                        <div
                            v-else
                            class="flex aspect-square w-full items-center justify-center rounded-2xl bg-muted text-muted-foreground"
                            aria-hidden="true"
                        >
                            <Blocks class="size-7" />
                        </div>

                        <div class="flex min-w-0 flex-col gap-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3
                                        class="text-base leading-tight font-extrabold sm:text-lg"
                                    >
                                        {{ gift.title }}
                                    </h3>
                                    <p
                                        v-if="gift.description"
                                        class="mt-1 text-sm leading-5 text-muted-foreground"
                                    >
                                        {{ gift.description }}
                                    </p>
                                </div>
                                <p
                                    class="shrink-0 text-base font-extrabold sm:text-lg"
                                >
                                    {{ formatEuro(gift.price) }}
                                </p>
                            </div>

                            <div v-if="gift.pledged > 0">
                                <div
                                    class="mb-1.5 flex items-center justify-between gap-3 text-xs text-muted-foreground"
                                >
                                    <span class="font-semibold">
                                        {{ formatEuro(gift.pledged) }}
                                        toegezegd
                                    </span>
                                    <span>
                                        Nog {{ formatEuro(gift.remaining) }}
                                    </span>
                                </div>
                                <div
                                    class="h-2 w-full overflow-hidden rounded-full bg-muted"
                                    role="progressbar"
                                    :aria-valuenow="progressPercentage(gift)"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                >
                                    <div
                                        class="h-full rounded-full bg-primary"
                                        :style="{
                                            width: `${progressPercentage(gift)}%`,
                                        }"
                                    />
                                </div>
                            </div>

                            <div
                                class="mt-auto flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <a
                                    v-if="gift.shop_url"
                                    :href="gift.shop_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                                >
                                    Bekijk in de webshop
                                    <ExternalLink class="size-3.5" />
                                </a>
                                <span v-else class="hidden sm:block"></span>

                                <Button
                                    as-child
                                    class="w-full rounded-full font-bold sm:w-auto sm:px-6"
                                >
                                    <Link
                                        :href="
                                            GiftContributionController.create({
                                                giftList: giftList.slug,
                                                gift: gift.id,
                                            })
                                        "
                                    >
                                        <ShoppingBag
                                            v-if="gift.allows_purchase"
                                            class="size-4"
                                        />
                                        <HandCoins v-else class="size-4" />
                                        {{
                                            gift.allows_purchase
                                                ? 'Kopen of bijdragen'
                                                : 'Bijdragen'
                                        }}
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-if="chosenGifts.length > 0" class="mt-8">
                <h3
                    v-if="!giftList.is_closed"
                    class="mb-3 text-sm font-bold text-muted-foreground"
                >
                    Al gekozen ({{ chosenGifts.length }})
                </h3>
                <ul
                    class="divide-y overflow-hidden rounded-3xl border-2 border-white/90 bg-card/70"
                >
                    <li
                        v-for="gift in chosenGifts"
                        :key="gift.id"
                        class="flex items-center gap-3 px-4 py-3 text-sm"
                    >
                        <img
                            v-if="gift.image_url"
                            :src="gift.image_url"
                            alt=""
                            class="size-10 shrink-0 rounded-xl object-cover opacity-70"
                        />
                        <div
                            v-else
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-muted text-muted-foreground"
                            aria-hidden="true"
                        >
                            <Blocks class="size-4" />
                        </div>
                        <span
                            class="min-w-0 flex-1 truncate font-semibold text-muted-foreground"
                        >
                            {{ gift.title }}
                        </span>
                        <Badge
                            variant="secondary"
                            class="shrink-0 rounded-full"
                        >
                            <CircleCheck class="size-3.5" />
                            {{
                                giftList.is_closed
                                    ? 'Afgesloten'
                                    : gift.status_label
                            }}
                        </Badge>
                    </li>
                </ul>
            </div>
        </section>
    </div>
</template>
