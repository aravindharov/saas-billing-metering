<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Dashboard</h2>
        <div v-if="merchant" class="bg-white shadow rounded-lg p-6 space-y-2">
            <p class="text-gray-700">
                <span class="font-medium">Merchant:</span> {{ merchant.name }}
            </p>
            <p class="text-gray-700">
                <span class="font-medium">Signed in as:</span> {{ user?.name }} ({{ user?.email }})
            </p>
            <p class="text-gray-700"><span class="font-medium">Role:</span> {{ user?.role }}</p>
        </div>
        <nav class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <router-link
                :to="{ name: 'usage.daily' }"
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-blue-300"
            >
                <span class="font-medium text-gray-900">Usage</span>
                <p class="mt-1 text-sm text-gray-500">Daily totals and record usage events</p>
            </router-link>
            <router-link
                :to="{ name: 'subscriptions.index' }"
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-blue-300"
            >
                <span class="font-medium text-gray-900">Subscriptions</span>
                <p class="mt-1 text-sm text-gray-500">Manage customer subscriptions</p>
            </router-link>
            <router-link
                :to="{ name: 'customers.index' }"
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-blue-300"
            >
                <span class="font-medium text-gray-900">Customers</span>
                <p class="mt-1 text-sm text-gray-500">Customer directory</p>
            </router-link>
        </nav>
    </div>
</template>

<script setup lang="ts">
import { useAuth } from '@/composables/useAuth';

const { user, merchant } = useAuth();
</script>
