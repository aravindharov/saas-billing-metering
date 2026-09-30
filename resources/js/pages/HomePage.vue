<template>
    <div>
        <h2 class="mb-2 text-2xl font-bold text-gray-800">Usage &amp; Billing Dashboard</h2>
        <p v-if="merchant" class="mb-6 text-sm text-gray-500">{{ merchant.name }}</p>

        <div
            v-if="error"
            class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            {{ error }}
        </div>

        <div v-if="loading" class="text-sm text-gray-500">Loading dashboard…</div>

        <div v-else-if="dashboard" class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase text-gray-500">Current month usage</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ formatUnits(dashboard.summary.current_month_usage_units) }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ dashboard.period.month_start }} → today (UTC)
                    </p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase text-gray-500">
                        Projected overage revenue
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ formatMoney(dashboard.projected_overage_revenue.amount) }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">Estimate for active billing cycles</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase text-gray-500">Active customers</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ dashboard.summary.active_customers }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ dashboard.summary.active_subscriptions }} active subscriptions
                    </p>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="mb-3 text-lg font-semibold text-gray-800">Current billing cycle</h3>
                <dl class="grid gap-2 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-gray-500">Cycle start (earliest)</dt>
                        <dd class="font-medium text-gray-900">
                            {{ formatDateTime(dashboard.billing_cycle.period_start) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Cycle end (latest)</dt>
                        <dd class="font-medium text-gray-900">
                            {{ formatDateTime(dashboard.billing_cycle.period_end) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Projected overage</dt>
                        <dd class="font-medium text-gray-900">
                            {{ formatMoney(dashboard.projected_overage_revenue.amount) }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <h3 class="border-b border-gray-100 px-4 py-3 text-lg font-semibold text-gray-800">
                    Top customers (this month)
                </h3>
                <div
                    v-if="dashboard.top_customers.length === 0"
                    class="px-4 py-6 text-sm text-gray-500"
                >
                    No usage recorded for the current month yet.
                </div>
                <table v-else class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Customer
                            </th>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Email
                            </th>
                            <th
                                class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500"
                            >
                                Usage
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="row in dashboard.top_customers" :key="row.customer_id">
                            <td class="px-4 py-2 font-medium text-gray-900">{{ row.name }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ row.email }}</td>
                            <td class="px-4 py-2 text-right text-gray-900">
                                {{ formatUnits(row.usage_units) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <h3 class="border-b border-gray-100 px-4 py-3 text-lg font-semibold text-gray-800">
                    Usage drops &gt;50% month-over-month
                </h3>
                <div
                    v-if="dashboard.usage_drops.length === 0"
                    class="px-4 py-6 text-sm text-gray-500"
                >
                    No customers with a greater than 50% usage drop (month-to-date comparison).
                </div>
                <table v-else class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Customer
                            </th>
                            <th
                                class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500"
                            >
                                Previous
                            </th>
                            <th
                                class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500"
                            >
                                Current
                            </th>
                            <th
                                class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500"
                            >
                                Change
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="row in dashboard.usage_drops" :key="row.customer_id">
                            <td class="px-4 py-2 font-medium text-gray-900">{{ row.name }}</td>
                            <td class="px-4 py-2 text-right text-gray-700">
                                {{ formatUnits(row.previous_usage_units) }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-700">
                                {{ formatUnits(row.current_usage_units) }}
                            </td>
                            <td class="px-4 py-2 text-right font-medium text-red-700">
                                {{ row.percentage_change }}%
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <nav class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <router-link
                :to="{ name: 'usage.daily' }"
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-blue-300"
            >
                <span class="font-medium text-gray-900">Usage</span>
                <p class="mt-1 text-sm text-gray-500">Daily totals and record events</p>
            </router-link>
            <router-link
                :to="{ name: 'invoices.index' }"
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-blue-300"
            >
                <span class="font-medium text-gray-900">Invoices</span>
                <p class="mt-1 text-sm text-gray-500">Issued billing documents</p>
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
import { onMounted, ref } from 'vue';
import { useAuth } from '@/composables/useAuth';
import * as dashboardApi from '@/api/dashboard';
import type { DashboardData } from '@/types/dashboard';

const { merchant } = useAuth();

const dashboard = ref<DashboardData | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

function formatMoney(paise: number): string {
    return '₹' + (paise / 100).toFixed(2);
}

function formatUnits(units: number): string {
    return units.toLocaleString();
}

function formatDateTime(iso: string | null): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleString();
}

async function loadDashboard() {
    if (!merchant.value?.id) {
        loading.value = false;
        return;
    }

    loading.value = true;
    error.value = null;
    try {
        dashboard.value = await dashboardApi.getMerchantDashboard(merchant.value.id);
    } catch {
        error.value = 'Failed to load dashboard analytics.';
        dashboard.value = null;
    } finally {
        loading.value = false;
    }
}

onMounted(() => loadDashboard());
</script>
