<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowDown,
    BadgeCheck,
    Baby,
    Blocks,
    CalendarHeart,
    CircleCheck,
    ExternalLink,
    HandCoins,
    ListChecks,
    Milk,
    Share2,
    ShoppingBag,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import FreeContributionController from '@/actions/App/Http/Controllers/FreeContributionController';
import GiftContributionController from '@/actions/App/Http/Controllers/GiftContributionController';
import NameGuessController from '@/actions/App/Http/Controllers/NameGuessController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatEuro } from '@/lib/money';

defineOptions({
    layout: {
        wide: true,
    },
});

const page = usePage();
const isLoggedIn = computed(() => page.props.auth.user !== null);

interface GiftListInfo {
    slug: string;
    title: string;
    baby_name: string | null;
    baby_gender: { label: string; emoji: string } | null;
    intro: string | null;
    photo_url: string | null;
    expected_at: string | null;
    days_until_due: number | null;
    pregnancy_week: number | null;
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
    nameGuesses: { name: string; count: number }[];
    myNameGuess: string | null;
}>();

const guessForm = useForm({
    name: props.myNameGuess ?? '',
});

function submitGuess(name?: string) {
    if (name !== undefined) {
        guessForm.name = name;
    }

    guessForm.post(
        NameGuessController.store.url({ giftList: props.giftList.slug }),
        { preserveScroll: true },
    );
}

function progressPercentage(gift: GiftInfo): number {
    if (gift.price === 0) {
        return 0;
    }

    return Math.min(100, Math.round((gift.pledged / gift.price) * 100));
}

function formattedExpectedAt(): string | null {
    if (!props.giftList.expected_at) {
        return null;
    }

    return new Intl.DateTimeFormat('nl-BE', { dateStyle: 'long' }).format(
        new Date(props.giftList.expected_at),
    );
}

const countdown = computed(() => {
    const days = props.giftList.days_until_due;

    if (days === null) {
        return props.giftList.expected_at
            ? 'Het kan nu elk moment zijn! 🎉'
            : null;
    }

    if (days === 0) {
        return 'Vandaag uitgerekend! 🎉';
    }

    const weeks = Math.floor(days / 7);
    const restDays = days % 7;
    const parts = [];

    if (weeks > 0) {
        parts.push(`${weeks} ${weeks === 1 ? 'week' : 'weken'}`);
    }

    if (restDays > 0) {
        parts.push(`${restDays} ${restDays === 1 ? 'dag' : 'dagen'}`);
    }

    return `Nog ${parts.join(' en ')} te gaan`;
});

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

const showOnlyAvailable = ref(false);
const sortBy = ref<'standaard' | 'prijs-op' | 'prijs-af'>('standaard');

const availableGiftCount = computed(
    () => props.gifts.filter((gift) => gift.is_available).length,
);

const giftSectionIntro = computed(() => {
    if (props.giftList.is_closed) {
        return 'Kijk nog even mee naar wat gekozen werd.';
    }

    if (props.gifts.length === 0) {
        return 'Er staan nog geen cadeaus op de lijst.';
    }

    if (availableGiftCount.value === 0) {
        return 'Alles is gekozen. Een vrije bijdrage kan nog.';
    }

    return 'Kies een cadeautje of leg samen bij.';
});

const giftTileClasses = [
    'bg-chart-1/25 text-[hsl(340_60%_45%)]',
    'bg-chart-2/25 text-[hsl(172_55%_28%)]',
    'bg-chart-3/25 text-[hsl(203_70%_34%)]',
    'bg-chart-4/30 text-[hsl(40_80%_32%)]',
];

function giftTileClass(index: number): string {
    return giftTileClasses[index % giftTileClasses.length];
}

const visibleGifts = computed(() => {
    let gifts = props.gifts;

    if (showOnlyAvailable.value) {
        gifts = gifts.filter((gift) => gift.is_available);
    }

    if (sortBy.value === 'prijs-op') {
        gifts = [...gifts].sort((a, b) => a.price - b.price);
    } else if (sortBy.value === 'prijs-af') {
        gifts = [...gifts].sort((a, b) => b.price - a.price);
    }

    return gifts;
});
</script>

<template>
    <div>
        <Head :title="giftList.title" />

        <div
            class="grid gap-5 lg:grid-cols-[300px_minmax(0,1fr)] lg:items-start"
        >
            <aside class="flex flex-col gap-4 lg:sticky lg:top-6">
                <Card
                    class="overflow-hidden rounded-3xl border-2 border-white/90 bg-card/95 py-0 shadow-xl shadow-primary/10 backdrop-blur"
                >
                    <CardContent class="p-0">
                        <div
                            class="relative bg-chart-1/15 px-5 pt-6 pb-4 text-center"
                        >
                            <img
                                v-if="giftList.photo_url"
                                :src="giftList.photo_url"
                                alt=""
                                class="mx-auto mb-3 size-24 rounded-full object-cover shadow-lg ring-4 ring-white"
                            />
                            <div
                                v-else
                                class="mx-auto mb-3 flex size-16 items-center justify-center rounded-full bg-white text-primary shadow-md ring-2 ring-chart-1/40"
                                aria-hidden="true"
                            >
                                <Milk class="size-8" />
                            </div>

                            <p
                                class="mb-2 inline-flex -rotate-1 items-center gap-1.5 rounded-full bg-white/90 px-3 py-1 text-xs font-extrabold text-primary shadow-sm"
                            >
                                <Baby class="size-3.5" />
                                Mini onderweg
                            </p>
                            <h1
                                class="font-display text-3xl leading-tight font-bold tracking-tight"
                            >
                                {{ giftList.baby_name ?? giftList.title }}
                            </h1>
                            <p
                                v-if="giftList.baby_name"
                                class="mt-1 text-sm text-muted-foreground"
                            >
                                {{ giftList.title }}
                            </p>

                            <div
                                class="mt-3 flex flex-wrap items-center justify-center gap-2"
                            >
                                <span
                                    v-if="giftList.baby_gender"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-accent px-3 py-1 text-sm font-bold text-accent-foreground shadow-sm"
                                >
                                    {{ giftList.baby_gender.emoji }}
                                    {{ giftList.baby_gender.label }}
                                </span>
                                <span
                                    v-if="formattedExpectedAt()"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-secondary px-3 py-1 text-sm font-bold text-secondary-foreground shadow-sm"
                                >
                                    <CalendarHeart class="size-4" />
                                    Verwacht rond {{ formattedExpectedAt() }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-4 p-4">
                            <div
                                v-if="countdown"
                                class="rounded-2xl border-2 border-chart-4/40 bg-chart-4/10 p-3"
                            >
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <p class="text-sm font-bold">
                                        {{ countdown }}
                                    </p>
                                    <p
                                        v-if="giftList.pregnancy_week !== null"
                                        class="text-xs whitespace-nowrap text-muted-foreground"
                                    >
                                        Week {{ giftList.pregnancy_week }} van
                                        40
                                    </p>
                                </div>
                                <div
                                    v-if="giftList.pregnancy_week !== null"
                                    class="h-3 w-full overflow-hidden rounded-full bg-white/80"
                                    role="progressbar"
                                    :aria-valuenow="giftList.pregnancy_week"
                                    aria-valuemin="0"
                                    aria-valuemax="40"
                                >
                                    <div
                                        class="h-full rounded-full bg-primary transition-all"
                                        :style="{
                                            width: `${Math.round((giftList.pregnancy_week / 40) * 100)}%`,
                                        }"
                                    />
                                </div>
                            </div>

                            <p
                                v-if="giftList.intro"
                                class="text-sm leading-5 whitespace-pre-line text-muted-foreground"
                            >
                                {{ giftList.intro }}
                            </p>

                            <div
                                v-if="giftList.is_closed"
                                class="rounded-2xl border-2 border-dashed border-border bg-muted/70 p-4 text-sm text-muted-foreground"
                            >
                                De lijst is afgesloten. Dankjewel voor alle
                                liefde.
                            </div>
                            <div
                                v-else-if="!isLoggedIn"
                                class="rounded-2xl border-2 border-dashed border-border bg-muted/70 p-3 text-sm leading-5 text-muted-foreground"
                            >
                                Kies iets liefs, aanmelden doen we pas daarna.
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <Button as-child class="rounded-full font-bold">
                                    <a href="#cadeaus">
                                        <ListChecks class="size-4" />
                                        Cadeaus
                                        <ArrowDown class="size-4" />
                                    </a>
                                </Button>
                                <Button
                                    variant="outline"
                                    class="rounded-full bg-background/70"
                                    @click="share"
                                >
                                    <Share2 class="size-4" />
                                    Delen
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card
                    v-if="!giftList.is_closed"
                    class="rounded-3xl border-2 border-dashed border-secondary-foreground/25 bg-secondary/70 py-0 shadow-sm"
                >
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-11 shrink-0 items-center justify-center rounded-full bg-white text-xl shadow-sm"
                                aria-hidden="true"
                            >
                                🐷
                            </div>
                            <div class="min-w-0">
                                <h2
                                    class="font-display text-lg leading-tight font-bold"
                                >
                                    Spaarpotje
                                </h2>
                                <p
                                    class="mt-0.5 text-sm leading-5 text-muted-foreground"
                                >
                                    Voor luiers, knuffels en later.
                                </p>
                            </div>
                        </div>
                        <Button
                            as-child
                            variant="secondary"
                            size="sm"
                            class="mt-3 w-full rounded-full bg-white/90 font-bold shadow-sm hover:bg-white"
                        >
                            <Link
                                :href="
                                    FreeContributionController.create({
                                        giftList: giftList.slug,
                                    })
                                "
                            >
                                <HandCoins class="size-4" />
                                Geef vrij
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <Card
                    class="rounded-3xl border-2 border-white/90 bg-accent/45 py-0 shadow-sm"
                >
                    <CardContent class="flex flex-col gap-3 p-4">
                        <div class="flex items-start gap-3">
                            <div
                                class="flex size-11 shrink-0 items-center justify-center rounded-full bg-white text-xl shadow-sm"
                                aria-hidden="true"
                            >
                                🔮
                            </div>
                            <div>
                                <h2
                                    class="font-display text-lg leading-tight font-bold"
                                >
                                    Raad de naam
                                </h2>
                                <p
                                    class="mt-0.5 text-sm leading-5 text-muted-foreground"
                                >
                                    Heb jij een gokje?
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="nameGuesses.length > 0"
                            class="flex flex-wrap gap-2"
                        >
                            <button
                                v-for="guess in nameGuesses"
                                :key="guess.name"
                                type="button"
                                class="rounded-full border px-3 py-1.5 text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-60"
                                :class="
                                    myNameGuess === guess.name
                                        ? 'border-primary bg-primary/10 text-primary'
                                        : 'border-white bg-white/80 shadow-sm hover:bg-white'
                                "
                                :disabled="!isLoggedIn || guessForm.processing"
                                @click="submitGuess(guess.name)"
                            >
                                {{ guess.name }}
                                <span
                                    v-if="guess.count > 1"
                                    class="text-muted-foreground"
                                    >×{{ guess.count }}</span
                                >
                            </button>
                        </div>

                        <form
                            v-if="isLoggedIn"
                            class="grid gap-2 sm:grid-cols-[1fr_auto] lg:grid-cols-[1fr_auto]"
                            @submit.prevent="submitGuess()"
                        >
                            <Input
                                v-model="guessForm.name"
                                placeholder="Jouw gok..."
                                maxlength="50"
                                required
                                class="bg-background/80"
                            />
                            <Button
                                type="submit"
                                variant="secondary"
                                size="sm"
                                class="rounded-full font-bold"
                                :disabled="guessForm.processing"
                            >
                                Gok!
                            </Button>
                        </form>
                        <InputError :message="guessForm.errors.name" />
                        <p
                            v-if="isLoggedIn && myNameGuess"
                            class="text-sm text-muted-foreground"
                        >
                            Jouw gok: <strong>{{ myNameGuess }}</strong
                            >.
                        </p>
                        <p
                            v-if="!isLoggedIn"
                            class="text-sm text-muted-foreground"
                        >
                            Meld je aan om mee te raden.
                        </p>
                    </CardContent>
                </Card>

                <p class="text-center text-sm text-muted-foreground">
                    <Link
                        href="/mijn"
                        class="underline underline-offset-4 hover:text-foreground"
                    >
                        Mijn bijdragen opvolgen
                    </Link>
                </p>
            </aside>

            <section id="cadeaus" class="min-w-0 scroll-mt-6">
                <div
                    class="mb-4 rounded-3xl border-2 border-white/90 bg-card/90 p-4 shadow-sm backdrop-blur"
                >
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0">
                            <p
                                class="inline-flex -rotate-1 items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-extrabold text-primary"
                            >
                                <Blocks class="size-3.5" />
                                Cadeautjes
                            </p>
                            <h2
                                class="mt-2 font-display text-3xl font-bold tracking-tight underline decoration-chart-4 decoration-wavy decoration-3 underline-offset-6"
                            >
                                Voor de kleine spruit
                            </h2>
                            <p
                                class="mt-1 max-w-2xl text-sm text-muted-foreground"
                            >
                                {{ giftSectionIntro }}
                            </p>
                        </div>

                        <div
                            v-if="gifts.length > 5"
                            class="flex flex-wrap items-center gap-2 sm:justify-end"
                        >
                            <label
                                class="flex h-10 cursor-pointer items-center gap-2 rounded-full border bg-background/80 px-3 text-sm text-muted-foreground"
                            >
                                <input
                                    v-model="showOnlyAvailable"
                                    type="checkbox"
                                    class="size-4 rounded accent-primary"
                                />
                                Enkel open
                            </label>
                            <select
                                v-model="sortBy"
                                class="h-10 rounded-full border border-input bg-background/80 px-3 text-sm text-muted-foreground focus-visible:border-ring focus-visible:outline-none"
                            >
                                <option value="standaard">Onze volgorde</option>
                                <option value="prijs-op">
                                    Prijs: laag naar hoog
                                </option>
                                <option value="prijs-af">
                                    Prijs: hoog naar laag
                                </option>
                            </select>
                        </div>
                        <div
                            v-else
                            class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                        >
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-secondary px-3 py-1.5 font-semibold text-secondary-foreground"
                            >
                                <BadgeCheck class="size-4" />
                                {{ availableGiftCount }} open
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <Card
                        v-for="(gift, index) in visibleGifts"
                        :key="gift.id"
                        class="group overflow-hidden rounded-3xl border-2 border-white/90 bg-card/95 py-0 shadow-sm transition-all hover:-translate-y-1 hover:shadow-lg hover:shadow-primary/10"
                        :class="{
                            'opacity-75 saturate-75':
                                !gift.is_available && !giftList.is_closed,
                        }"
                    >
                        <CardContent
                            class="grid grid-cols-[5rem_minmax(0,1fr)] gap-3 p-3 sm:p-4"
                        >
                            <div class="relative">
                                <img
                                    v-if="gift.image_url"
                                    :src="gift.image_url"
                                    alt=""
                                    class="size-20 rounded-2xl object-cover ring-2 ring-white transition-transform group-hover:-rotate-1"
                                />
                                <div
                                    v-else
                                    class="flex size-20 items-center justify-center rounded-2xl transition-transform group-hover:-rotate-1"
                                    :class="giftTileClass(index)"
                                    aria-hidden="true"
                                >
                                    <Blocks class="size-8" />
                                </div>
                            </div>

                            <div class="min-w-0">
                                <div
                                    class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <h3
                                                class="text-base leading-tight font-extrabold sm:text-lg"
                                            >
                                                {{ gift.title }}
                                            </h3>
                                            <Badge
                                                v-if="!gift.is_available"
                                                variant="secondary"
                                                class="rounded-full"
                                            >
                                                <CircleCheck class="size-3.5" />
                                                {{
                                                    giftList.is_closed
                                                        ? 'Afgesloten'
                                                        : gift.status_label
                                                }}
                                            </Badge>
                                        </div>

                                        <p
                                            v-if="gift.description"
                                            class="mt-1 text-sm leading-5 text-muted-foreground"
                                        >
                                            {{ gift.description }}
                                        </p>
                                    </div>

                                    <div class="shrink-0">
                                        <p
                                            class="inline-flex -rotate-1 rounded-full bg-chart-4/30 px-3 py-1 text-sm font-extrabold shadow-sm"
                                        >
                                            {{ formatEuro(gift.price) }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="
                                        gift.status !== 'reserved' &&
                                        gift.price > 0
                                    "
                                    class="mt-3"
                                >
                                    <div
                                        class="mb-1.5 flex items-center justify-between gap-3 text-xs text-muted-foreground"
                                    >
                                        <span
                                            v-if="gift.pledged > 0"
                                            class="font-semibold"
                                        >
                                            {{ formatEuro(gift.pledged) }}
                                            toegezegd
                                        </span>
                                        <span v-else class="font-semibold">
                                            Nog open
                                        </span>
                                        <span v-if="gift.remaining > 0">
                                            Nog {{ formatEuro(gift.remaining) }}
                                        </span>
                                        <span v-else>Dankjewel!</span>
                                    </div>
                                    <div
                                        class="h-2.5 w-full overflow-hidden rounded-full bg-muted"
                                        role="progressbar"
                                        :aria-valuenow="
                                            progressPercentage(gift)
                                        "
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                    >
                                        <div
                                            class="h-full rounded-full bg-primary transition-all"
                                            :style="{
                                                width: `${progressPercentage(gift)}%`,
                                            }"
                                        />
                                    </div>
                                </div>

                                <div
                                    class="mt-3 flex flex-col gap-2 border-t pt-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <a
                                        v-if="gift.shop_url"
                                        :href="gift.shop_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted-foreground underline underline-offset-4 hover:text-foreground"
                                    >
                                        Webshop
                                        <ExternalLink class="size-3.5" />
                                    </a>
                                    <span v-else class="hidden sm:block"></span>

                                    <Button
                                        v-if="gift.is_available"
                                        as-child
                                        size="sm"
                                        class="w-full rounded-full font-bold sm:w-auto sm:px-5"
                                    >
                                        <Link
                                            :href="
                                                GiftContributionController.create(
                                                    {
                                                        giftList: giftList.slug,
                                                        gift: gift.id,
                                                    },
                                                )
                                            "
                                        >
                                            <ShoppingBag
                                                v-if="gift.allows_purchase"
                                                class="size-4"
                                            />
                                            <HandCoins v-else class="size-4" />
                                            {{
                                                gift.allows_purchase
                                                    ? 'Bijdragen of kopen'
                                                    : 'Bijdragen'
                                            }}
                                        </Link>
                                    </Button>
                                    <div
                                        v-else
                                        class="inline-flex items-center justify-center gap-2 rounded-full bg-muted px-3 py-1.5 text-sm font-bold text-muted-foreground"
                                    >
                                        <CircleCheck class="size-4" />
                                        {{
                                            giftList.is_closed
                                                ? 'Lijst afgesloten'
                                                : gift.status_label
                                        }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </section>
        </div>
    </div>
</template>
