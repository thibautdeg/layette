<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { CalendarHeart, ExternalLink, Share2 } from '@lucide/vue';
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
    const shareData = {
        title: props.giftList.title,
        text: 'Neem een kijkje op onze geboortelijst!',
        url: window.location.href,
    };

    if (navigator.share) {
        try {
            await navigator.share(shareData);
        } catch {
            // Sharing was dismissed by the user.
        }

        return;
    }

    await navigator.clipboard.writeText(window.location.href);
    toast.success('Link gekopieerd! Plak hem in WhatsApp of een berichtje.');
}

const showOnlyAvailable = ref(false);
const sortBy = ref<'standaard' | 'prijs-op' | 'prijs-af'>('standaard');

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
    <Head :title="giftList.title" />

    <div class="grid gap-6 lg:grid-cols-[320px_1fr] lg:items-start">
        <aside class="flex flex-col gap-4 lg:sticky lg:top-6">
            <Card>
                <CardContent class="p-5 text-center">
                    <img
                        v-if="giftList.photo_url"
                        :src="giftList.photo_url"
                        alt=""
                        class="mx-auto mb-4 h-36 w-36 rounded-full object-cover shadow-lg ring-4 ring-primary/20"
                    />
                    <div v-else class="mb-3 text-4xl" aria-hidden="true">
                        🍼
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight">
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
                            class="inline-flex items-center gap-1.5 rounded-full bg-accent px-4 py-1.5 text-sm font-semibold text-accent-foreground"
                        >
                            {{ giftList.baby_gender.emoji }}
                            {{ giftList.baby_gender.label }}
                        </span>
                        <span
                            v-if="formattedExpectedAt()"
                            class="inline-flex items-center gap-1.5 rounded-full bg-secondary px-4 py-1.5 text-sm font-semibold text-secondary-foreground"
                        >
                            <CalendarHeart class="size-4" />
                            Verwacht rond {{ formattedExpectedAt() }}
                        </span>
                    </div>

                    <div v-if="countdown" class="mt-4">
                        <p class="text-sm font-semibold">{{ countdown }}</p>
                        <template v-if="giftList.pregnancy_week !== null">
                            <div
                                class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full bg-linear-to-r from-primary to-chart-1 transition-all"
                                    :style="{
                                        width: `${Math.round((giftList.pregnancy_week / 40) * 100)}%`,
                                    }"
                                />
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Week {{ giftList.pregnancy_week }} van 40
                            </p>
                        </template>
                    </div>
                    <p
                        v-if="giftList.intro"
                        class="mt-4 text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ giftList.intro }}
                    </p>
                    <p
                        v-if="giftList.is_closed"
                        class="mt-4 rounded-md bg-muted p-3 text-sm text-muted-foreground"
                    >
                        De lijst is afgesloten. Bedankt aan iedereen die iets
                        gaf!
                    </p>
                    <p
                        v-else-if="!isLoggedIn"
                        class="mt-4 rounded-md bg-muted p-3 text-left text-sm text-muted-foreground"
                    >
                        Iets gezien? Kies een cadeau en meld je aan (of maak in
                        een minuutje een account). Zo weten we van wie het komt
                        en kan je je bijdragen later opvolgen.
                    </p>

                    <div class="mt-4">
                        <Button
                            variant="outline"
                            size="sm"
                            class="rounded-full"
                            @click="share"
                        >
                            <Share2 class="size-4" />
                            Deel deze lijst
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="!giftList.is_closed" class="border-dashed">
                <CardContent
                    class="flex flex-col items-center gap-3 p-5 text-center"
                >
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-2xl"
                        aria-hidden="true"
                    >
                        💝
                    </div>
                    <h2 class="text-base leading-tight font-bold">
                        Vrije bijdrage
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Liever niet aan één cadeau gebonden? Geef gewoon iets
                        voor de spaarpot van de kleine.
                    </p>
                    <Button
                        as-child
                        variant="secondary"
                        class="w-full rounded-full font-bold"
                    >
                        <Link
                            :href="
                                FreeContributionController.create({
                                    giftList: giftList.slug,
                                })
                            "
                        >
                            💝 Geef een vrije bijdrage
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="flex flex-col gap-3 p-5">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl" aria-hidden="true">🔮</span>
                        <h2 class="text-base leading-tight font-bold">
                            Raad de naam
                        </h2>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Hoe zal
                        {{
                            giftList.baby_gender?.label === 'Jongen'
                                ? 'hij'
                                : giftList.baby_gender?.label === 'Meisje'
                                  ? 'ze'
                                  : 'het kindje'
                        }}
                        heten, denk je? Gok zelf of sluit je aan bij een gok van
                        iemand anders.
                    </p>

                    <div
                        v-if="nameGuesses.length > 0"
                        class="flex flex-wrap gap-2"
                    >
                        <button
                            v-for="guess in nameGuesses"
                            :key="guess.name"
                            type="button"
                            class="rounded-full border px-3 py-1 text-sm font-semibold transition-colors"
                            :class="
                                myNameGuess === guess.name
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'hover:bg-accent'
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
                        class="flex gap-2"
                        @submit.prevent="submitGuess()"
                    >
                        <Input
                            v-model="guessForm.name"
                            placeholder="Jouw gok…"
                            maxlength="50"
                            required
                        />
                        <Button
                            type="submit"
                            variant="secondary"
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
                        Jouw gok: <strong>{{ myNameGuess }}</strong> — je kan ze
                        altijd nog aanpassen.
                    </p>
                    <p v-if="!isLoggedIn" class="text-sm text-muted-foreground">
                        Meld je aan om mee te raden!
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

        <section>
            <div
                v-if="gifts.length > 5"
                class="mb-4 flex flex-wrap items-center justify-between gap-2"
            >
                <label
                    class="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground"
                >
                    <input
                        v-model="showOnlyAvailable"
                        type="checkbox"
                        class="size-4 rounded accent-primary"
                    />
                    Enkel wat nog openstaat
                </label>
                <select
                    v-model="sortBy"
                    class="h-8 rounded-full border border-input bg-transparent px-3 text-sm text-muted-foreground focus-visible:border-ring focus-visible:outline-none"
                >
                    <option value="standaard">Onze volgorde</option>
                    <option value="prijs-op">Prijs: laag naar hoog</option>
                    <option value="prijs-af">Prijs: hoog naar laag</option>
                </select>
            </div>

            <div class="flex flex-col gap-4">
                <Card
                    v-for="gift in visibleGifts"
                    :key="gift.id"
                    class="border-border/70 shadow-sm transition-shadow hover:shadow-md"
                    :class="{ 'opacity-70 saturate-50': !gift.is_available }"
                >
                    <CardContent class="flex gap-4 p-4 sm:p-5">
                        <img
                            v-if="gift.image_url"
                            :src="gift.image_url"
                            alt=""
                            class="h-24 w-24 shrink-0 rounded-xl object-cover"
                        />
                        <div
                            v-else
                            class="flex h-24 w-24 shrink-0 items-center justify-center rounded-xl bg-muted text-3xl"
                            aria-hidden="true"
                        >
                            🧸
                        </div>
                        <div class="flex min-w-0 flex-1 flex-col gap-2">
                            <div class="flex items-start justify-between gap-2">
                                <h2 class="text-base leading-tight font-bold">
                                    {{ gift.title }}
                                </h2>
                                <Badge
                                    v-if="gift.status === 'reserved'"
                                    variant="secondary"
                                    class="rounded-full"
                                    >🎀 Gereserveerd</Badge
                                >
                                <Badge
                                    v-else-if="gift.status === 'full'"
                                    variant="secondary"
                                    class="rounded-full"
                                    >🎉 Volzet</Badge
                                >
                            </div>

                            <p
                                v-if="gift.description"
                                class="text-sm text-muted-foreground"
                            >
                                {{ gift.description }}
                            </p>

                            <a
                                v-if="gift.shop_url"
                                :href="gift.shop_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                            >
                                Bekijk in de webshop
                                <ExternalLink class="size-3.5" />
                            </a>

                            <div v-if="gift.status !== 'reserved'" class="mt-1">
                                <div
                                    class="mb-1 flex items-baseline justify-between text-sm"
                                >
                                    <span class="font-medium">{{
                                        formatEuro(gift.price)
                                    }}</span>
                                    <span
                                        v-if="
                                            gift.remaining > 0 &&
                                            gift.pledged > 0
                                        "
                                        class="text-muted-foreground"
                                    >
                                        nog
                                        {{ formatEuro(gift.remaining) }} nodig
                                    </span>
                                </div>
                                <div
                                    class="h-2.5 w-full overflow-hidden rounded-full bg-muted"
                                >
                                    <div
                                        class="h-full rounded-full bg-linear-to-r from-primary to-chart-1 transition-all"
                                        :style="{
                                            width: `${progressPercentage(gift)}%`,
                                        }"
                                    />
                                </div>
                            </div>

                            <div v-if="gift.is_available" class="mt-2">
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
                                        🎁
                                        {{
                                            gift.allows_purchase
                                                ? 'Bijdragen of zelf kopen'
                                                : 'Bijdragen'
                                        }}
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </section>
    </div>
</template>
