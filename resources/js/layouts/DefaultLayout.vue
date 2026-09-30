<template>
    <div class="ui-shell">
        <div
            v-if="sidebarOpen"
            class="ui-overlay"
            aria-hidden="true"
            @click="sidebarOpen = false"
        />

        <aside
            class="ui-sidebar"
            :class="{ 'ui-sidebar-hidden': !sidebarOpen }"
            aria-label="Main navigation"
        >
            <div class="ui-sidebar-brand">
                <p class="ui-sidebar-brand-title">
                    {{ merchant?.name ?? 'Billing & Metering' }}
                </p>
                <p class="ui-sidebar-brand-sub">Usage &amp; subscription platform</p>
            </div>

            <nav class="ui-sidebar-nav">
                <router-link
                    v-for="item in navItems"
                    :key="item.name"
                    :to="{ name: item.name }"
                    class="ui-nav-link"
                    :class="{ 'ui-nav-link-active': isActive(item) }"
                    @click="sidebarOpen = false"
                >
                    <svg
                        class="ui-nav-icon"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.5"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.iconPath" />
                    </svg>
                    {{ item.label }}
                </router-link>
            </nav>

            <div v-if="user" class="ui-sidebar-footer">
                <p class="truncate text-sm font-medium text-slate-900">{{ user.name }}</p>
                <p class="truncate text-xs text-slate-500">
                    {{ user.email }}
                    <span class="text-slate-400">· {{ user.role }}</span>
                </p>
                <button
                    type="button"
                    class="ui-btn-ghost mt-3 w-full justify-start px-2"
                    :disabled="loggingOut"
                    @click="handleLogout"
                >
                    {{ loggingOut ? 'Logging out…' : 'Sign out' }}
                </button>
            </div>
        </aside>

        <div class="ui-main">
            <header class="ui-topbar">
                <button
                    type="button"
                    class="ui-btn-secondary px-2.5"
                    aria-label="Open menu"
                    @click="sidebarOpen = true"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>
                </button>
                <span class="truncate text-sm font-semibold text-slate-900">
                    {{ currentTitle }}
                </span>
            </header>

            <main class="ui-content">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const route = useRoute();
const { user, merchant, logout } = useAuth();
const loggingOut = ref(false);
const sidebarOpen = ref(false);

const navItems = [
    {
        name: 'home',
        label: 'Dashboard',
        iconPath: 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z',
    },
    {
        name: 'plans.index',
        label: 'Plans',
        iconPath:
            'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
        name: 'customers.index',
        label: 'Customers',
        iconPath:
            'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    },
    {
        name: 'subscriptions.index',
        label: 'Subscriptions',
        iconPath:
            'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
    },
    {
        name: 'usage.daily',
        label: 'Usage',
        iconPath: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
    },
    {
        name: 'invoices.index',
        label: 'Invoices',
        iconPath:
            'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',
    },
] as const;

const titleByRoute: Record<string, string> = {
    home: 'Dashboard',
    'plans.index': 'Plans',
    'plans.create': 'Create plan',
    'plans.edit': 'Edit plan',
    'customers.index': 'Customers',
    'customers.create': 'Create customer',
    'customers.edit': 'Edit customer',
    'subscriptions.index': 'Subscriptions',
    'subscriptions.create': 'Create subscription',
    'subscriptions.show': 'Subscription',
    'usage.daily': 'Daily usage',
    'usage.record': 'Record usage',
    'invoices.index': 'Invoices',
    'invoices.show': 'Invoice',
};

const currentTitle = computed(() => {
    const name = route.name?.toString() ?? '';
    return titleByRoute[name] ?? 'Billing';
});

function isActive(item: (typeof navItems)[number]): boolean {
    const name = route.name?.toString() ?? '';
    const prefix = item.name.split('.')[0];
    if (prefix === 'usage') {
        return name === 'usage.daily' || name === 'usage.record';
    }
    if (item.name === 'home') {
        return name === 'home';
    }
    return name.startsWith(prefix);
}

async function handleLogout() {
    loggingOut.value = true;
    try {
        await logout();
        router.push({ name: 'login' });
    } finally {
        loggingOut.value = false;
    }
}
</script>
