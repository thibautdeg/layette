<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronDown,
    CircleUser,
    HandHeart,
    KeyRound,
    LayoutDashboard,
    LogOut,
    UserPen,
} from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import { home, login, logout } from '@/routes';
import { contributions as accountContributions } from '@/routes/account';
import { index as adminContributions } from '@/routes/admin/contributions';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

withDefaults(
    defineProps<{
        wide?: boolean;
    }>(),
    {
        wide: false,
    },
);

const page = usePage();
const user = computed(() => page.props.auth.user);
const firstName = computed(() => user.value?.name?.split(' ')[0] ?? '');

function handleLogout() {
    router.flushAll();
}
</script>

<template>
    <div
        class="public-theme relative min-h-screen overflow-x-hidden bg-background font-baby text-foreground"
    >
        <header class="relative z-30">
            <div
                class="mx-auto flex h-20 w-full items-center justify-between gap-3 px-5"
                :class="wide ? 'max-w-3xl' : 'max-w-2xl'"
            >
                <Link
                    :href="home()"
                    class="font-display text-[2rem] leading-none font-bold tracking-tight text-ink"
                >
                    layette
                </Link>

                <DropdownMenu v-if="user">
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="inline-flex h-11 pressable items-center gap-2 rounded-2xl bg-white px-3 text-sm font-extrabold chunky-sm"
                            data-test="account-menu"
                        >
                            <CircleUser class="size-5" />
                            <span class="max-w-32 truncate">{{
                                firstName
                            }}</span>
                            <ChevronDown class="size-4" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="end"
                        class="public-theme w-60 rounded-2xl bg-white p-1.5 font-baby"
                    >
                        <DropdownMenuLabel class="font-normal">
                            <p class="truncate font-extrabold">
                                {{ user.name }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ user.email }}
                            </p>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator class="bg-ink/15" />
                        <DropdownMenuItem as-child>
                            <Link
                                :href="accountContributions()"
                                class="w-full cursor-pointer rounded-xl font-bold"
                            >
                                <HandHeart class="size-4" />
                                Mijn bijdragen
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <Link
                                :href="editProfile()"
                                class="w-full cursor-pointer rounded-xl font-bold"
                            >
                                <UserPen class="size-4" />
                                Mijn gegevens
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-if="page.props.auth.has_password"
                            as-child
                        >
                            <Link
                                :href="editSecurity()"
                                class="w-full cursor-pointer rounded-xl font-bold"
                            >
                                <KeyRound class="size-4" />
                                Wachtwoord
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-if="page.props.auth.is_admin"
                            as-child
                        >
                            <Link
                                :href="adminContributions()"
                                class="w-full cursor-pointer rounded-xl font-bold"
                            >
                                <LayoutDashboard class="size-4" />
                                Beheer
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator class="bg-ink/15" />
                        <DropdownMenuItem as-child>
                            <Link
                                :href="logout()"
                                as="button"
                                class="w-full cursor-pointer rounded-xl font-bold"
                                data-test="logout-button"
                                @click="handleLogout"
                            >
                                <LogOut class="size-4" />
                                Afmelden
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <Link
                    v-else
                    :href="login()"
                    class="inline-flex h-11 pressable items-center gap-2 rounded-2xl bg-white px-4 text-sm font-extrabold chunky-sm"
                >
                    <CircleUser class="size-5" />
                    Aanmelden
                </Link>
            </div>
        </header>

        <main
            class="relative mx-auto w-full px-5 pt-2 pb-10"
            :class="wide ? 'max-w-3xl' : 'max-w-2xl'"
        >
            <slot />
        </main>

        <footer
            class="relative pb-10 text-center font-display text-base font-semibold text-bark"
        >
            Met liefde gemaakt voor ons kleintje
        </footer>

        <Toaster />
    </div>
</template>
