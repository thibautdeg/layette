<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogOut } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { logout } from '@/routes';
import { contributions as accountContributions } from '@/routes/account';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user);

const navItems = computed<NavItem[]>(() => [
    { title: 'Mijn bijdragen', href: accountContributions() },
    { title: 'Mijn gegevens', href: editProfile() },
    ...(page.props.auth.has_password
        ? [{ title: 'Wachtwoord', href: editSecurity() }]
        : []),
]);

const { isCurrentOrParentUrl } = useCurrentUrl();

function handleLogout() {
    router.flushAll();
}
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="font-display text-4xl font-bold">Mijn account</h1>
            <p class="mt-1 truncate text-sm font-semibold text-bark">
                {{ user?.name }} · {{ user?.email }}
            </p>
        </div>

        <nav
            class="mb-6 flex gap-1 overflow-x-auto rounded-full bg-white p-1 chunky-sm"
            aria-label="Account"
        >
            <Link
                v-for="item in navItems"
                :key="toUrl(item.href)"
                :href="item.href"
                class="flex-1 rounded-full px-4 py-2 text-center text-sm font-bold whitespace-nowrap transition-colors"
                :class="
                    isCurrentOrParentUrl(item.href)
                        ? 'bg-ink text-cream'
                        : 'text-ink hover:bg-cream'
                "
            >
                {{ item.title }}
            </Link>
        </nav>

        <slot />

        <div
            class="mt-10 flex justify-center border-t-2 border-dashed border-ink/30 pt-6"
        >
            <Button as-child variant="outline" class="rounded-full bg-card">
                <Link :href="logout()" as="button" @click="handleLogout">
                    <LogOut class="size-4" />
                    Afmelden
                </Link>
            </Button>
        </div>
    </div>
</template>
