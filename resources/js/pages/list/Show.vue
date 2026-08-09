<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CalendarHeart, ExternalLink } from '@lucide/vue';
import { computed } from 'vue';
import GiftContributionController from '@/actions/App/Http/Controllers/GiftContributionController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatEuro } from '@/lib/money';

const page = usePage();
const isLoggedIn = computed(() => page.props.auth.user !== null);

interface GiftListInfo {
    slug: string;
    title: string;
    intro: string | null;
    photo_url: string | null;
    expected_at: string | null;
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

function formattedExpectedAt(): string | null {
    if (!props.giftList.expected_at) {
        return null;
    }

    return new Intl.DateTimeFormat('nl-BE', { dateStyle: 'long' }).format(
        new Date(props.giftList.expected_at),
    );
}
</script>

<template>
    <Head :title="giftList.title" />

    <header class="mb-8 text-center">
        <img
            v-if="giftList.photo_url"
            :src="giftList.photo_url"
            alt=""
            class="mx-auto mb-4 h-40 w-40 rounded-full object-cover shadow-md"
        />
        <h1 class="text-3xl font-bold tracking-tight">{{ giftList.title }}</h1>
        <p
            v-if="formattedExpectedAt()"
            class="mt-2 inline-flex items-center gap-1.5 text-sm text-muted-foreground"
        >
            <CalendarHeart class="size-4" />
            Verwacht rond {{ formattedExpectedAt() }}
        </p>
        <p
            v-if="giftList.intro"
            class="mx-auto mt-4 max-w-prose whitespace-pre-line text-muted-foreground"
        >
            {{ giftList.intro }}
        </p>
        <p
            v-if="giftList.is_closed"
            class="mx-auto mt-4 max-w-prose rounded-md bg-muted p-3 text-sm text-muted-foreground"
        >
            De lijst is afgesloten. Bedankt aan iedereen die iets gaf!
        </p>
        <p
            v-else-if="!isLoggedIn"
            class="mx-auto mt-4 max-w-prose rounded-md bg-muted p-3 text-sm text-muted-foreground"
        >
            Iets gezien? Kies een cadeau en meld je aan (of maak in een minuutje
            een account). Zo weten we van wie het komt en kan je je bijdragen
            later opvolgen.
        </p>
    </header>

    <div class="flex flex-col gap-4">
        <Card
            v-for="gift in gifts"
            :key="gift.id"
            :class="{ 'opacity-70': !gift.is_available }"
        >
            <CardContent class="flex gap-4 p-4">
                <img
                    v-if="gift.image_url"
                    :src="gift.image_url"
                    alt=""
                    class="h-24 w-24 shrink-0 rounded-md object-cover"
                />
                <div class="flex min-w-0 flex-1 flex-col gap-2">
                    <div class="flex items-start justify-between gap-2">
                        <h2 class="leading-tight font-semibold">
                            {{ gift.title }}
                        </h2>
                        <Badge
                            v-if="gift.status === 'reserved'"
                            variant="secondary"
                            >Gereserveerd</Badge
                        >
                        <Badge
                            v-else-if="gift.status === 'full'"
                            variant="secondary"
                            >Volzet</Badge
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
                                v-if="gift.remaining > 0 && gift.pledged > 0"
                                class="text-muted-foreground"
                            >
                                nog {{ formatEuro(gift.remaining) }} nodig
                            </span>
                        </div>
                        <div
                            class="h-2 w-full overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full bg-primary transition-all"
                                :style="{
                                    width: `${progressPercentage(gift)}%`,
                                }"
                            />
                        </div>
                    </div>

                    <div v-if="gift.is_available" class="mt-2">
                        <Button as-child class="w-full sm:w-auto">
                            <Link
                                :href="
                                    GiftContributionController.create({
                                        giftList: giftList.slug,
                                        gift: gift.id,
                                    })
                                "
                            >
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

    <footer class="mt-10 text-center text-sm text-muted-foreground">
        <Link
            href="/mijn"
            class="underline underline-offset-4 hover:text-foreground"
        >
            Mijn bijdragen opvolgen
        </Link>
    </footer>
</template>
