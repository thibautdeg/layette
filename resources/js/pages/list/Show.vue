<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Gift, Share2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import FreeContributionController from '@/actions/App/Http/Controllers/FreeContributionController';
import GiftContributionController from '@/actions/App/Http/Controllers/GiftContributionController';
import { formatEuro } from '@/lib/money';
import { percentage, tileColor } from '@/lib/playful';

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
    return percentage(gift.pledged, gift.price);
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

type Filter = 'alles' | 'vrij' | 'samen' | 'geregeld';

const filter = ref<Filter>('alles');

const filters = computed<{ id: Filter; label: string; count: number }[]>(() => [
    { id: 'alles', label: 'Alles', count: props.gifts.length },
    {
        id: 'vrij',
        label: 'Nog vrij',
        count: availableGifts.value.filter((gift) => gift.pledged === 0).length,
    },
    {
        id: 'samen',
        label: 'Samen geven',
        count: availableGifts.value.filter((gift) => gift.pledged > 0).length,
    },
    {
        id: 'geregeld',
        label: 'Al geregeld',
        count: chosenGifts.value.length,
    },
]);

const visibleGifts = computed(() => {
    switch (filter.value) {
        case 'vrij':
            return availableGifts.value.filter((gift) => gift.pledged === 0);
        case 'samen':
            return availableGifts.value.filter((gift) => gift.pledged > 0);
        case 'geregeld':
            return chosenGifts.value;
        default:
            return [...availableGifts.value, ...chosenGifts.value];
    }
});

const listProgress = computed(() =>
    percentage(chosenGifts.value.length, props.gifts.length),
);

function tileFor(gift: GiftInfo): string {
    return tileColor(props.gifts.findIndex((item) => item.id === gift.id));
}

function statusPill(gift: GiftInfo): { label: string; class: string } {
    if (props.giftList.is_closed) {
        return { label: 'Afgesloten', class: 'bg-ink text-cream' };
    }

    if (!gift.is_available) {
        return { label: gift.status_label, class: 'bg-ink text-cream' };
    }

    if (gift.pledged > 0) {
        return {
            label: `Samen geven · ${progressPercentage(gift)}%`,
            class: 'bg-cream',
        };
    }

    return {
        label: gift.allows_purchase ? 'Nog vrij' : 'Samen geven',
        class: 'bg-mint',
    };
}

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
    <div class="flex flex-col gap-6">
        <Head :title="giftList.title" />

        <section
            class="relative mt-3 flex flex-col gap-4 rounded-[28px] bg-cream p-5 chunky-lg sm:p-7"
        >
            <span
                v-if="giftList.baby_gender"
                class="absolute -top-4 right-4 rounded-full bg-sky px-3.5 py-1 font-display text-[15px] font-semibold inked"
            >
                {{ giftList.baby_gender.emoji }}
                {{ giftList.baby_gender.label }}
            </span>

            <div class="flex items-center gap-4">
                <img
                    v-if="giftList.photo_url"
                    :src="giftList.photo_url"
                    alt=""
                    class="size-20 shrink-0 rounded-full object-cover inked sm:size-24"
                />
                <div class="flex min-w-0 flex-col gap-1">
                    <span
                        class="text-xs font-extrabold tracking-[0.1em] uppercase"
                    >
                        {{
                            giftList.baby_name
                                ? 'De babylijst van'
                                : 'Welkom op'
                        }}
                    </span>
                    <h1
                        class="font-display text-5xl leading-none font-bold break-words sm:text-6xl"
                    >
                        {{ giftList.baby_name ?? giftList.title }}
                    </h1>
                    <span
                        v-if="giftList.baby_name"
                        class="font-display text-lg font-semibold text-bark"
                    >
                        {{ giftList.title }}
                    </span>
                </div>
            </div>

            <p
                v-if="giftList.intro"
                class="leading-6 font-semibold whitespace-pre-line"
            >
                {{ giftList.intro }}
            </p>

            <div v-if="gifts.length > 0" class="flex flex-col gap-1.5">
                <div class="flex justify-between gap-3 text-sm font-extrabold">
                    <span>
                        {{ chosenGifts.length }} van {{ gifts.length }}
                        cadeautjes geregeld
                    </span>
                    <span>{{ listProgress }}%</span>
                </div>
                <div
                    class="h-4 overflow-hidden rounded-full bg-white inked"
                    role="progressbar"
                    :aria-valuenow="listProgress"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-label="Hoeveel cadeautjes al geregeld zijn"
                >
                    <div
                        class="h-full bg-tangerine"
                        :class="
                            listProgress > 0 && listProgress < 100
                                ? 'border-r-[2.5px] border-ink'
                                : ''
                        "
                        :style="{ width: `${listProgress}%` }"
                    />
                </div>
            </div>

            <button
                type="button"
                class="inline-flex h-11 pressable items-center justify-center gap-2 self-start rounded-2xl bg-white px-4 text-sm font-extrabold chunky-sm"
                @click="share"
            >
                <Share2 class="size-4" />
                Deel de lijst
            </button>
        </section>

        <section id="cadeaus" class="flex scroll-mt-20 flex-col gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-display text-2xl font-bold">Cadeautjes</h2>
                <p class="text-sm font-semibold text-bark">
                    {{ giftSectionIntro }}
                </p>
            </div>

            <div
                v-if="gifts.length > 0"
                class="flex flex-wrap items-center gap-2 pr-1 pb-1"
            >
                <button
                    v-for="option in filters"
                    :key="option.id"
                    type="button"
                    class="h-11 pressable rounded-full px-4 text-sm font-extrabold chunky-sm"
                    :class="
                        filter === option.id
                            ? 'bg-ink text-cream'
                            : 'bg-white text-ink'
                    "
                    :aria-pressed="filter === option.id"
                    @click="filter = option.id"
                >
                    {{ option.label }}
                    <span class="opacity-70">{{ option.count }}</span>
                </button>

                <select
                    v-if="availableGifts.length > 5 && filter !== 'geregeld'"
                    v-model="sortBy"
                    aria-label="Sorteren"
                    class="h-11 rounded-full px-3 text-sm sm:ml-auto"
                >
                    <option value="standaard">Onze volgorde</option>
                    <option value="prijs-op">Prijs: laag naar hoog</option>
                    <option value="prijs-af">Prijs: hoog naar laag</option>
                </select>
            </div>

            <p
                v-if="gifts.length > 0 && visibleGifts.length === 0"
                class="rounded-3xl border-dashed bg-white p-5 text-center text-sm font-bold inked"
            >
                Hier staat (nog) niets.
            </p>

            <div
                v-if="visibleGifts.length > 0"
                class="grid grid-cols-2 gap-3.5 sm:grid-cols-3"
            >
                <template v-for="gift in visibleGifts" :key="gift.id">
                    <component
                        :is="gift.is_available ? Link : 'div'"
                        :href="
                            gift.is_available
                                ? GiftContributionController.create({
                                      giftList: giftList.slug,
                                      gift: gift.id,
                                  })
                                : undefined
                        "
                        class="flex min-w-0 flex-col gap-2 rounded-[22px] bg-white p-2.5 text-ink"
                        :class="
                            gift.is_available
                                ? 'pressable chunky'
                                : 'border-dashed opacity-75 inked'
                        "
                    >
                        <div
                            class="flex h-28 items-center justify-center overflow-hidden rounded-2xl sm:h-32"
                            :class="
                                gift.is_available ? tileFor(gift) : 'bg-sand'
                            "
                        >
                            <img
                                v-if="gift.image_url"
                                :src="gift.image_url"
                                alt=""
                                class="size-full object-cover"
                                :class="gift.is_available ? '' : 'grayscale-50'"
                            />
                            <Gift
                                v-else
                                class="size-14"
                                :stroke-width="1.6"
                                aria-hidden="true"
                            />
                        </div>
                        <span
                            class="font-display text-[17px] leading-tight font-semibold break-words"
                        >
                            {{ gift.title }}
                        </span>
                        <span class="text-sm font-bold">
                            {{ formatEuro(gift.price) }}
                        </span>
                        <span
                            class="mt-auto max-w-full self-start rounded-full border-2 border-ink px-2.5 py-1 text-xs leading-tight font-extrabold"
                            :class="statusPill(gift).class"
                        >
                            {{ statusPill(gift).label }}
                        </span>
                    </component>
                </template>
            </div>
        </section>

        <Link
            v-if="!giftList.is_closed"
            :href="
                FreeContributionController.create({ giftList: giftList.slug })
            "
            class="flex h-15 pressable items-center justify-center gap-2.5 rounded-[20px] bg-sky px-4 text-center font-display text-lg font-semibold chunky"
        >
            <Gift class="size-5.5" :stroke-width="2.4" />
            Verras ons: zelf een bedrag kiezen
        </Link>
    </div>
</template>
