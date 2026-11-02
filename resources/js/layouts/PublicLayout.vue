<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronDown,
    CircleUser,
    HandHeart,
    KeyRound,
    LayoutDashboard,
    LogOut,
    Milk,
    UserPen,
} from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
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
        <header
            class="sticky top-0 z-30 border-b border-border/70 bg-background/85 backdrop-blur"
        >
            <div
                class="mx-auto flex h-14 w-full items-center justify-between gap-3 px-4"
                :class="wide ? 'max-w-3xl' : 'max-w-2xl'"
            >
                <Link
                    :href="home()"
                    class="inline-flex items-center gap-2 font-display text-lg font-bold"
                >
                    <Milk class="size-5 text-primary" />
                    Geboortelijst
                </Link>

                <DropdownMenu v-if="user">
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="outline"
                            size="sm"
                            class="rounded-full bg-card"
                            data-test="account-menu"
                        >
                            <CircleUser class="size-4" />
                            <span class="max-w-32 truncate">{{
                                firstName
                            }}</span>
                            <ChevronDown class="size-4 text-muted-foreground" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="end"
                        class="public-theme w-56 font-baby"
                    >
                        <DropdownMenuLabel class="font-normal">
                            <p class="truncate font-bold">{{ user.name }}</p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ user.email }}
                            </p>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem as-child>
                            <Link
                                :href="accountContributions()"
                                class="w-full cursor-pointer"
                            >
                                <HandHeart class="size-4" />
                                Mijn bijdragen
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <Link
                                :href="editProfile()"
                                class="w-full cursor-pointer"
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
                                class="w-full cursor-pointer"
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
                                class="w-full cursor-pointer"
                            >
                                <LayoutDashboard class="size-4" />
                                Beheer
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem as-child>
                            <Link
                                :href="logout()"
                                as="button"
                                class="w-full cursor-pointer"
                                data-test="logout-button"
                                @click="handleLogout"
                            >
                                <LogOut class="size-4" />
                                Afmelden
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <Button
                    v-else
                    as-child
                    variant="outline"
                    size="sm"
                    class="rounded-full bg-card"
                >
                    <Link :href="login()">
                        <CircleUser class="size-4" />
                        Aanmelden
                    </Link>
                </Button>
            </div>
        </header>

        <main
            class="relative mx-auto w-full px-4 py-8 sm:py-10"
            :class="wide ? 'max-w-3xl' : 'max-w-2xl'"
        >
            <slot />
        </main>

        <footer class="relative pb-8 text-center text-sm text-muted-foreground">
            Met liefde gemaakt voor ons kleintje
        </footer>

        <Toaster />
    </div>
</template>
