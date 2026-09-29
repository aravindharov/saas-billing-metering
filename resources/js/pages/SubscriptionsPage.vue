<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Subscriptions</h2>
            <router-link
                v-if="isOwner"
                :to="{ name: 'subscriptions.create' }"
                class="bg-blue-600 text-white py-2 px-4 rounded font-medium text-sm hover:bg-blue-700"
            >
                Create Subscription
            </router-link>
        </div>

        <div class="flex items-center gap-3 mb-4">
            <select
                v-model="statusFilter"
                class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                @change="loadSubscriptions(1)"
            >
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="cancelled">Cancelled</option>
                <option value="expired">Expired</option>
            </select>
        </div>

        <div
            v-if="error"
            class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm mb-4"
        >
            {{ error }}
        </div>

        <div v-if="loading" class="text-gray-500 text-sm">Loading subscriptions…</div>

        <div v-else-if="subscriptions.length === 0" class="text-gray-500 text-sm">
            No subscriptions found.
        </div>

        <div v-else>
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Customer
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Plan
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Cycle
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Base Price
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Period
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="sub in subscriptions" :key="sub.id">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ sub.customer?.name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ sub.plan?.name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="statusClass(sub.status)"
                                >
                                    {{ sub.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 capitalize">
                                {{ sub.billing_cycle }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ formatMoney(sub.base_price) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ formatDate(sub.current_period_start) }} —
                                {{ formatDate(sub.current_period_end) }}
                            </td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <router-link
                                    :to="{ name: 'subscriptions.show', params: { id: sub.id } }"
                                    class="text-blue-600 hover:text-blue-800"
                                >
                                    View
                                </router-link>
                                <button
                                    v-if="isOwner && sub.status === 'active'"
                                    class="text-red-600 hover:text-red-800"
                                    @click="confirmCancel(sub)"
                                >
                                    Cancel
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="meta && meta.last_page > 1"
                class="flex items-center justify-between mt-4 text-sm text-gray-600"
            >
                <span
                    >Page {{ meta.current_page }} of {{ meta.last_page }} ({{
                        meta.total
                    }}
                    subscriptions)</span
                >
                <div class="space-x-2">
                    <button
                        :disabled="meta.current_page <= 1"
                        class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100"
                        @click="loadSubscriptions(meta.current_page - 1)"
                    >
                        Previous
                    </button>
                    <button
                        :disabled="meta.current_page >= meta.last_page"
                        class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100"
                        @click="loadSubscriptions(meta.current_page + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import * as subsApi from '@/api/subscriptions';
import type { SubscriptionData } from '@/types/subscriptions';
import type { PaginatedResponse } from '@/types/plans';

const { isOwner } = useAuth();
const subscriptions = ref<SubscriptionData[]>([]);
const meta = ref<PaginatedResponse<SubscriptionData>['meta'] | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);
const statusFilter = ref('');

function formatMoney(paise: number): string {
    return '₹' + (paise / 100).toFixed(2);
}
function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString();
}
function statusClass(status: string): string {
    if (status === 'active') return 'bg-green-100 text-green-800';
    if (status === 'cancelled') return 'bg-red-100 text-red-800';
    return 'bg-gray-100 text-gray-800';
}

async function loadSubscriptions(page = 1) {
    loading.value = true;
    error.value = null;
    try {
        const response = await subsApi.getSubscriptions(page, statusFilter.value || undefined);
        subscriptions.value = response.data;
        meta.value = response.meta;
    } catch {
        error.value = 'Failed to load subscriptions.';
    } finally {
        loading.value = false;
    }
}

async function confirmCancel(sub: SubscriptionData) {
    if (!confirm(`Cancel subscription for "${sub.customer?.name}"?`)) return;
    try {
        await subsApi.cancelSubscription(sub.id);
        await loadSubscriptions(meta.value?.current_page ?? 1);
    } catch {
        error.value = 'Failed to cancel subscription.';
    }
}

onMounted(() => loadSubscriptions());
</script>
