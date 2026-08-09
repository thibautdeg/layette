<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Gift, HandCoins, Heart, Landmark, Settings2 } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { index as bankIndex } from '@/routes/admin/bank';
import { index as contributionsIndex } from '@/routes/admin/contributions';
import { index as giftsIndex } from '@/routes/admin/gifts';
import { edit as settingsEdit } from '@/routes/admin/settings';
import type { NavItem } from '@/types';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() =>
    page.props.auth.is_admin
        ? [
              {
                  title: 'Bijdragen',
                  href: contributionsIndex(),
                  icon: HandCoins,
              },
              {
                  title: 'Cadeaus',
                  href: giftsIndex(),
                  icon: Gift,
              },
              {
                  title: 'Bank',
                  href: bankIndex(),
                  icon: Landmark,
              },
              {
                  title: 'Instellingen',
                  href: settingsEdit(),
                  icon: Settings2,
              },
          ]
        : [
              {
                  title: 'Mijn bijdragen',
                  href: '/mijn/bijdragen',
                  icon: Heart,
              },
          ],
);

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
