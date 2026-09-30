<template>
    <div class="ui-page">
        <UiPageHeader title="Subscriptions">
            <template #actions>
                <select
                    v-model="statusFilter"
                    class="ui-select w-auto min-w-[10rem]"
                    @change="loadSubscriptions(1)"
                >
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="expired">Expired</option>
                </select>
                <router-link
                    v-if="isOwner"
                    :to="{ name: 'subscriptions.create' }"
                    class="ui-btn-primary"
                >
                    Create Subscription
                </router-link>
            </template>
        </UiPageHeader>

        <UiAlert v-if="error">{{ error }}</UiAlert>
        <UiLoading v-if="loading" message="Loading subscriptions…" />
        <UiEmpty
            v-else-if="subscriptions.length === 0"
            title="No subscriptions found"
            message="No subscriptions found."
        />

        <template v-else>
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Cycle</th>
                            <th>Base Price</th>
                            <th>Period</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sub in subscriptions" :key="sub.id">
                            <td class="ui-table-primary">{{ sub.customer?.name ?? '—' }}</td>
                            <td>{{ sub.plan?.name ?? '—' }}</td>
                            <td>
                                <UiBadge :variant="statusVariant(sub.status)">
                                    {{ sub.status }}
                                </UiBadge>
                            </td>
                            <td class="capitalize">{{ sub.billing_cycle }}</td>
                            <td>{{ formatMoney(sub.base_price) }}</td>
                            <td class="text-slate-500 whitespace-nowrap">
                                {{ formatDate(sub.current_period_start) }} —
                                {{ formatDate(sub.current_period_end) }}
                            </td>
                            <td class="space-x-3 whitespace-nowrap">
                                <router-link
                                    :to="{ name: 'subscriptions.show', params: { id: sub.id } }"
                                    class="ui-link"
                                >
                                    View
                                </router-link>
                                <button
                                    v-if="isOwner && sub.status === 'active'"
                                    type="button"
                                    class="ui-btn-danger px-0 py-0"
                                    @click="confirmCancel(sub)"
                                >
                                    Cancel
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <UiPagination :meta="meta" label="subscriptions" @change="loadSubscriptions" />
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import UiAlert from '@/components/UiAlert.vue';
import UiBadge from '@/components/UiBadge.vue';
import UiEmpty from '@/components/UiEmpty.vue';
import UiLoading from '@/components/UiLoading.vue';
import UiPageHeader from '@/components/UiPageHeader.vue';
import UiPagination from '@/components/UiPagination.vue';
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
function statusVariant(status: string): 'success' | 'danger' | 'neutral' {
    if (status === 'active') return 'success';
    if (status === 'cancelled') return 'danger';
    return 'neutral';
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
