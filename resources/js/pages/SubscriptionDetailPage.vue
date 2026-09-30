<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Subscription Detail</h2>
            <router-link
                :to="{ name: 'subscriptions.index' }"
                class="text-gray-600 hover:text-gray-800 text-sm"
            >
                ← Back to list
            </router-link>
        </div>

        <div
            v-if="error"
            class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm mb-4"
        >
            {{ error }}
        </div>

        <div v-if="loading" class="text-gray-500 text-sm">Loading…</div>

        <div v-else-if="sub" class="space-y-6">
            <div class="bg-white shadow rounded-lg p-6 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="font-medium text-gray-500">Customer:</span>
                    {{ sub.customer?.name }}
                </div>
                <div><span class="font-medium text-gray-500">Plan:</span> {{ sub.plan?.name }}</div>
                <div>
                    <span class="font-medium text-gray-500">Status:</span>
                    <span
                        class="ml-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="
                            sub.status === 'active'
                                ? 'bg-green-100 text-green-800'
                                : 'bg-gray-100 text-gray-800'
                        "
                    >
                        {{ sub.status }}
                    </span>
                </div>
                <div>
                    <span class="font-medium text-gray-500">Billing Cycle:</span>
                    {{ sub.billing_cycle }}
                </div>
                <div>
                    <span class="font-medium text-gray-500">Base Price:</span>
                    {{ formatMoney(sub.base_price) }}
                </div>
                <div>
                    <span class="font-medium text-gray-500">Included Units:</span>
                    {{ sub.included_usage_units.toLocaleString() }}
                </div>
                <div>
                    <span class="font-medium text-gray-500">Overage Rate:</span>
                    {{ formatMoney(sub.overage_rate) }}/unit
                </div>
                <div>
                    <span class="font-medium text-gray-500">Started:</span>
                    {{ formatDate(sub.started_at) }}
                </div>
                <div>
                    <span class="font-medium text-gray-500">Current Period:</span>
                    {{ formatDate(sub.current_period_start) }} —
                    {{ formatDate(sub.current_period_end) }}
                </div>
                <div v-if="sub.cancelled_at">
                    <span class="font-medium text-gray-500">Cancelled:</span>
                    {{ formatDate(sub.cancelled_at) }}
                </div>
            </div>

            <div v-if="isOwner" class="flex flex-wrap items-center gap-3">
                <button
                    v-if="billingPeriodEnded"
                    :disabled="generatingInvoice"
                    class="bg-emerald-600 text-white py-2 px-4 rounded font-medium text-sm hover:bg-emerald-700 disabled:opacity-50"
                    @click="handleGenerateInvoice"
                >
                    {{ generatingInvoice ? 'Generating…' : 'Generate invoice' }}
                </button>
                <p v-else class="text-sm text-gray-500">
                    Invoice generation is available after the current billing period ends ({{
                        formatDate(sub.current_period_end)
                    }}).
                </p>
                <template v-if="sub.status === 'active'">
                    <button
                        class="bg-blue-600 text-white py-2 px-4 rounded font-medium text-sm hover:bg-blue-700"
                        @click="showChangePlan = !showChangePlan"
                    >
                        Change Plan
                    </button>
                    <button
                        class="bg-red-600 text-white py-2 px-4 rounded font-medium text-sm hover:bg-red-700"
                        @click="handleCancel"
                    >
                        Cancel Subscription
                    </button>
                </template>
            </div>

            <div
                v-if="invoiceSuccessId"
                class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
            >
                Invoice created.
                <router-link
                    :to="{ name: 'invoices.show', params: { id: invoiceSuccessId } }"
                    class="font-medium text-green-900 underline"
                >
                    View invoice
                </router-link>
            </div>

            <div v-if="showChangePlan" class="bg-white shadow rounded-lg p-6 max-w-md">
                <h3 class="text-lg font-semibold mb-3">Change Plan</h3>
                <select
                    v-model="targetPlanId"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm mb-3"
                >
                    <option value="">Select new plan…</option>
                    <option v-for="p in plans" :key="p.id" :value="p.id">
                        {{ p.name }} — {{ formatMoney(p.base_price) }}/{{ p.billing_cycle }}
                    </option>
                </select>
                <button
                    :disabled="!targetPlanId || changing"
                    class="bg-blue-600 text-white py-2 px-4 rounded font-medium text-sm hover:bg-blue-700 disabled:opacity-50"
                    @click="handleChangePlan"
                >
                    {{ changing ? 'Changing…' : 'Confirm Change' }}
                </button>
            </div>

            <div v-if="sub.plan_changes && sub.plan_changes.length > 0">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Plan Change History</h3>
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Date
                                </th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    From
                                </th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    To
                                </th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Old Price
                                </th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    New Price
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="ch in sub.plan_changes" :key="ch.id">
                                <td class="px-4 py-2 text-gray-700">
                                    {{ formatDate(ch.effective_at) }}
                                </td>
                                <td class="px-4 py-2 text-gray-700">
                                    {{ ch.from_plan?.name ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-gray-700">
                                    {{ ch.to_plan?.name ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-gray-700">
                                    {{ formatMoney(ch.from_base_price) }}
                                </td>
                                <td class="px-4 py-2 text-gray-700">
                                    {{ formatMoney(ch.to_base_price) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import * as subsApi from '@/api/subscriptions';
import * as plansApi from '@/api/plans';
import type { SubscriptionData } from '@/types/subscriptions';
import type { Plan } from '@/types/plans';

const route = useRoute();
const { isOwner } = useAuth();

const sub = ref<SubscriptionData | null>(null);
const plans = ref<Plan[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const showChangePlan = ref(false);
const targetPlanId = ref('');
const changing = ref(false);
const generatingInvoice = ref(false);
const invoiceSuccessId = ref<string | null>(null);

const billingPeriodEnded = computed(() => {
    if (!sub.value?.current_period_end) return false;
    return new Date(sub.value.current_period_end).getTime() <= Date.now();
});

function formatMoney(paise: number): string {
    return '₹' + (paise / 100).toFixed(2);
}
function formatDate(iso: string): string {
    return new Date(iso).toLocaleString();
}

async function loadSubscription() {
    loading.value = true;
    try {
        sub.value = await subsApi.getSubscription(route.params.id as string);
        const pRes = await plansApi.getPlans(1, 'active');
        plans.value = pRes.data.filter((p) => p.id !== sub.value?.plan?.id);
    } catch {
        error.value = 'Failed to load subscription.';
    } finally {
        loading.value = false;
    }
}

async function handleChangePlan() {
    if (!targetPlanId.value || !sub.value) return;
    changing.value = true;
    error.value = null;
    try {
        await subsApi.changePlan(sub.value.id, targetPlanId.value);
        showChangePlan.value = false;
        targetPlanId.value = '';
        await loadSubscription();
    } catch {
        error.value = 'Failed to change plan.';
    } finally {
        changing.value = false;
    }
}

async function handleCancel() {
    if (!sub.value || !confirm('Cancel this subscription?')) return;
    try {
        await subsApi.cancelSubscription(sub.value.id);
        await loadSubscription();
    } catch {
        error.value = 'Failed to cancel subscription.';
    }
}

async function handleGenerateInvoice() {
    if (!sub.value || !confirm('Generate invoice for the current billing period?')) return;
    generatingInvoice.value = true;
    error.value = null;
    invoiceSuccessId.value = null;
    try {
        const invoice = await subsApi.generateSubscriptionInvoice(sub.value.id);
        invoiceSuccessId.value = invoice.id;
    } catch (e) {
        if (axios.isAxiosError(e) && e.response?.status === 422) {
            const errors = e.response.data?.errors as Record<string, string[]> | undefined;
            error.value =
                errors?.subscription?.[0] ?? 'Cannot generate invoice for this subscription.';
        } else {
            error.value = 'Failed to generate invoice.';
        }
    } finally {
        generatingInvoice.value = false;
    }
}

onMounted(() => loadSubscription());
</script>
