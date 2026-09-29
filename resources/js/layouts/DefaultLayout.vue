<template>
    <div class="min-h-screen bg-gray-50">
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <h1 class="text-lg font-semibold text-gray-900">
                        {{ merchant?.name ?? 'Subscription Billing & Usage Metering' }}
                    </h1>
                    <nav class="flex items-center gap-4 text-sm">
                        <router-link
                            :to="{ name: 'home' }"
                            class="text-gray-600 hover:text-gray-900"
                        >
                            Dashboard
                        </router-link>
                        <router-link
                            :to="{ name: 'plans.index' }"
                            class="text-gray-600 hover:text-gray-900"
                        >
                            Plans
                        </router-link>
                        <router-link
                            :to="{ name: 'customers.index' }"
                            class="text-gray-600 hover:text-gray-900"
                        >
                            Customers
                        </router-link>
                        <router-link
                            :to="{ name: 'subscriptions.index' }"
                            class="text-gray-600 hover:text-gray-900"
                        >
                            Subscriptions
                        </router-link>
                        <router-link
                            :to="{ name: 'usage.daily' }"
                            class="text-gray-600 hover:text-gray-900"
                        >
                            Usage
                        </router-link>
                    </nav>
                </div>
                <div v-if="user" class="flex items-center gap-4 text-sm">
                    <span class="text-gray-600">
                        {{ user.name }}
                        <span class="text-xs text-gray-400 ml-1">({{ user.role }})</span>
                    </span>
                    <button
                        class="text-red-600 hover:text-red-800 font-medium"
                        :disabled="loggingOut"
                        @click="handleLogout"
                    >
                        {{ loggingOut ? 'Logging out…' : 'Logout' }}
                    </button>
                </div>
            </div>
        </header>
        <main class="max-w-7xl mx-auto px-4 py-6">
            <router-view />
        </main>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const { user, merchant, logout } = useAuth();
const loggingOut = ref(false);

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
