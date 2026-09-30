<template>
    <div class="ui-page">
        <UiPageHeader
            title="Usage &amp; Billing Dashboard"
            :description="merchant ? merchant.name : undefined"
        />

        <UiAlert v-if="error">{{ error }}</UiAlert>

        <UiLoading v-if="loading" message="Loading dashboard…" />

        <div v-else-if="dashboard" class="space-y-6">
            <div class="ui-stat-grid">
                <div class="ui-stat-card">
                    <p class="ui-stat-label">Current month usage</p>
                    <p class="ui-stat-value">
                        {{ formatUnits(dashboard.summary.current_month_usage_units) }}
                    </p>
                    <p class="ui-stat-hint">{{ dashboard.period.month_start }} → today (UTC)</p>
                </div>
                <div class="ui-stat-card">
                    <p class="ui-stat-label">Projected overage revenue</p>
                    <p class="ui-stat-value">
                        {{ formatMoney(dashboard.projected_overage_revenue.amount) }}
                    </p>
                    <p class="ui-stat-hint">Estimate for active billing cycles</p>
                </div>
                <div class="ui-stat-card">
                    <p class="ui-stat-label">Active customers</p>
                    <p class="ui-stat-value">{{ dashboard.summary.active_customers }}</p>
                    <p class="ui-stat-hint">
                        {{ dashboard.summary.active_subscriptions }} active subscriptions
                    </p>
                </div>
            </div>

            <section class="ui-card ui-card-body">
                <h3 class="ui-card-title mb-4">Current billing cycle</h3>
                <dl class="ui-dl-grid">
                    <div class="ui-dl-item">
                        <dt>Cycle start (earliest)</dt>
                        <dd>{{ formatDateTime(dashboard.billing_cycle.period_start) }}</dd>
                    </div>
                    <div class="ui-dl-item">
                        <dt>Cycle end (latest)</dt>
                        <dd>{{ formatDateTime(dashboard.billing_cycle.period_end) }}</dd>
                    </div>
                    <div class="ui-dl-item">
                        <dt>Projected overage</dt>
                        <dd>{{ formatMoney(dashboard.projected_overage_revenue.amount) }}</dd>
                    </div>
                </dl>
            </section>

            <section class="ui-card ui-card-body-flush">
                <div class="ui-card-header">
                    <h3 class="ui-card-title">Top customers (this month)</h3>
                </div>
                <UiEmpty v-if="dashboard.top_customers.length === 0" title="No usage this month">
                    No usage recorded for the current month yet.
                </UiEmpty>
                <table v-else class="ui-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th class="ui-table-num">Usage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in dashboard.top_customers" :key="row.customer_id">
                            <td class="ui-table-primary">{{ row.name }}</td>
                            <td>{{ row.email }}</td>
                            <td class="ui-table-num">{{ formatUnits(row.usage_units) }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="ui-card ui-card-body-flush">
                <div class="ui-card-header">
                    <h3 class="ui-card-title">Usage drops &gt;50% month-over-month</h3>
                </div>
                <UiEmpty v-if="dashboard.usage_drops.length === 0" title="No significant drops">
                    No customers with a greater than 50% usage drop (month-to-date comparison).
                </UiEmpty>
                <table v-else class="ui-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th class="ui-table-num">Previous</th>
                            <th class="ui-table-num">Current</th>
                            <th class="ui-table-num">Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in dashboard.usage_drops" :key="row.customer_id">
                            <td class="ui-table-primary">{{ row.name }}</td>
                            <td class="ui-table-num">
                                {{ formatUnits(row.previous_usage_units) }}
                            </td>
                            <td class="ui-table-num">
                                {{ formatUnits(row.current_usage_units) }}
                            </td>
                            <td class="ui-table-num font-medium text-red-700">
                                {{ row.percentage_change }}%
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>

        <nav class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <router-link :to="{ name: 'usage.daily' }" class="ui-quick-link">
                <span class="font-medium text-slate-900">Usage</span>
                <p class="mt-1 text-sm text-slate-500">Daily totals and record events</p>
            </router-link>
            <router-link :to="{ name: 'invoices.index' }" class="ui-quick-link">
                <span class="font-medium text-slate-900">Invoices</span>
                <p class="mt-1 text-sm text-slate-500">Issued billing documents</p>
            </router-link>
            <router-link :to="{ name: 'subscriptions.index' }" class="ui-quick-link">
                <span class="font-medium text-slate-900">Subscriptions</span>
                <p class="mt-1 text-sm text-slate-500">Manage customer subscriptions</p>
            </router-link>
            <router-link :to="{ name: 'customers.index' }" class="ui-quick-link">
                <span class="font-medium text-slate-900">Customers</span>
                <p class="mt-1 text-sm text-slate-500">Customer directory</p>
            </router-link>
        </nav>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import UiAlert from '@/components/UiAlert.vue';
import UiEmpty from '@/components/UiEmpty.vue';
import UiLoading from '@/components/UiLoading.vue';
import UiPageHeader from '@/components/UiPageHeader.vue';
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
